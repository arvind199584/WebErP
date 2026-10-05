"""
superuser_setup.py — Run ONCE as a superuser to enable the pgvector extension.
This only needs the 'postgres' superuser role; no password if using trust/peer auth.

Usage:
    .venv_uv\Scripts\python.exe modules/AIML/Python/scripts/superuser_setup.py [postgres_password]
"""
import sys
import psycopg2

pg_password = sys.argv[1] if len(sys.argv) > 1 else ""

try:
    conn = psycopg2.connect(
        host="localhost", port=5432, dbname="modpyphp",
        user="postgres", password=pg_password
    )
    conn.autocommit = True
    cur = conn.cursor()
    cur.execute("CREATE EXTENSION IF NOT EXISTS vector;")
    # Grant usage of extension objects to the app user
    cur.execute("GRANT USAGE ON SCHEMA public TO modpyphp_app;")
    cur.close()
    conn.close()
    print("SUCCESS: pgvector extension enabled. Now run create_aiml_schema.py")
except Exception as exc:
    print(f"FAILED: {exc}")
    sys.exit(1)
