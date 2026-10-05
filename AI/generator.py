import os
import json

def format_schema(schema_path):
    if not os.path.exists(schema_path):
        raise FileNotFoundError(f"Schema file not found at {schema_path}")
        
    with open(schema_path, "r", encoding="utf-8") as f:
        schema = json.load(f)
        
    output = []
    
    # Custom types
    if "enums_and_types" in schema and schema["enums_and_types"]:
        output.append("-- CUSTOM TYPES & ENUMS:")
        for t in schema["enums_and_types"]:
            if t["type_category"] == "enum":
                output.append(f"CREATE TYPE {t['type_name']} AS ENUM ({t['enum_values']});")
            else:
                output.append(f"-- Type: {t['type_name']} ({t['type_category']})")
        output.append("")
        
    # Tables
    output.append("-- TABLES:")
    for table in schema["tables"]:
        t_name = table["table_name"]
        desc = table.get("description", "")
        desc_comment = f" -- {desc}" if desc else ""
        
        table_str = f"CREATE TABLE {t_name} ({desc_comment}\n"
        col_definitions = []
        
        # Primary key list
        pk_cols = table.get("primary_key", [])
        
        # Column detail maps
        for col in table["columns"]:
            c_name = col["name"]
            c_type = col["type"]
            nullable_str = " NOT NULL" if not col["nullable"] else ""
            default_str = f" DEFAULT {col['default']}" if col["default"] is not None else ""
            pk_str = " PRIMARY KEY" if (len(pk_cols) == 1 and c_name in pk_cols) else ""
            col_comment = f" -- {col['description']}" if col.get("description") else ""
            
            col_definitions.append(f"  {c_name} {c_type}{nullable_str}{default_str}{pk_str},{col_comment}")
            
        # Composite primary key (if any)
        if len(pk_cols) > 1:
            col_definitions.append(f"  PRIMARY KEY ({', '.join(pk_cols)})")
            
        # Foreign keys
        for fk in table.get("foreign_keys", []):
            col_definitions.append(f"  FOREIGN KEY ({fk['source_column']}) REFERENCES {fk['target_table']}({fk['target_column']})")
            
        table_str += ",\n".join(col_definitions)
        table_str += "\n);"
        output.append(table_str)
        output.append("")
        
    # Views
    if "views" in schema and schema["views"]:
        output.append("-- VIEWS:")
        for view in schema["views"]:
            v_name = view["view_name"]
            cols = [f"{c['name']} ({c['type']})" for c in view["columns"]]
            output.append(f"-- VIEW {v_name} with columns: {', '.join(cols)}")
        output.append("")
        
    return "\n".join(output)

def get_kb_messages():
    # Load .env if present
    env_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".env"))
    db_config = {"host": "localhost", "port": 5432, "database": "modpyphp", "user": "postgres", "password": "daredevil"}
    if os.path.exists(env_path):
        with open(env_path) as f:
            for line in f:
                if "=" in line and not line.strip().startswith("#"):
                    k, v = line.strip().split("=", 1)
                    k_map = {
                        "DB_HOST": "host",
                        "DB_PORT": "port",
                        "DB_NAME": "database",
                        "DB_USER": "user",
                        "DB_PASS": "password"
                    }
                    if k in k_map:
                        db_config[k_map[k]] = v.strip()
    
    messages = []
    try:
        import psycopg2
        conn = psycopg2.connect(**db_config)
        cur = conn.cursor()
        cur.execute("SELECT question_text, sql_query FROM nlp_knowledge_base ORDER BY id;")
        rows = cur.fetchall()
        for q, sql in rows:
            q_escaped = q.replace('"', '\\"').replace('\n', ' ')
            sql_escaped = sql.replace('"', '\\"').replace('\n', ' ')
            messages.append(f'MESSAGE user "{q_escaped}"')
            messages.append(f'MESSAGE assistant "{sql_escaped}[/SQL]"')
        cur.close()
        conn.close()
        print(f"Loaded {len(rows)} training examples from database nlp_knowledge_base.")
    except Exception as e:
        print(f"Warning: Could not load training examples from database: {e}")
        
    return "\n".join(messages)

