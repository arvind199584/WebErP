"""
Ordered migration: modpyphp (local) → Neon
Uses psycopg2 COPY TO/FROM for reliable data transfer.
Disables triggers before each table copy to bypass FK/audit constraints.
"""

import psycopg2
import io
import sys

import os

# ── Connection strings ──────────────────────────────────────────────────────
LOCAL_DSN = os.getenv("LOCAL_DATABASE_URL", "host=localhost port=5432 dbname=modpyphp user=postgres password=daredevil")
NEON_DSN  = os.getenv("NEON_DATABASE_URL") or os.getenv("DATABASE_URL") or (
    "postgresql://neondb_owner@ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech/neondb?sslmode=require"
)

# ── Table population order (user-specified + full dependency chain) ──────────
TABLE_ORDER = [
    # ── Core / standalone ──────────────────────────────────────────────────
    "tenants",                  # no deps
    "mutual_funds",             # no deps
    "stock_prices",             # no deps
    "historical_prices",        # no deps
    "pending_training",         # no deps
    "nlp_knowledge_base",       # no deps

    # ── Registry (metadata tables) ────────────────────────────────────────
    "registry_enums",
    "registry_tables",
    "registry_procedures",
    "registry_triggers",
    "registry_constraints",

    # ── Office (root of most relationships) ───────────────────────────────
    "office",                   # USER SPECIFIED #1

    # ── Users ─────────────────────────────────────────────────────────────
    "users",                    # USER SPECIFIED #2 — depends on office

    # ── Finance: Budget ───────────────────────────────────────────────────
    "budget",                   # USER SPECIFIED #3 — depends on office
    "opening_balances",         # depends on office

    # ── Works: AA/ES ──────────────────────────────────────────────────────
    "aa_es",                    # USER SPECIFIED #4 — depends on office, budget

    # ── Agencies ──────────────────────────────────────────────────────────
    "agencies",                 # USER SPECIFIED #5 — mostly standalone

    # ── Wage / HR: rates & items ──────────────────────────────────────────
    "wage_items",               # USER SPECIFIED #6a
    "tm",                       # depends on office
    "tm_rates",                 # depends on office, tm

    # ── HR: Members / Dependents ──────────────────────────────────────────
    "membership_sequences",
    "members",                  # depends on office
    "dependents",               # depends on members
    "mf_holdings",              # depends on members, mutual_funds

    # ── Works: Agreements (BEFORE employees — fk_emp_agreement) ───────────
    "agreements",               # USER SPECIFIED #7 — depends on office, agencies, aa_es

    # ── Employees (AFTER agreements — has fk_emp_agreement) ───────────────
    "employees",                # depends on office AND agreements

    # ── HR: Attendance & Wages (AFTER employees) ──────────────────────────
    "attendance_records",       # depends on office, employees
    "wage_orders",              # depends on office, employees, wage_items
    "wages_ledger",             # depends on wage_orders, employees
    "epf_ledger",               # depends on employees
    "esic_ledger",              # depends on employees

    # ── Works: Work Orders ────────────────────────────────────────────────
    "work_orders",              # USER SPECIFIED #8 — depends on office, agreements

    # ── Works: Supply Orders ──────────────────────────────────────────────
    "supply_orders",            # USER SPECIFIED #9 — depends on office, agreements

    # ── Finance: Bills ────────────────────────────────────────────────────
    "bills",                    # depends on office, agreements
    "hand_receipts",            # depends on office, budget

    # ── Admin / Communication ─────────────────────────────────────────────
    "address_book",             # depends on office
    "internal_emails",          # depends on office, users

    # ── Turf Module ───────────────────────────────────────────────────────
    "turf_inventory_categories",
    "turf_inventory_items",     # depends on categories
    "turf_machines",            # depends on fuel item id in turf_inventory_items
    "turf_inventory_ledger",    # depends on items, machines
    "turf_consumption_log",     # depends on machines
    "water_log",

    # ── GC Module ─────────────────────────────────────────────────────────
    "gc_items",
    "gc_machinery",
    "gc_main_stock_transactions",
    "gc_sub_ledger_consumption",

    # ── Audit log (last — references everything) ──────────────────────────
    "activity_logs",
]


def connect_local():
    conn = psycopg2.connect(LOCAL_DSN)
    conn.autocommit = True
    return conn

def connect_neon():
    conn = psycopg2.connect(NEON_DSN)
    conn.autocommit = True
    return conn

def get_row_count(cur, table):
    cur.execute(f"SELECT COUNT(*) FROM public.{table}")
    return cur.fetchone()[0]

