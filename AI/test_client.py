import os
import json
import urllib.request
import psycopg2

OLLAMA_URL = "http://localhost:11434"
MODEL_NAME = "hinglish-crud-llama"

def ask_custom_model(prompt: str) -> str:
    url = f"{OLLAMA_URL}/api/chat"
    payload = {
        "model": MODEL_NAME,
        "messages": [
            {"role": "user", "content": prompt}
        ],
        "stream": False,
        "options": {
            "temperature": 0.0
        }
    }
    
    try:
        data = json.dumps(payload).encode("utf-8")
        req = urllib.request.Request(
            url, data=data,
            headers={"Content-Type": "application/json"}
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            res_json = json.loads(resp.read().decode("utf-8"))
            content = res_json.get("message", {}).get("content", "").strip()
            # Remove any trailing stop tokens or markdown formatting
            if "[/SQL]" in content:
                content = content.split("[/SQL]")[0].strip()
            content = content.replace("```sql", "").replace("```", "").strip()
            return content
    except Exception as e:
        return f"Error communicating with Ollama: {e}"

def execute_on_db(sql: str):
    # Load connection credentials from .env
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

    try:
        conn = psycopg2.connect(**db_config)
        cur = conn.cursor()
        
        # Determine query type
        sql_upper = sql.strip().upper()
        
        if sql_upper.startswith("SELECT"):
            print(f"\nExecuting Read Query: {sql}")
            cur.execute(sql)
            rows = cur.fetchall()
            cols = [desc[0] for desc in cur.description]
            print(f"Results ({len(rows)} rows):")
            # Print columns
            print(" | ".join(cols))
            print("-" * 50)
            for row in rows[:10]:  # Limit print to 10 rows
                print(" | ".join(str(val) for val in row))
            if len(rows) > 10:
                print("... (truncated)")
        else:
            # For INSERT, UPDATE, DELETE, we ask user confirmation for safety before committing
            print(f"\n[DANGEROUS ACTION] SQL modifies the database: {sql}")
            confirm = input("Do you want to execute and commit this mutation on your database? (yes/no): ").strip().lower()
            if confirm == "yes":
                cur.execute(sql)
                conn.commit()
                print(f"Executed successfully! Rows affected: {cur.rowcount}")
            else:
                print("Execution aborted by user.")
                
        cur.close()
        conn.close()
    except Exception as e:
        print(f"Database Execution Error: {e}")

def main():
    print("=" * 60)
    print("ModPyPhp Hinglish-to-SQL Custom LLM Tester")
    print(f"Model: {MODEL_NAME}")
    print("Type 'exit' or 'quit' to close.")
    print("=" * 60)
    
    while True:
        try:
            prompt = input("\nHinglish Prompt > ").strip()
            if not prompt:
                continue
            if prompt.lower() in ("exit", "quit"):
                break
                
            print("Translating prompt to PostgreSQL...")
            sql = ask_custom_model(prompt)
            print("-" * 50)
            print("Generated SQL:")
            print(sql)
            print("-" * 50)
            
            if sql.startswith("Error"):
                continue
                
            run_db = input("Do you want to run this SQL query on your local database? (y/n): ").strip().lower()
            if run_db == 'y':
                execute_on_db(sql)
                
        except KeyboardInterrupt:
            print("\nExiting...")
            break
        except Exception as e:
            print(f"Error: {e}")

if __name__ == "__main__":
    main()
