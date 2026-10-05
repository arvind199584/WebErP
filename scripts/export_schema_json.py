import os
import json
import psycopg2
from psycopg2.extras import RealDictCursor

def get_db_connection():
    return psycopg2.connect(
        host=os.getenv("DB_HOST", "localhost"),
        port=int(os.getenv("DB_PORT", "5432")),
        database=os.getenv("DB_NAME", "modpyphp"),
        user=os.getenv("DB_USER", "postgres"),
        password=os.getenv("DB_PASS", "daredevil")
    )

def extract_schema():
    conn = get_db_connection()
    cur = conn.cursor(cursor_factory=RealDictCursor)
    
    schema = {
        "database": "modpyphp",
        "tables": [],
        "views": [],
        "functions": [],
        "enums_and_types": []
    }
    
    # 1. Fetch user-defined types (enums, range types, etc.)
    type_query = """
    SELECT 
        t.typname AS type_name,
        CASE 
            WHEN t.typtype = 'e' THEN 'enum'
            WHEN t.typtype = 'r' THEN 'range'
            WHEN t.typtype = 'b' THEN 'base'
            WHEN t.typtype = 'd' THEN 'domain'
            ELSE 'other'
        END AS type_category,
        ARRAY_TO_STRING(ARRAY(
            SELECT enumlabel 
            FROM pg_enum 
            WHERE enumtypid = t.oid 
            ORDER BY enumsortorder
        ), ', ') AS enum_values
    FROM pg_type t
    JOIN pg_namespace n ON t.typnamespace = n.oid
    WHERE n.nspname = 'public' 
      AND (t.typtype = 'e' OR t.typname = 'daterange' OR t.typname = 'vector');
    """
    cur.execute(type_query)
    schema["enums_and_types"] = [dict(row) for row in cur.fetchall()]

    # 2. Fetch tables
    cur.execute("""
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
        ORDER BY table_name;
    """)
    tables = [row["table_name"] for row in cur.fetchall()]
    
    for table_name in tables:
        table_info = {
            "table_name": table_name,
            "description": "",
            "columns": [],
            "primary_key": [],
            "foreign_keys": [],
            "constraints": [],
            "indexes": []
        }
        
        # Get table description
        cur.execute("""
            SELECT obj_description(c.oid, 'pg_class') AS description
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            WHERE n.nspname = 'public' AND c.relname = %s AND c.relkind = 'r';
        """, (table_name,))
        desc_row = cur.fetchone()
        if desc_row and desc_row["description"]:
            table_info["description"] = desc_row["description"]
            
        # Get primary keys
        cur.execute("""
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu 
              ON tc.constraint_name = kcu.constraint_name 
              AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = 'PRIMARY KEY' 
              AND tc.table_schema = 'public' 
              AND tc.table_name = %s;
        """, (table_name,))
        table_info["primary_key"] = [row["column_name"] for row in cur.fetchall()]
        
        # Get column details
        cur.execute("""
            SELECT 
                column_name, 
                data_type, 
                is_nullable, 
                column_default, 
                udt_name
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = %s
            ORDER BY ordinal_position;
        """, (table_name,))
        columns = cur.fetchall()
        
        # Get column comments
        cur.execute("""
            SELECT 
                a.attname AS column_name,
                col_description(a.attrelid, a.attnum) AS description
            FROM pg_class c
            JOIN pg_namespace n ON n.oid = c.relnamespace
            JOIN pg_attribute a ON a.attrelid = c.oid
            WHERE n.nspname = 'public' AND c.relname = %s AND a.attnum > 0 AND NOT a.attisdropped;
        """, (table_name,))
        col_comments = {row["column_name"]: row["description"] for row in cur.fetchall() if row["description"]}
        
        for col in columns:
            col_name = col["column_name"]
            table_info["columns"].append({
                "name": col_name,
                "type": col["data_type"] if col["data_type"] != "USER-DEFINED" else col["udt_name"],
                "nullable": col["is_nullable"] == "YES",
                "default": col["column_default"],
                "is_primary": col_name in table_info["primary_key"],
                "description": col_comments.get(col_name, "")
            })
            
        # Get foreign keys
        cur.execute("""
            SELECT
                kcu.column_name AS source_column,
                ccu.table_name AS target_table,
                ccu.column_name AS target_column,
                tc.constraint_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage ccu
              ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
              AND tc.table_schema = 'public'
              AND tc.table_name = %s;
        """, (table_name,))
        table_info["foreign_keys"] = [dict(row) for row in cur.fetchall()]
        
        # Get check constraints
        cur.execute("""
            SELECT
                cc.constraint_name,
                cc.check_clause
            FROM information_schema.table_constraints tc
            JOIN information_schema.check_constraints cc
              ON tc.constraint_name = cc.constraint_name
            WHERE tc.table_schema = 'public'
              AND tc.table_name = %s
              AND tc.constraint_type = 'CHECK';
        """, (table_name,))
        table_info["constraints"] = [dict(row) for row in cur.fetchall()]
        
        # Get indexes
        cur.execute("""
            SELECT
                i.relname AS index_name,
                a.attname AS column_name,
                ix.indisunique AS is_unique
            FROM pg_class t
            JOIN pg_index ix ON t.oid = ix.indrelid
            JOIN pg_class i ON i.oid = ix.indexrelid
            JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = ANY(ix.indkey)
            WHERE t.relname = %s AND t.relkind = 'r';
        """, (table_name,))
        indexes_raw = cur.fetchall()
        # Group columns by index_name
        grouped_indexes = {}
        for idx in indexes_raw:
            idx_name = idx["index_name"]
            if idx_name not in grouped_indexes:
                grouped_indexes[idx_name] = {
                    "index_name": idx_name,
                    "columns": [],
                    "unique": idx["is_unique"]
                }
            grouped_indexes[idx_name]["columns"].append(idx["column_name"])
        table_info["indexes"] = list(grouped_indexes.values())
        
        schema["tables"].append(table_info)
        
    # 3. Fetch views
    cur.execute("""
        SELECT table_name AS view_name, view_definition
        FROM information_schema.views
        WHERE table_schema = 'public'
        ORDER BY view_name;
    """)
    views = cur.fetchall()
    for view in views:
        view_info = {
            "view_name": view["view_name"],
            "definition": view["view_definition"].strip() if view["view_definition"] else "",
            "columns": []
        }
        # Get columns for view
        cur.execute("""
            SELECT column_name, data_type, udt_name
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = %s
            ORDER BY ordinal_position;
        """, (view["view_name"],))
        for col in cur.fetchall():
            view_info["columns"].append({
                "name": col["column_name"],
                "type": col["data_type"] if col["data_type"] != "USER-DEFINED" else col["udt_name"]
            })
        schema["views"].append(view_info)
        
    # 4. Fetch user-defined functions
    # Ignore aggregates, window functions, and helper functions from pgvector/tablefunc extensions
    cur.execute("""
        SELECT 
            p.proname AS function_name,
            pg_get_function_arguments(p.oid) AS arguments,
            pg_get_functiondef(p.oid) AS definition,
            t.typname AS return_type
        FROM pg_proc p
        JOIN pg_namespace n ON p.pronamespace = n.oid
        JOIN pg_type t ON p.prorettype = t.oid
        WHERE n.nspname = 'public'
          AND p.prokind IN ('f', 'p')
          AND p.proname NOT IN (
              'uuid_generate_v1', 'uuid_generate_v1mc', 'uuid_generate_v3', 
              'uuid_generate_v4', 'uuid_generate_v5', 'uuid_nil', 
              'uuid_ns_dns', 'uuid_ns_url', 'uuid_ns_oid', 'uuid_ns_x500',
              'normal_rand', 'crosstab2', 'crosstab3', 'crosstab4',
              'ivfflathandler', 'hnswhandler', 'ivfflat_halfvec_support',
              'ivfflat_bit_support', 'hnsw_halfvec_support', 'hnsw_bit_support',
              'hnsw_sparsevec_support'
          )
          AND p.proname NOT LIKE '%vector%'
          AND p.proname NOT LIKE '%halfvec%'
          AND p.proname NOT LIKE '%sparsevec%'
          AND p.proname NOT LIKE 'crosstab%'
          AND p.proname NOT LIKE 'connectby%'
        ORDER BY function_name;
    """)
    funcs = cur.fetchall()
    for func in funcs:
        schema["functions"].append({
            "function_name": func["function_name"],
            "arguments": func["arguments"],
            "return_type": func["return_type"],
            "definition": func["definition"].strip() if func["definition"] else ""
        })
        
    cur.close()
    conn.close()
    
    # Write to JSON file
    output_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "schema", "modpyphp_schema.json"))
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(schema, f, indent=2, ensure_ascii=False)
        
    print(f"Schema exported successfully to {output_path}")

if __name__ == "__main__":
    # Load .env if present
    env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".env"))
    if os.path.exists(env_path):
        with open(env_path) as f:
            for line in f:
                if "=" in line and not line.strip().startswith("#"):
                    k, v = line.strip().split("=", 1)
                    os.environ[k] = v
    extract_schema()
