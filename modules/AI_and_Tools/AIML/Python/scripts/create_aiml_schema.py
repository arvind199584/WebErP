"""
create_aiml_schema.py — One-time database migration script.

What it does:
  1. Enables the pgvector extension (requires PostgreSQL >= 13).
  2. Adds a `vec_embedding vector(384)` column to `nlp_knowledge_base`
     (all-minilm produces 384-dimensional vectors).
  3. Creates an IVFFlat ANN index on that column for fast cosine search.
  4. Back-fills embeddings for existing rows (calls Ollama all-minilm).

Run from the project root with the project's virtual environment:
    .venv_uv\\Scripts\\python.exe modules/AIML/Python/scripts/create_aiml_schema.py

This script is idempotent — safe to run multiple times.
"""

import os
import sys
import json
import urllib.request
from pathlib import Path
import psycopg2

# ---------------------------------------------------------------------------
# Load .env
# ---------------------------------------------------------------------------
env_path = Path(__file__).resolve().parents[4] / ".env"
if env_path.is_file():
    with open(env_path) as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                key, _, value = line.partition('=')
                os.environ.setdefault(key.strip(), value.strip())
else:
    print(".env not found — using OS environment variables.")

DB_HOST = os.getenv('DB_HOST', 'localhost')
DB_PORT = os.getenv('DB_PORT', '5432')
DB_NAME = os.getenv('DB_NAME', 'modpyphp')
DB_USER = os.getenv('DB_USER', 'postgres')
DB_PASS = os.getenv('DB_PASS', '')

OLLAMA_URL  = "http://localhost:11434"
EMBED_MODEL = "all-minilm"
EMBED_DIM   = 384

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def get_embedding(text: str) -> list:
    url  = f"{OLLAMA_URL}/api/embeddings"
    data = json.dumps({"model": EMBED_MODEL, "prompt": text}).encode("utf-8")
    req  = urllib.request.Request(url, data=data, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read()).get("embedding", [])
    except Exception as exc:
        print(f"  [warn] Embedding error for text snippet: {exc}")
        return []

# ---------------------------------------------------------------------------
# Main migration
# ---------------------------------------------------------------------------

conn = None
try:
    conn = psycopg2.connect(
        host=DB_HOST, port=DB_PORT, dbname=DB_NAME,
        user=DB_USER, password=DB_PASS,
    )
    conn.autocommit = True
    cur = conn.cursor()

    # 1. Enable pgvector
    print("Step 1: Enabling pgvector extension...")
    cur.execute("CREATE EXTENSION IF NOT EXISTS vector;")
    print("  [OK] pgvector extension ready.")

    # 2. Add vec_embedding column (idempotent)
    print(f"Step 2: Adding vec_embedding vector({EMBED_DIM}) column...")
    cur.execute(f"""
        ALTER TABLE nlp_knowledge_base
        ADD COLUMN IF NOT EXISTS vec_embedding vector({EMBED_DIM});
    """)
    print("  [OK] Column added (or already present.)")

    # 3. Create IVFFlat index for cosine ANN search
    print("Step 3: Creating IVFFlat cosine index...")
    cur.execute("""
        CREATE INDEX IF NOT EXISTS nlp_kb_vec_idx
        ON nlp_knowledge_base
        USING ivfflat (vec_embedding vector_cosine_ops)
        WITH (lists = 10);
    """)
    print("  [OK] Index created (or already present.)")

    # 4. Back-fill embeddings for existing rows
    cur.execute(
        "SELECT id, question_text FROM nlp_knowledge_base WHERE vec_embedding IS NULL;"
    )
    rows = cur.fetchall()
    if rows:
        print(f"Step 4: Back-filling embeddings for {len(rows)} existing KB row(s)…")
        filled = 0
        for row_id, question in rows:
            emb = get_embedding(question)
            if emb:
                cur.execute(
                    "UPDATE nlp_knowledge_base SET vec_embedding = %s WHERE id = %s;",
                    (str(emb), row_id)
                )
                filled += 1
                print(f"  [OK] [{filled}/{len(rows)}] '{question[:60]}'")
            else:
                print(f"  [SKIP] Skipped (no embedding): '{question[:60]}'")
        print(f"  Back-fill complete: {filled}/{len(rows)} rows updated.")
    else:
        print("Step 4: No rows need back-filling.")

    # 5. Drop old JSON-text embedding column if still present (optional cleanup)
    cur.execute("""
        SELECT column_name FROM information_schema.columns
        WHERE table_name = 'nlp_knowledge_base'
          AND column_name = 'embedding'
          AND table_schema = 'public';
    """)
    if cur.fetchone():
        print("Step 5: Dropping legacy text 'embedding' column...")
        cur.execute("ALTER TABLE nlp_knowledge_base DROP COLUMN IF EXISTS embedding;")
        print("  [OK] Legacy column removed.")

    cur.close()
    print("\nMigration complete. The AIML service now uses pgvector + Llama.")

except Exception as exc:
    print(f"\nMigration FAILED: {exc}")
    sys.exit(1)
finally:
    if conn:
        conn.close()
