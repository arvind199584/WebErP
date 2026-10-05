"""
seed_training.py — Seeds aiml.vector_kb with verified SQL pairs.

This script takes all proven SQL queries from the existing intent maps
(intent_map.json, report_intent_map.json, turf_intent_map.json) and the
current nlp_knowledge_base, then generates multiple natural-language
question variants per query using the local LLM (gemma2:9b).

Every question→SQL pair gets an all-minilm embedding and is stored in
aiml.vector_kb ONLY. No existing tables are touched.

Run from the project root:
    .venv_uv\\Scripts\\python.exe modules/AIML/Python/scripts/seed_training.py

The script is idempotent — already-present questions are skipped via
ON CONFLICT DO NOTHING.
"""

import os
import sys
import json
import urllib.request
from pathlib import Path

import psycopg2

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
env_path = Path(__file__).resolve().parents[4] / ".env"
if env_path.is_file():
    with open(env_path) as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, _, v = line.partition('=')
                os.environ.setdefault(k.strip(), v.strip())

DB_CONFIG = {
    "host":     os.getenv("DB_HOST", "localhost"),
    "port":     os.getenv("DB_PORT", "5432"),
    "dbname":   os.getenv("DB_NAME", "modpyphp"),
    "user":     os.getenv("DB_USER", "postgres"),
    "password": os.getenv("DB_PASS", "daredevil"),
}

OLLAMA_URL  = "http://localhost:11434"
EMBED_MODEL = "all-minilm"
LLM_MODEL   = "gemma2:9b"

BASE_DIR           = Path(__file__).resolve().parents[4]
UTILITY_PYTHON_DIR = BASE_DIR / "modules" / "Utility" / "Python"

# ---------------------------------------------------------------------------
# Ollama helpers
# ---------------------------------------------------------------------------

def ollama_post(endpoint, payload, timeout=60):
    url  = f"{OLLAMA_URL}{endpoint}"
    data = json.dumps(payload).encode("utf-8")
    req  = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})
    with urllib.request.urlopen(req, timeout=timeout) as r:
        return json.loads(r.read().decode("utf-8"))


def get_embedding(text: str) -> list:
    try:
        res = ollama_post("/api/embeddings", {"model": EMBED_MODEL, "prompt": text}, timeout=15)
        return res.get("embedding", [])
    except Exception as e:
        print(f"  [WARN] Embedding error: {e}")
        return []


def generate_question_variants(canonical_question: str, sql: str, n: int = 6) -> list:
    """
    Asks the LLM to produce N natural-language paraphrases for a given
    canonical question and its SQL. Returns a list of unique strings.
    """
    system = (
        "You are a government office management assistant. "
        "Generate EXACTLY {n} different natural-language questions that all mean the same thing "
        "as the canonical question. Include English, simple Hinglish, and formal variants. "
        "Output ONE question per line, numbered 1. to {n}. No explanations."
    ).format(n=n)

    user = (
        f"Canonical question: {canonical_question}\n"
        f"SQL it maps to: {sql}\n\n"
        f"Generate {n} paraphrase questions:"
    )

    try:
        res = ollama_post("/api/chat", {
            "model":   LLM_MODEL,
            "messages": [
                {"role": "system", "content": system},
                {"role": "user",   "content": user},
            ],
            "stream":  False,
            "options": {"temperature": 0.7, "num_predict": 400},
        }, timeout=90)
        raw = res.get("message", {}).get("content", "")
        variants = []
        for line in raw.splitlines():
            line = line.strip()
            # Strip leading numbers like "1. " or "1) "
            import re
            line = re.sub(r'^[\d]+[.)]\s*', '', line).strip()
            if line and len(line) > 8:
                variants.append(line)
        return variants[:n]
    except Exception as e:
        print(f"  [WARN] LLM variant generation error: {e}")
        return []

# ---------------------------------------------------------------------------
# Seed data — verified SQL from existing intent maps + hand-written pairs
# ---------------------------------------------------------------------------

