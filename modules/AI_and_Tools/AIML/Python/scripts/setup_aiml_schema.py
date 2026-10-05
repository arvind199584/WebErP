"""
setup_aiml_schema.py — One-time setup for the separate AIML vector store.

What this does:
  1. Enables the pgvector extension (superuser required).
  2. Creates the 'aiml' schema (isolated from all existing tables).
  3. Creates 'aiml.vector_kb' — a separate vector knowledge base.
     This table is INDEPENDENT of nlp_knowledge_base; existing tables
     are NEVER modified.
  4. Grants the app user full access to the new aiml schema.
  5. Syncs existing nlp_knowledge_base entries into aiml.vector_kb
     (READ-ONLY SELECT from the original table, no modifications).

Run from the project root:
    .venv_uv\Scripts\python.exe modules/AIML/Python/scripts/setup_aiml_schema.py [postgres_password]

This script is idempotent — safe to run multiple times.
"""

import sys
import os
import json
import urllib.request
from pathlib import Path
import psycopg2

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
pg_password = sys.argv[1] if len(sys.argv) > 1 else ""
EMBED_DIM   = 384
APP_USER    = "modpyphp_app"

# Load .env for DB credentials
env_path = Path(__file__).resolve().parents[4] / ".env"
if env_path.is_file():
    with open(env_path) as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, _, val = line.partition('=')
                os.environ.setdefault(key.strip(), val.strip())

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = os.getenv("DB_PORT", "5432")
DB_NAME = os.getenv("DB_NAME", "modpyphp")
DB_USER = os.getenv("DB_USER", APP_USER)
DB_PASS = os.getenv("DB_PASS", "")

OLLAMA_URL  = "http://localhost:11434"
EMBED_MODEL = "all-minilm"


def get_embedding(text: str) -> list:
    url  = f"{OLLAMA_URL}/api/embeddings"
    data = json.dumps({"model": EMBED_MODEL, "prompt": text}).encode("utf-8")
    req  = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read()).get("embedding", [])
    except Exception as exc:
        print(f"  [WARN] Embedding error: {exc}")
        return []


# ---------------------------------------------------------------------------
# Phase 1: Superuser DDL (extension, schema, table, index, grants)
# ---------------------------------------------------------------------------
print("=== Phase 1: Superuser setup ===")
try:
    su_conn = psycopg2.connect(
        host=DB_HOST, port=DB_PORT, dbname=DB_NAME,
        user="postgres", password=pg_password
    )
    su_conn.autocommit = True
    cur = su_conn.cursor()

    # Enable pgvector
    cur.execute("CREATE EXTENSION IF NOT EXISTS vector;")
    print("[OK] pgvector extension enabled.")

    # Create isolated aiml schema
    cur.execute("CREATE SCHEMA IF NOT EXISTS aiml;")
    print("[OK] Schema 'aiml' created (or already exists).")

    # Create vector KB table — INDEPENDENT of any existing tables
    cur.execute(f"""
        CREATE TABLE IF NOT EXISTS aiml.vector_kb (
            id            SERIAL PRIMARY KEY,
            question_text TEXT    NOT NULL UNIQUE,
            sql_query     TEXT    NOT NULL,
            source        TEXT    DEFAULT 'manual',
            vec_embedding vector({EMBED_DIM}),
            created_at    TIMESTAMPTZ DEFAULT NOW()
        );
    """)
    print("[OK] Table 'aiml.vector_kb' created (or already exists).")

    # IVFFlat index for fast cosine ANN search
    cur.execute("""
        CREATE INDEX IF NOT EXISTS aiml_vec_kb_idx
        ON aiml.vector_kb
        USING ivfflat (vec_embedding vector_cosine_ops)
        WITH (lists = 10);
    """)
    print("[OK] IVFFlat cosine index created (or already exists).")

    # Grant full access on the new schema to the app user
    cur.execute(f"GRANT USAGE ON SCHEMA aiml TO {APP_USER};")
    cur.execute(f"GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA aiml TO {APP_USER};")
    cur.execute(f"GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA aiml TO {APP_USER};")
    cur.execute(f"ALTER DEFAULT PRIVILEGES IN SCHEMA aiml GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO {APP_USER};")
    cur.execute(f"ALTER DEFAULT PRIVILEGES IN SCHEMA aiml GRANT USAGE, SELECT ON SEQUENCES TO {APP_USER};")
    print(f"[OK] Granted all privileges on 'aiml' schema to '{APP_USER}'.")

    cur.close()
    su_conn.close()
except Exception as exc:
    print(f"[FAIL] Superuser phase failed: {exc}")
    sys.exit(1)

# ---------------------------------------------------------------------------
# Phase 2: App-user sync (READ-ONLY SELECT from nlp_knowledge_base)
# ---------------------------------------------------------------------------
print("\n=== Phase 2: Syncing existing KB entries (read-only) ===")
try:
    app_conn = psycopg2.connect(
        host=DB_HOST, port=DB_PORT, dbname=DB_NAME,
        user=DB_USER, password=DB_PASS
    )
    app_conn.autocommit = False
    cur2 = app_conn.cursor()

    # Read existing nlp_knowledge_base entries (SELECT ONLY — no modifications)
    cur2.execute("""
        SELECT nk.question_text, nk.sql_query
        FROM   nlp_knowledge_base nk
        WHERE  NOT EXISTS (
            SELECT 1 FROM aiml.vector_kb vk
            WHERE  lower(vk.question_text) = lower(nk.question_text)
        );
    """)
    new_rows = cur2.fetchall()
    print(f"Found {len(new_rows)} nlp_knowledge_base entry(ies) not yet in aiml.vector_kb.")

    synced = 0
    for question_text, sql_query in new_rows:
        emb = get_embedding(question_text)
        if emb:
            cur2.execute("""
                INSERT INTO aiml.vector_kb (question_text, sql_query, source, vec_embedding)
                VALUES (%s, %s, 'synced_from_kb', %s)
                ON CONFLICT (question_text) DO NOTHING;
            """, (question_text.lower(), sql_query, str(emb)))
            synced += 1
            print(f"  Synced [{synced}/{len(new_rows)}]: '{question_text[:60]}'")
        else:
            print(f"  [SKIP] No embedding returned for: '{question_text[:60]}'")

    app_conn.commit()
    cur2.close()
    app_conn.close()
    print(f"\n[OK] Synced {synced}/{len(new_rows)} entries.")

except Exception as exc:
    print(f"[FAIL] Sync phase failed: {exc}")
    sys.exit(1)

print("\n=== Setup complete. ===")
print("Existing tables are UNCHANGED. The aiml schema is fully independent.")
print("Start the AIML Flask server: .venv_uv\\Scripts\\python.exe modules/AIML/Python/brain.py")