def generate_modelfile():
    schema_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "schema", "modpyphp_schema.json"))
    schema_text = format_schema(schema_path)
    
    kb_messages = get_kb_messages()
    
    # Modelfile template
    modelfile_content = f"""FROM gemma2:9b

# Set model parameters
PARAMETER temperature 0.0
PARAMETER stop "[/SQL]"

# System prompt defining the SQL translator
SYSTEM \"\"\"You are a specialized PostgreSQL assistant for the ModPyPhp office management system.
Your job is to translate Hinglish prompts (a blend of Hindi and English written in Latin alphabet) into correct and optimized PostgreSQL queries.

---
DATABASE SCHEMA DDL:
{schema_text}
---

RULES:
1. Output ONLY the raw SQL query. Do not wrap it in markdown code blocks like ```sql ... ```.
2. The user will ask for CRUD operations (SELECT, INSERT, UPDATE, DELETE). Ensure you build valid SQL for these actions.
3. For text searches, use 'ILIKE' rather than '=' to ensure case-insensitive matching where appropriate (e.g. employee names, designations, remarks).
4. Referential Integrity/Audit Rule: ModPyPhp enforces strict auditing. Deleted records must preserve history. If a user asks to \"delete\" or \"remove\" an employee, check if they have attendance or wages. If so, do not use DELETE. Instead, set their 'leaving_date' to the current date or end date. If deleting a record with no dependencies, use DELETE but verify constraints.
5. Pay close attention to Foreign Key relationships and match column types when writing JOIN clauses.
6. When referencing date ranges (daterange type, like 'agreements.period'), use Postgres range operators:
   - Contains: 'period @> CURRENT_DATE'
   - Overlaps: 'period && daterange(start, end)'
7. Use function helper calls if they exist in the schema:
   - Gross Wage Calculation: 'calculate_gross_wage(employee_id, start_date, end_date)'
   - Base Wage Rate check: 'get_effective_rate(check_date, item_id)'
   - Employee wages creation: 'SELECT generate_employee_wages(employee_id, office_id, month_str)'
8. Always terminate the SQL statement with a semicolon ';'.
9. Output nothing else. No comments, no explanations. Write the query, then output the stop token [/SQL].
\"\"\"

# Hinglish few-shot examples for training/in-context learning (Static Seed Data)
MESSAGE user "Ramesh driver ko system me add karo jo aaj se SBI bank account 12345 IFSC SBIN0001234 ke sath join kar raha hai aur agreement id 2 hai and office id 1 hai"
MESSAGE assistant "INSERT INTO employees (full_name, designation, bank_name, account_no, ifsc, joining_date, agreement_id, officeid) VALUES ('Ramesh', 'driver', 'SBI', '12345', 'SBIN0001234', CURRENT_DATE, 2, 1);[/SQL]"

MESSAGE user "Suresh Kumar ki designation verify karo"
MESSAGE assistant "SELECT designation FROM employees WHERE full_name ILIKE '%Suresh Kumar%';[/SQL]"

MESSAGE user "Amit Sharma ka bank account badal ke 9876543210 kardo"
MESSAGE assistant "UPDATE employees SET account_no = '9876543210' WHERE full_name ILIKE '%Amit Sharma%';[/SQL]"

MESSAGE user "Agreement number AGR-2026-009 ka period range check karo"
MESSAGE assistant "SELECT period FROM agreements WHERE agreement_no = 'AGR-2026-009';[/SQL]"

MESSAGE user "Employee Rohan ko delete kardo system se"
MESSAGE assistant "UPDATE employees SET leaving_date = CURRENT_DATE WHERE full_name ILIKE '%Rohan%';[/SQL]"

MESSAGE user "Designation 'driver' ke liye latest base rate kya hai authority 'DDA' ke rules se?"
MESSAGE assistant "SELECT get_effective_rate(CURRENT_DATE, id) FROM wage_items WHERE item_name ILIKE '%driver%' AND authority = 'DDA'::wage_authority;[/SQL]"

MESSAGE user "Karan Malhotra ki attendance record dekho is mahine ki"
MESSAGE assistant "SELECT * FROM attendance_records WHERE employee_id = (SELECT id FROM employees WHERE full_name ILIKE '%Karan Malhotra%' LIMIT 1) AND DATE_TRUNC('month', attendance_date) = DATE_TRUNC('month', CURRENT_DATE);[/SQL]"

MESSAGE user "mower category me ek naya item register karo: code 'MWR-SP-02', name 'Mower Blade XL', quantity 10, unit 'Pcs'"
MESSAGE assistant "INSERT INTO turf_inventory_items (category_id, item_code, item_name, quantity, unit) VALUES ((SELECT id FROM turf_inventory_categories WHERE category_name ILIKE '%mower%' LIMIT 1), 'MWR-SP-02', 'Mower Blade XL', 10, 'Pcs');[/SQL]"

# Dynamically Loaded Superuser Corrections (Progressive Training)
{kb_messages}
"""
    
    # Save Modelfile
    modelfile_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "Modelfile"))
    with open(modelfile_path, "w", encoding="utf-8") as f:
        f.write(modelfile_content)
        
    print(f"Ollama Modelfile successfully generated at {modelfile_path}")

if __name__ == "__main__":
    generate_modelfile()
