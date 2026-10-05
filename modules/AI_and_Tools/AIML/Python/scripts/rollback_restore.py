"""
rollback_restore.py — Restores the 'embedding' TEXT column that was incorrectly dropped.
Run once as superuser.
"""
import sys
import psycopg2

pg_password = sys.argv[1] if len(sys.argv) > 1 else ""

conn = psycopg2.connect(
    host="localhost", port=5432, dbname="modpyphp",
    user="postgres", password=pg_password
)
conn.autocommit = True
cur = conn.cursor()

# Check if already exists
cur.execute("""
    SELECT column_name FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name   = 'nlp_knowledge_base'
      AND column_name  = 'embedding';
""")
if cur.fetchone():
    print("'embedding' column already exists - nothing to restore.")
else:
    cur.execute("ALTER TABLE nlp_knowledge_base ADD COLUMN embedding TEXT;")
    print("Restored 'embedding' TEXT column on nlp_knowledge_base.")

# Also drop the vec_embedding column we added (return table to original state)
cur.execute("""
    SELECT column_name FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name   = 'nlp_knowledge_base'
      AND column_name  = 'vec_embedding';
""")
if cur.fetchone():
    cur.execute("ALTER TABLE nlp_knowledge_base DROP COLUMN vec_embedding;")
    print("Removed the vec_embedding column (returning table to original state).")

# Drop the IVFFlat index we created (if it exists)
cur.execute("""
    SELECT indexname FROM pg_indexes
    WHERE tablename = 'nlp_knowledge_base'
      AND indexname = 'nlp_kb_vec_idx';
""")
if cur.fetchone():
    cur.execute("DROP INDEX IF EXISTS nlp_kb_vec_idx;")
    print("Dropped nlp_kb_vec_idx index.")

cur.close()
conn.close()
print("Rollback complete. nlp_knowledge_base is back to its original state.")