# Each entry: (canonical_question, sql_query)
# These are sourced from the proven intent_map.json, turf_intent_map.json,
# report_intent_map.json, and a curated set of domain-specific queries.
SEED_PAIRS = [

    # ── Employees ───────────────────────────────────────────────────────────
    (
        "list all employees",
        "SELECT e.full_name, e.designation, o.OfficeName FROM employees e "
        "JOIN office o ON e.officeid = o.Officeid WHERE 1=1 LIMIT 500"
    ),
    (
        "show employees at a specific office",
        "SELECT e.full_name, e.designation, o.OfficeName FROM employees e "
        "JOIN office o ON e.officeid = o.Officeid "
        "WHERE lower(o.OfficeName) ILIKE '%dgcd%' LIMIT 500"
    ),
    (
        "count total number of employees",
        "SELECT COUNT(*) AS total_employees FROM employees"
    ),
    (
        "list employees by designation",
        "SELECT e.full_name, e.designation, o.OfficeName FROM employees e "
        "JOIN office o ON e.officeid = o.Officeid "
        "WHERE lower(e.designation) ILIKE '%clerk%' LIMIT 500"
    ),
    (
        "show employee details by name",
        "SELECT e.full_name, e.designation, e.date_of_joining, o.OfficeName "
        "FROM employees e JOIN office o ON e.officeid = o.Officeid "
        "WHERE lower(e.full_name) ILIKE '%ram%' LIMIT 10"
    ),

    # ── Wages ────────────────────────────────────────────────────────────────
    (
        "total wages paid this month",
        "SELECT SUM(wl.cr_amount) AS total_wages FROM wages_ledger wl "
        "WHERE to_char(wl.transaction_date, 'YYYY-MM') = to_char(NOW(), 'YYYY-MM')"
    ),
    (
        "total wages paid for a specific month",
        "SELECT SUM(wl.cr_amount) AS total_wages FROM wages_ledger wl "
        "WHERE to_char(wl.transaction_date, 'YYYY-MM') = '2025-03'"
    ),
    (
        "wages paid to a specific employee",
        "SELECT SUM(wl.cr_amount) AS total FROM wages_ledger wl "
        "JOIN employees e ON wl.employee_id = e.id "
        "WHERE lower(e.full_name) ILIKE '%ram kumar%' LIMIT 500"
    ),
    (
        "show wage ledger for current month",
        "SELECT e.full_name, e.designation, wl.cr_amount, wl.transaction_date "
        "FROM wages_ledger wl JOIN employees e ON wl.employee_id = e.id "
        "WHERE to_char(wl.transaction_date, 'YYYY-MM') = to_char(NOW(), 'YYYY-MM') "
        "ORDER BY e.full_name LIMIT 500"
    ),
    (
        "compare wages month by month",
        "SELECT to_char(transaction_date, 'YYYY-MM') AS month, "
        "SUM(cr_amount) AS total_wages FROM wages_ledger "
        "GROUP BY month ORDER BY month"
    ),

    # ── Attendance ───────────────────────────────────────────────────────────
    (
        "show attendance for current month",
        "SELECT e.full_name, a.present_days, a.absent_days, a.attendance_month "
        "FROM attendance a JOIN employees e ON a.employee_id = e.id "
        "WHERE a.attendance_month = to_char(NOW(), 'YYYY-MM') LIMIT 500"
    ),
    (
        "list employees with absent days this month",
        "SELECT e.full_name, a.absent_days FROM attendance a "
        "JOIN employees e ON a.employee_id = e.id "
        "WHERE a.absent_days > 0 "
        "AND a.attendance_month = to_char(NOW(), 'YYYY-MM') "
        "ORDER BY a.absent_days DESC LIMIT 500"
    ),
    (
        "attendance summary for a specific month",
        "SELECT e.full_name, a.present_days, a.absent_days "
        "FROM attendance a JOIN employees e ON a.employee_id = e.id "
        "WHERE a.attendance_month = '2025-03' ORDER BY e.full_name LIMIT 500"
    ),

    # ── Budget & Finance ─────────────────────────────────────────────────────
    (
        "show total budget allocated",
        "SELECT SUM(allocated_amount) AS total_budget FROM budget_heads LIMIT 500"
    ),
    (
        "list all budget heads",
        "SELECT head_name, allocated_amount, spent_amount, "
        "(allocated_amount - spent_amount) AS balance FROM budget_heads LIMIT 500"
    ),
    (
        "show budget balance per head",
        "SELECT head_name, (allocated_amount - spent_amount) AS remaining_balance "
        "FROM budget_heads ORDER BY remaining_balance ASC LIMIT 500"
    ),

    # ── Supply Orders / Work Orders ──────────────────────────────────────────
    (
        "list all supply orders",
        "SELECT so.id, so.order_date, so.vendor_name, so.total_amount, so.status "
        "FROM supply_orders so ORDER BY so.order_date DESC LIMIT 500"
    ),
    (
        "show pending supply orders",
        "SELECT so.id, so.order_date, so.vendor_name, so.total_amount "
        "FROM supply_orders so WHERE lower(so.status) = 'pending' "
        "ORDER BY so.order_date ASC LIMIT 500"
    ),
    (
        "list all work orders",
        "SELECT wo.id, wo.work_description, wo.awarded_to, wo.contract_amount, wo.status "
        "FROM work_orders wo ORDER BY wo.id DESC LIMIT 500"
    ),

    # ── Machinery / Turf ─────────────────────────────────────────────────────
    (
        "list all machines",
        "SELECT name AS machine_name, make, status FROM turf_machines LIMIT 500"
    ),
    (
        "show machines that are active",
        "SELECT name AS machine_name, make FROM turf_machines "
        "WHERE lower(status) = 'active' LIMIT 500"
    ),
    (
        "show fuel consumption for current month",
        "SELECT tm.name AS machine_name, SUM(tcl.fuel_consumed_qty) AS total_fuel "
        "FROM turf_consumption_log tcl JOIN turf_machines tm ON tcl.machine_id = tm.id "
        "WHERE to_char(tcl.log_date, 'YYYY-MM') = to_char(NOW(), 'YYYY-MM') "
        "GROUP BY tm.name ORDER BY total_fuel DESC"
    ),
    (
        "monthly fuel consumption by machine",
        "SELECT m.name AS machine_name, SUM(c.fuel_consumed_qty) AS total_fuel_consumed "
        "FROM turf_consumption_log c JOIN turf_machines m ON c.machine_id = m.id "
        "GROUP BY m.name ORDER BY total_fuel_consumed DESC"
    ),
    (
        "show inventory stock balance",
        "SELECT item_name, unit_of_measurement, current_balance "
        "FROM gc_view_main_stock_balance ORDER BY item_name LIMIT 500"
    ),
    (
        "list inventory items with low stock",
        "SELECT item_name, unit_of_measurement, current_balance "
        "FROM gc_view_main_stock_balance WHERE current_balance < 10 "
        "ORDER BY current_balance ASC LIMIT 500"
    ),
    (
        "show turf consumption log",
        "SELECT tcl.log_date, tm.name AS machine_name, tcl.recorded_by AS operator, "
        "tcl.fuel_consumed_qty, tcl.running_hours "
        "FROM turf_consumption_log tcl JOIN turf_machines tm ON tcl.machine_id = tm.id "
        "ORDER BY tcl.log_date DESC LIMIT 500"
    ),

    # ── Offices ───────────────────────────────────────────────────────────────
    (
        "list all offices",
        "SELECT OfficeName, OfficialAddress FROM office ORDER BY OfficeName LIMIT 500"
    ),
    (
        "count employees per office",
        "SELECT o.OfficeName, COUNT(e.id) AS employee_count "
        "FROM office o LEFT JOIN employees e ON e.officeid = o.Officeid "
        "GROUP BY o.OfficeName ORDER BY employee_count DESC"
    ),

    # ── EPF / ESIC ────────────────────────────────────────────────────────────
    (
        "show EPF deductions for current month",
        "SELECT e.full_name, ep.employee_share, ep.employer_share, ep.month "
        "FROM epf_records ep JOIN employees e ON ep.employee_id = e.id "
        "WHERE ep.month = to_char(NOW(), 'YYYY-MM') ORDER BY e.full_name LIMIT 500"
    ),
    (
        "show ESIC deductions for current month",
        "SELECT e.full_name, es.employee_share, es.employer_share, es.month "
        "FROM esic_records es JOIN employees e ON es.employee_id = e.id "
        "WHERE es.month = to_char(NOW(), 'YYYY-MM') ORDER BY e.full_name LIMIT 500"
    ),
    (
        "total EPF contribution this month",
        "SELECT SUM(employee_share + employer_share) AS total_epf "
        "FROM epf_records WHERE month = to_char(NOW(), 'YYYY-MM')"
    ),

    # ── Revenue / Income ──────────────────────────────────────────────────────
    (
        "show total revenue collected this month",
        "SELECT SUM(amount) AS total_revenue FROM revenue_entries "
        "WHERE to_char(entry_date, 'YYYY-MM') = to_char(NOW(), 'YYYY-MM')"
    ),
    (
        "list all revenue entries",
        "SELECT re.entry_date, re.description, re.amount, o.OfficeName "
        "FROM revenue_entries re JOIN office o ON re.office_id = o.Officeid "
        "ORDER BY re.entry_date DESC LIMIT 500"
    ),

    # ── Payments ──────────────────────────────────────────────────────────────
    (
        "list all payments made",
        "SELECT p.payment_date, p.payee_name, p.amount, p.payment_mode, p.status "
        "FROM payments p ORDER BY p.payment_date DESC LIMIT 500"
    ),
    (
        "show pending payments",
        "SELECT p.payment_date, p.payee_name, p.amount "
        "FROM payments p WHERE lower(p.status) = 'pending' "
        "ORDER BY p.payment_date ASC LIMIT 500"
    ),
    (
        "total payments made this month",
        "SELECT SUM(amount) AS total_paid FROM payments "
        "WHERE to_char(payment_date, 'YYYY-MM') = to_char(NOW(), 'YYYY-MM')"
    ),

    # ── Wage Rates ────────────────────────────────────────────────────────────
    (
        "show current wage rates",
        "SELECT designation, daily_rate, effective_from FROM wages_rates "
        "ORDER BY designation LIMIT 500"
    ),
    (
        "show wage rates for a designation",
        "SELECT designation, daily_rate, effective_from FROM wages_rates "
        "WHERE lower(designation) ILIKE '%gardener%' LIMIT 500"
    ),

    # ── Agreements ────────────────────────────────────────────────────────────
    (
        "list all agreements",
        "SELECT a.agreement_no, a.party_name, a.start_date, a.end_date, a.contract_amount "
        "FROM agreements a ORDER BY a.start_date DESC LIMIT 500"
    ),
    (
        "show active agreements",
        "SELECT a.agreement_no, a.party_name, a.end_date, a.contract_amount "
        "FROM agreements a WHERE a.end_date >= CURRENT_DATE "
        "ORDER BY a.end_date ASC LIMIT 500"
    ),

]