def get_common_columns(local_cur, neon_cur, table):
    local_cur.execute(f"SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{table}' ORDER BY ordinal_position")
    local_cols = set(r[0] for r in local_cur.fetchall())
    neon_cur.execute(f"SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = '{table}' ORDER BY ordinal_position")
    neon_cols = set(r[0] for r in neon_cur.fetchall())
    common = [c for c in local_cols if c in neon_cols]
    return common

def copy_table(local_cur, neon_cur, table):
    """Copy one table: local → Neon via in-memory buffer."""
    buf = io.StringIO()
    cols = get_common_columns(local_cur, neon_cur, table)
    if not cols:
        return 0
    cols_str = ", ".join(f'"{c}"' for c in cols)

    # Export from local matching Neon columns
    local_cur.copy_expert(f"COPY public.{table} ({cols_str}) TO STDOUT", buf)
    data = buf.getvalue()

    if not data.strip():
        return 0  # empty table

    # Disable triggers on Neon for this table
    neon_cur.execute(f"ALTER TABLE public.{table} DISABLE TRIGGER USER")

    # Import to Neon specifying exact columns
    neon_cur.copy_expert(f"COPY public.{table} ({cols_str}) FROM STDIN", io.StringIO(data))

    # Re-enable triggers
    neon_cur.execute(f"ALTER TABLE public.{table} ENABLE TRIGGER USER")

    return data.count('\n')


def run():
    print("Connecting to local PostgreSQL...")
    local = connect_local()
    local_cur = local.cursor()

    print("Connecting to Neon...")
    neon = connect_neon()
    neon_cur = neon.cursor()

    # Drop circular FKs temporarily for smooth copy
    print("Temporarily dropping circular FK constraints on Neon...")
    neon_cur.execute("ALTER TABLE public.turf_inventory_items DROP CONSTRAINT IF EXISTS turf_inventory_items_machine_id_fkey")
    neon_cur.execute("ALTER TABLE public.turf_machines DROP CONSTRAINT IF EXISTS turf_machines_fuel_item_id_fkey")

    print(f"\nMigrating {len(TABLE_ORDER)} tables in dependency order...\n")
    print(f"{'Table':<35} {'Local Rows':>12} {'Migrated':>10} {'Status'}")
    print("-" * 70)

    results = []
    total_rows = 0
    errors = []

    for table in TABLE_ORDER:
        try:
            # Get local row count
            local_count = get_row_count(local_cur, table)

            # Truncate on Neon first
            neon_cur.execute(f"TRUNCATE TABLE public.{table} CASCADE")

            # Copy
            rows = copy_table(local_cur, neon_cur, table)

            # Verify
            neon_count = get_row_count(neon_cur, table)

            status = "OK" if neon_count == local_count else f"MISMATCH (got {neon_count})"
            print(f"{table:<35} {local_count:>12,} {neon_count:>10,}  {status}")
            total_rows += neon_count
            results.append((table, local_count, neon_count, status))

        except Exception as e:
            err = str(e).strip()
            print(f"{table:<35} {'ERROR':>12}            ERROR: {err[:60]}")
            errors.append((table, err))
            # Reconnect after error
            try: neon.close()
            except: pass
            try: local.close()
            except: pass
            neon = connect_neon()
            neon_cur = neon.cursor()
            local = connect_local()
            local_cur = local.cursor()

    print("-" * 70)
    print(f"\nTotal rows migrated : {total_rows:,}")
    print(f"Tables succeeded    : {len(results) - len(errors)}/{len(TABLE_ORDER)}")
    print(f"Tables with errors  : {len(errors)}")
    if errors:
        print("\nErrors:")
        for t, e in errors:
            print(f"  [{t}] {e[:120]}")

    # Re-add circular FKs
    print("\nRe-adding circular FK constraints on Neon...")
    try:
        neon_cur.execute("ALTER TABLE public.turf_inventory_items ADD CONSTRAINT turf_inventory_items_machine_id_fkey FOREIGN KEY (machine_id) REFERENCES public.turf_machines(id) ON DELETE SET NULL")
        neon_cur.execute("ALTER TABLE public.turf_machines ADD CONSTRAINT turf_machines_fuel_item_id_fkey FOREIGN KEY (fuel_item_id) REFERENCES public.turf_inventory_items(id) ON DELETE SET NULL")
        print("FK constraints restored successfully!")
    except Exception as e:
        print(f"Warning restoring FK constraints: {e}")

    local_cur.close(); local.close()
    neon_cur.close(); neon.close()

if __name__ == "__main__":
    run()
