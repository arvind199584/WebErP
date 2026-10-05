"""
superuser_migrate.py — Run ONCE as postgres superuser to:
  1. Add vec_embedding vector(384) column to nlp_knowledge_base
  2. Create an IVFFlat cosine index on it
  3. Drop the legacy text 'embedding' column (if present)
  4. Grant SELECT/UPDATE on the table to modpyphp_app

Usage:
    .venv_uv\Scripts\python.exe modules/AIML/Python/scripts/superuser_migrate.py [postgres_password]
"""
import sys
import psycopg2

pg_password = sys.argv[1] if len(sys.argv) > 1 else ""
EMBED_DIM = 384
APP_USER  = "modpyphp_app"

try:
    conn = psycopg2.connect(
        host="localhost", port=5432, dbname="modpyphp",
        user="postgres", password=pg_password
    )
    conn.autocommit = True
    cur = conn.cursor()

    print("Step 1: Adding vec_embedding column...")
    cur.execute(f"ALTER TABLE nlp_knowledge_base ADD COLUMN IF NOT EXISTS vec_embedding vector({EMBED_DIM});")
    print("  [OK]")

    print("Step 2: Creating IVFFlat cosine ANN index...")
    cur.execute("""
        CREATE INDEX IF NOT EXISTS nlp_kb_vec_idx
        ON nlp_knowledge_base
        USING ivfflat (vec_embedding vector_cosine_ops)
        WITH (lists = 10);
    """)
    print("  [OK]")

    print("Step 3: Checking for legacy text 'embedding' column...")
    cur.execute("""
        SELECT column_name FROM information_schema.columns
        WHERE table_name = 'nlp_knowledge_base'
          AND column_name = 'embedding'
          AND table_schema = 'public';
    """)
    if cur.fetchone():
        print("  Found legacy column, dropping...")
        cur.execute("ALTER TABLE nlp_knowledge_base DROP COLUMN IF EXISTS embedding;")
        print("  [OK] Legacy 'embedding' column removed.")
    else:
        print("  [SKIP] No legacy column found.")

    print("Step 4: Granting privileges to app user...")
    cur.execute(f"GRANT SELECT, INSERT, UPDATE, DELETE ON nlp_knowledge_base TO {APP_USER};")
    cur.close()
    conn.close()
    print("\nSUCCESS: Database migration complete.")
    print("Now run create_aiml_schema.py to back-fill embeddings.")
except Exception as exc:
    print(f"FAILED: {exc}")
    sys.exit(1)
