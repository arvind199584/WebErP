import os
import sys
import json
import datetime
import psycopg2
from psycopg2.extras import RealDictCursor

NEON_DSN = os.getenv("NEON_DATABASE_URL") or os.getenv("DATABASE_URL")
if not NEON_DSN:
    env_neon_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), ".env.neon")
    if os.path.exists(env_neon_path):
        with open(env_neon_path, "r", encoding="utf-8") as f:
            for line in f:
                if line.startswith("DATABASE_URL="):
                    NEON_DSN = line.strip().split("=", 1)[1]
                    break
if not NEON_DSN:
    NEON_DSN = "postgresql://neondb_owner:npg_YoD4CLZ2TQpw@ep-withered-wave-axase6mw-pooler.c-4.us-east-2.aws.neon.tech/neondb?sslmode=require"

timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
dumps_dir = r"E:\Research\ModPyPhp\database\dumps"
os.makedirs(dumps_dir, exist_ok=True)

sql_filename = f"neon_cloud_backup_{timestamp}.sql"
sql_filepath = os.path.join(dumps_dir, sql_filename)
latest_sql = os.path.join(dumps_dir, "neon_cloud_backup_latest.sql")

json_filename = f"neon_cloud_backup_{timestamp}.json"
json_filepath = os.path.join(dumps_dir, json_filename)
latest_json = os.path.join(dumps_dir, "neon_cloud_backup_latest.json")

print(f"Connecting to Neon Cloud DB...")
conn = psycopg2.connect(NEON_DSN)
cur = conn.cursor(cursor_factory=RealDictCursor)

# 1. Fetch all tables
cur.execute("""
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    ORDER BY table_name;
""")
tables = [r['table_name'] for r in cur.fetchall()]
print(f"Discovered {len(tables)} tables to back up.")

# 2. Fetch all sequences
cur.execute("""
    SELECT sequence_name 
    FROM information_schema.sequences 
    WHERE sequence_schema = 'public'
    ORDER BY sequence_name;
""")
sequences = [r['sequence_name'] for r in cur.fetchall()]

backup_manifest = {
    "timestamp": timestamp,
    "source": "Neon Cloud PostgreSQL",
    "database": "neondb",
    "total_tables": len(tables),
    "tables_summary": {},
    "total_rows": 0
}

sql_lines = []
sql_lines.append(f"-- ============================================================")
sql_lines.append(f"-- Neon Cloud PostgreSQL Database Full Backup")
sql_lines.append(f"-- Generated At: {datetime.datetime.now().isoformat()}")
sql_lines.append(f"-- Total Tables: {len(tables)}")
sql_lines.append(f"-- ============================================================\n")
sql_lines.append("SET statement_timeout = 0;")
sql_lines.append("SET lock_timeout = 0;")
sql_lines.append("SET client_encoding = 'UTF8';")
sql_lines.append("SET standard_conforming_strings = on;")
sql_lines.append("SET check_function_bodies = false;\n")

json_data = {}

total_rows_all = 0