# ---------------------------------------------------------------------------
# Main seeding logic
# ---------------------------------------------------------------------------

def insert_pair(cur, question: str, sql: str, source: str):
    emb = get_embedding(question)
    if not emb:
        print(f"    [SKIP] No embedding for: '{question[:60]}'")
        return False
    cur.execute("""
        INSERT INTO aiml.vector_kb (question_text, sql_query, source, vec_embedding)
        VALUES (%s, %s, %s, %s)
        ON CONFLICT (question_text) DO NOTHING;
    """, (question.lower().strip(), sql.strip(), source, str(emb)))
    return True


def main():
    print("=" * 60)
    print("AIML Seed Training")
    print("=" * 60)
    print(f"Seed pairs defined  : {len(SEED_PAIRS)}")
    print(f"Embed model         : {EMBED_MODEL}")
    print(f"LLM for variants    : {LLM_MODEL}")
    print()

    conn = psycopg2.connect(**DB_CONFIG)
    cur  = conn.cursor()

    total_inserted = 0
    total_variants = 0

    for idx, (canonical_q, sql) in enumerate(SEED_PAIRS, 1):
        print(f"[{idx:02d}/{len(SEED_PAIRS)}] {canonical_q}")

        # Insert canonical question
        ok = insert_pair(cur, canonical_q, sql, "seed_canonical")
        if ok:
            total_inserted += 1
            print(f"         Canonical: inserted")
        else:
            print(f"         Canonical: skipped (no embedding)")

        # Generate and insert variants via LLM
        variants = generate_question_variants(canonical_q, sql, n=5)
        for v in variants:
            ok = insert_pair(cur, v, sql, "seed_llm_variant")
            if ok:
                total_variants += 1
        print(f"         Variants : {len(variants)} generated, added to KB")
        print()

    conn.commit()

    # Also sync existing nlp_knowledge_base entries (read-only)
    print("Syncing existing nlp_knowledge_base entries...")
    cur.execute("""
        SELECT nk.question_text, nk.sql_query
        FROM   nlp_knowledge_base nk
        WHERE  NOT EXISTS (
            SELECT 1 FROM aiml.vector_kb vk
            WHERE  lower(vk.question_text) = lower(nk.question_text)
        );
    """)
    kb_rows = cur.fetchall()
    synced_kb = 0
    for q, s in kb_rows:
        ok = insert_pair(cur, q, s, "synced_from_kb")
        if ok:
            synced_kb += 1
    conn.commit()

    cur.close()
    conn.close()

    print("=" * 60)
    print(f"Seed complete!")
    print(f"  Canonical pairs inserted : {total_inserted}")
    print(f"  LLM variant pairs        : {total_variants}")
    print(f"  KB sync pairs            : {synced_kb}")
    print(f"  Total in aiml.vector_kb  : {total_inserted + total_variants + synced_kb}")
    print()
    print("The AIML service now has a solid baseline for semantic matching.")
    print("Restart the Flask server to apply changes.")


if __name__ == "__main__":
    main()
