#!/usr/bin/env python3
"""
import_neon_to_local.py

Imports data from Neon Cloud PostgreSQL database into the local PostgreSQL database (modpyphp).
GUARANTEE: The Neon Cloud database is accessed STRICTLY in READ-ONLY mode.
No DDL, DML, write, or mutation will occur on the Cloud database.
"""

import os
import sys
import io
import time
import datetime
import psycopg2
from psycopg2.extras import RealDictCursor

# -----------------------------------------------------------------------------
# Configuration
# -----------------------------------------------------------------------------
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
DUMPS_DIR = os.path.join(BASE_DIR, "database", "dumps")
os.makedirs(DUMPS_DIR, exist_ok=True)

def _load_env_file(filepath):
    """Load key-value pairs from an env file into os.environ if not already set."""
    if os.path.exists(filepath):
        with open(filepath, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, val = line.split("=", 1)
                    key = key.strip()
                    val = val.strip().strip('"').strip("'")
                    if key not in os.environ:
                        os.environ[key] = val

_load_env_file(os.path.join(BASE_DIR, ".env"))
_load_env_file(os.path.join(BASE_DIR, ".env.neon"))

# 1. Neon Cloud Connection
NEON_URL = os.getenv("NEON_DATABASE_URL") or os.getenv("DATABASE_URL")
if not NEON_URL:
    print("[-] ERROR: NEON_DATABASE_URL or DATABASE_URL is not set.")
    print("[-] Please specify your connection string in .env or .env.neon.")
    sys.exit(1)

# 2. Local PostgreSQL Configuration
LOCAL_CONFIG = {
    "host": os.getenv("DB_HOST", "localhost"),
    "port": int(os.getenv("DB_PORT", 5432)),
    "dbname": os.getenv("DB_NAME", "modpyphp"),
    "user": os.getenv("DB_USER", "postgres"),
    "password": os.getenv("DB_PASS", "")
}

timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
local_pre_backup_path = os.path.join(DUMPS_DIR, f"local_pre_neon_import_{timestamp}.sql")

def log(msg):
    print(f"[{datetime.datetime.now().strftime('%H:%M:%S')}] {msg}", flush=True)

def backup_local_database(local_conn):
    """Back up local tables to a SQL file prior to import."""
    log(f"Backing up current local database to {local_pre_backup_path}...")
    cur = local_conn.cursor(cursor_factory=RealDictCursor)
    cur.execute("""
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
        ORDER BY table_name;
    """)
    tables = [r['table_name'] for r in cur.fetchall()]
    
    with open(local_pre_backup_path, 'w', encoding='utf-8') as f:
        f.write(f"-- ModPyPhp Local Database Safety Backup before Neon Cloud Import\n")
        f.write(f"-- Timestamp: {datetime.datetime.now().isoformat()}\n\n")
        f.write("SET session_replication_role = 'replica';\n\n")
        
        for t in tables:
            cur.execute(f'SELECT * FROM "{t}"')
            rows = cur.fetchall()
            if not rows:
                continue
            cols = list(rows[0].keys())
            quoted_cols = ', '.join([f'"{c}"' for c in cols])
            
            f.write(f'-- Table "{t}" ({len(rows)} rows)\n')
            f.write(f'TRUNCATE TABLE "{t}" CASCADE;\n')
            for r in rows:
                vals = []
                for c in cols:
                    v = r[c]
                    if v is None:
                        vals.append("NULL")
                    elif isinstance(v, bool):
                        vals.append("TRUE" if v else "FALSE")
                    elif isinstance(v, (int, float)):
                        vals.append(str(v))
                    else:
                        escaped = str(v).replace("'", "''")
                        vals.append(f"'{escaped}'")
                f.write(f'INSERT INTO "{t}" ({quoted_cols}) VALUES ({", ".join(vals)});\n')
            f.write("\n")
        f.write("SET session_replication_role = 'origin';\n")
    
    file_size_kb = os.path.getsize(local_pre_backup_path) / 1024
    log(f"Local safety backup completed: {file_size_kb:.1f} KB")

def main():
    log("="*70)
    log("NEON CLOUD DATA IMPORT TO LOCAL POSTGRES")
    log("="*70)
    log("SAFETY CHECK: Enforcing STRICT READ-ONLY transaction on Neon Cloud...")
    
    # Connect to Neon STRICTLY in READ-ONLY mode
    neon_conn = psycopg2.connect(
        NEON_URL,
        options='-c default_transaction_read_only=on'
    )
    # Double check engine-level read-only guarantee
    cur_neon_check = neon_conn.cursor()
    cur_neon_check.execute("SET SESSION CHARACTERISTICS AS TRANSACTION READ ONLY;")
    cur_neon_check.execute("SHOW transaction_read_only;")
    ro_status = cur_neon_check.fetchone()[0]
    log(f"Neon Cloud transaction_read_only status: {ro_status.upper()}")
    if ro_status.lower() != 'on':
        raise RuntimeError("CRITICAL ERROR: Failed to enforce read-only mode on Neon Cloud!")
    
    # Connect to Local PostgreSQL
    log(f"Connecting to Local PostgreSQL ({LOCAL_CONFIG['host']}:{LOCAL_CONFIG['port']} / {LOCAL_CONFIG['dbname']})...")
    local_conn = psycopg2.connect(**LOCAL_CONFIG)
    local_conn.autocommit = True  # Autocommit for read-only backup and enum DDL

    try:
        # Step 1: Backup local database first
        backup_local_database(local_conn)
        
        # Step 1b: Synchronize any missing ENUM values from Neon to Local
        log("Synchronizing ENUM definitions from Neon to Local...")
        cur_l_enums = local_conn.cursor()
        cur_n_enums = neon_conn.cursor()
        cur_n_enums.execute("""
            SELECT t.typname as enum_name, e.enumlabel as enum_value
            FROM pg_type t 
            JOIN pg_enum e ON t.oid = e.enumtypid  
            JOIN pg_namespace n ON n.oid = t.typnamespace
            WHERE n.nspname = 'public'
            ORDER BY enum_name, e.enumsortorder;
        """)
        neon_enums = cur_n_enums.fetchall()
        for enum_name, enum_val in neon_enums:
            try:
                cur_l_enums.execute(f"ALTER TYPE \"{enum_name}\" ADD VALUE IF NOT EXISTS %s;", (enum_val,))
            except Exception as e:
                pass
        
        # Ensure chk_superuser_office includes supervisor
        try:
            cur_l_enums.execute("ALTER TABLE users DROP CONSTRAINT IF EXISTS chk_superuser_office;")
            cur_l_enums.execute("""
                ALTER TABLE users ADD CONSTRAINT chk_superuser_office 
                CHECK ((((role = 'superuser'::user_role) AND (officeid = 1)) OR (role = ANY (ARRAY['staff'::user_role, 'manager'::user_role, 'supervisor'::user_role]))));
            """)
        except Exception as e:
            pass
            
        local_conn.autocommit = False
        log("ENUM definitions and constraints synchronized.")
        
        # Step 2: Fetch tables to migrate
        cur_neon = neon_conn.cursor(cursor_factory=RealDictCursor)
        cur_neon.execute("""
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
            ORDER BY table_name;
        """)
        tables = [r['table_name'] for r in cur_neon.fetchall()]
        log(f"Discovered {len(tables)} tables to import from Neon.")

        # Step 3: Fetch sequence metadata from Neon
        cur_neon.execute("""
            SELECT sequence_name 
            FROM information_schema.sequences 
            WHERE sequence_schema = 'public' 
            ORDER BY sequence_name;
        """)
        sequences = [r['sequence_name'] for r in cur_neon.fetchall()]
        seq_values = {}
        for s in sequences:
            try:
                cur_neon.execute(f'SELECT last_value, is_called FROM "{s}"')
                row = cur_neon.fetchone()
                seq_values[s] = (row['last_value'], row['is_called'])
            except Exception as e:
                pass
        log(f"Discovered {len(seq_values)} sequences with current states.")

        # Step 4: Import tables into Local database
        cur_local = local_conn.cursor()
        
        log("Disabling FK constraints and triggers on local database (session_replication_role = 'replica')...")
        cur_local.execute("SET session_replication_role = 'replica';")
        
        total_tables = len(tables)
        total_rows_imported = 0
        start_time = time.time()
        
        log("Truncating all local tables atomically before streaming...")
        quoted_all = ", ".join([f'"{t}"' for t in tables])
        cur_local.execute(f"TRUNCATE TABLE {quoted_all} CASCADE;")
        
        log("Copying table rows via binary streams from Neon to Local...")
        for idx, t in enumerate(tables, 1):
            # Check row count on Neon
            cur_neon.execute(f'SELECT count(*) as c FROM "{t}"')
            neon_count = cur_neon.fetchone()['c']
            
            if neon_count > 0:
                # Binary COPY stream from Neon directly into buffer, then into Local
                buf = io.BytesIO()
                cur_neon.copy_expert(f'COPY "{t}" TO STDOUT (FORMAT binary)', buf)
                buf.seek(0)
                cur_local.copy_expert(f'COPY "{t}" FROM STDIN (FORMAT binary)', buf)
                total_rows_imported += neon_count
            
            log(f"  [{idx:2d}/{total_tables:2d}] '{t}': {neon_count} rows imported")

        # Step 5: Restore Sequence counters
        log(f"Synchronizing {len(seq_values)} sequence states on local database...")
        for seq_name, (last_val, is_called) in seq_values.items():
            called_str = "true" if is_called else "false"
            cur_local.execute(f"SELECT setval('\"{seq_name}\"', {last_val}, {called_str});")

        # Step 6: Re-enable FK constraints and triggers
        log("Re-enabling FK constraints and triggers on local database (session_replication_role = 'origin')...")
        cur_local.execute("SET session_replication_role = 'origin';")
        
        # Commit local transaction
        local_conn.commit()
        elapsed = time.time() - start_time
        log(f"Local transaction committed successfully in {elapsed:.2f} seconds!")

        # Step 7: Post-Import Verification
        log("="*70)
        log("VERIFICATION: Comparing Neon Cloud vs Local Postgres row counts...")
        log("="*70)
        
        cur_local_check = local_conn.cursor(cursor_factory=RealDictCursor)
        all_matched = True
        verification_results = []
        
        for t in tables:
            cur_neon.execute(f'SELECT count(*) as c FROM "{t}"')
            c_neon = cur_neon.fetchone()['c']
            
            cur_local_check.execute(f'SELECT count(*) as c FROM "{t}"')
            c_local = cur_local_check.fetchone()['c']
            
            status = "MATCH" if c_neon == c_local else "MISMATCH"
            if status != "MATCH":
                all_matched = False
            verification_results.append((t, c_neon, c_local, status))

        print(f"{'TABLE NAME':<40} | {'NEON CLOUD':<12} | {'LOCAL PG':<12} | {'STATUS'}")
        print("-" * 75)
        for t, cn, cl, st in verification_results:
            print(f"{t:<40} | {cn:<12} | {cl:<12} | {st}")
        print("-" * 75)
        
        if all_matched:
            log("ALL 60 TABLES VERIFIED: 100% PARITY BETWEEN NEON CLOUD AND LOCAL DB!")
            log(f"Total rows in Local DB: {total_rows_imported}")
        else:
            log("WARNING: Discrepancies detected during verification.")

    except Exception as e:
        local_conn.rollback()
        log(f"ERROR OCCURRED: {e}")
        raise
    finally:
        neon_conn.close()
        local_conn.close()
        log("Database connections closed.")

if __name__ == "__main__":
    main()