with open(sql_filepath, 'w', encoding='utf-8') as f_sql:
    f_sql.write("\n".join(sql_lines) + "\n")
    
    for tbl in tables:
        # Get column metadata
        cur.execute(f"""
            SELECT column_name, data_type, udt_name, is_nullable
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = %s
            ORDER BY ordinal_position;
        """, (tbl,))
        cols_meta = cur.fetchall()
        cols = [c['column_name'] for c in cols_meta]
        col_udts = {c['column_name']: c['udt_name'] for c in cols_meta}
        
        # Get row count
        cur.execute(f'SELECT count(*) as c FROM "{tbl}"')
        count = cur.fetchone()['c']
        total_rows_all += count
        backup_manifest["tables_summary"][tbl] = count
        
        f_sql.write(f"\n-- ------------------------------------------------------------\n")
        f_sql.write(f"-- Table: \"{tbl}\" ({count} rows)\n")
        f_sql.write(f"-- ------------------------------------------------------------\n")
        
        if count == 0:
            json_data[tbl] = []
            continue
            
        cur.execute(f'SELECT * FROM "{tbl}"')
        rows = cur.fetchall()
        
        # JSON export representation
        json_rows = []
        for r in rows:
            json_row = {}
            for k, v in r.items():
                if isinstance(v, (datetime.date, datetime.datetime, datetime.time)):
                    json_row[k] = v.isoformat()
                elif isinstance(v, bytes):
                    json_row[k] = v.hex()
                else:
                    json_row[k] = v
            json_rows.append(json_row)
        json_data[tbl] = json_rows
        
        quoted_cols = ', '.join([f'"{c}"' for c in cols])
        
        # Write INSERT batches
        batch_size = 100
        for i in range(0, len(rows), batch_size):
            batch = rows[i:i+batch_size]
            val_clauses = []
            for r in batch:
                row_vals = []
                for c in cols:
                    val = r[c]
                    if val is None:
                        row_vals.append("NULL")
                    elif isinstance(val, bool):
                        row_vals.append("TRUE" if val else "FALSE")
                    elif isinstance(val, (int, float)):
                        row_vals.append(str(val))
                    elif isinstance(val, (dict, list)):
                        escaped = json.dumps(val).replace("'", "''")
                        row_vals.append(f"'{escaped}'::jsonb")
                    elif isinstance(val, (datetime.date, datetime.datetime)):
                        row_vals.append(f"'{val.isoformat()}'")
                    else:
                        escaped = str(val).replace("'", "''")
                        row_vals.append(f"'{escaped}'")
                val_clauses.append(f"({', '.join(row_vals)})")
            
            f_sql.write(f'INSERT INTO "{tbl}" ({quoted_cols}) VALUES\n' + ',\n'.join(val_clauses) + ';\n')
        
        print(f"Backed up table '{tbl}': {count} rows")

    # Sequence setval restoration
    f_sql.write("\n-- ============================================================\n")
    f_sql.write("-- Reset Sequences\n")
    f_sql.write("-- ============================================================\n")
    for seq in sequences:
        # Check if table and column can be inferred
        try:
            cur.execute(f"SELECT last_value FROM \"{seq}\"")
            last_val = cur.fetchone()['last_value']
            f_sql.write(f"SELECT setval('\"{seq}\"', {last_val}, true);\n")
        except Exception as e:
            pass

backup_manifest["total_rows"] = total_rows_all

# Save latest copy of SQL
with open(latest_sql, 'w', encoding='utf-8') as f_latest:
    with open(sql_filepath, 'r', encoding='utf-8') as f_orig:
        f_latest.write(f_orig.read())

# Save JSON dump
with open(json_filepath, 'w', encoding='utf-8') as f_json:
    json.dump({"manifest": backup_manifest, "data": json_data}, f_json, indent=2, default=str)

# Save latest copy of JSON
with open(latest_json, 'w', encoding='utf-8') as f_latest_json:
    json.dump({"manifest": backup_manifest, "data": json_data}, f_latest_json, indent=2, default=str)

conn.close()

sql_size_mb = os.path.getsize(sql_filepath) / (1024 * 1024)
json_size_mb = os.path.getsize(json_filepath) / (1024 * 1024)

print("\n" + "="*60)
print("NEON CLOUD BACKUP COMPLETED SUCCESSFULLY!")
print(f"Total Tables Backed Up : {len(tables)}")
print(f"Total Rows Saved       : {total_rows_all}")
print(f"SQL Dump File          : {sql_filepath} ({sql_size_mb:.2f} MB)")
print(f"Latest SQL Pointer     : {latest_sql}")
print(f"JSON Dump File         : {json_filepath} ({json_size_mb:.2f} MB)")
print(f"Latest JSON Pointer    : {latest_json}")
print("="*60)
