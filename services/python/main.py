import os
import calendar
from dotenv import load_dotenv
from flask import Flask, request, jsonify
try:
    import psycopg2
    from psycopg2.extras import RealDictCursor
    USE_PSYCOPG2 = True
except Exception:
    import pg8000.dbapi
    USE_PSYCOPG2 = False

import pandas as pd

# Load shared .env from project root
load_dotenv(os.path.join(os.path.dirname(__file__), '../../.env'))

import urllib.parse

# --- Database Configuration (reads from .env or DATABASE_URL) ---
DB_CONFIG = {
    "host":     os.getenv("DB_HOST",    "localhost"),
    "port":     int(os.getenv("DB_PORT", 5432)),
    "user":     os.getenv("DB_USER",    "postgres"),
    "password": os.getenv("DB_PASS",    "daredevil"),
    "dbname":   os.getenv("DB_NAME",    "modpyphp"),
    "sslmode":  os.getenv("DB_SSLMODE", "disable"),
}

# Support standard cloud DATABASE_URL (Render, Neon, Supabase)
db_url = os.getenv("DATABASE_URL")
if db_url:
    try:
        parsed_url = urllib.parse.urlparse(db_url)
        if parsed_url.hostname:
            DB_CONFIG["host"] = parsed_url.hostname
        if parsed_url.port:
            DB_CONFIG["port"] = int(parsed_url.port)
        if parsed_url.username:
            DB_CONFIG["user"] = parsed_url.username
        if parsed_url.password:
            DB_CONFIG["password"] = parsed_url.password
        if parsed_url.path and parsed_url.path.strip('/'):
            DB_CONFIG["dbname"] = parsed_url.path.strip('/')
        if parsed_url.query:
            q = urllib.parse.parse_qs(parsed_url.query)
            if 'sslmode' in q:
                DB_CONFIG["sslmode"] = q['sslmode'][0]
    except Exception as e:
        print(f"[warning] Failed to parse DATABASE_URL in main.py: {e}")

app = Flask(__name__)

def get_db_connection():
    if USE_PSYCOPG2:
        return psycopg2.connect(**DB_CONFIG)
    else:
        import ssl
        ssl_ctx = ssl.create_default_context() if DB_CONFIG.get("sslmode") != "disable" else None
        return pg8000.dbapi.connect(
            user=DB_CONFIG["user"],
            password=DB_CONFIG["password"],
            host=DB_CONFIG["host"],
            port=DB_CONFIG["port"],
            database=DB_CONFIG["dbname"],
            ssl_context=ssl_ctx
        )

def get_db_cursor(conn):
    if USE_PSYCOPG2:
        return conn.cursor(cursor_factory=RealDictCursor)
    else:
        return conn.cursor(pg8000.dbapi.dict_row)

@app.route('/get_attendance_grid', methods=['GET'])
def get_attendance_grid():
    agreement_id = request.args.get('agreement_id', type=int)
    month_str = request.args.get('month') # YYYY-MM

    if not agreement_id or not month_str:
        return jsonify({"error": "agreement_id and month are required"}), 400

    year, month = map(int, month_str.split('-'))
    _, num_days = calendar.monthrange(year, month)
    
    conn = get_db_connection()
    cursor = get_db_cursor(conn)

    try:
        # 1. Fetch Employees for the agreement
        cursor.execute("""
            SELECT id, full_name, designation 
            FROM employees 
            WHERE agreement_id = %s 
            ORDER BY full_name
        """, (agreement_id,))
        
        employees = cursor.fetchall()
        if not employees:
            return jsonify({"error": "No employees found for this agreement"}), 404

        # 2. Fetch Attendance Records for the month
        start_date = f"{month_str}-01"
        end_date = f"{month_str}-{num_days}"
        
        cursor.execute("""
            SELECT employee_id, attendance_date, status 
            FROM attendance_records 
            WHERE employee_id IN (SELECT id FROM employees WHERE agreement_id = %s)
              AND attendance_date BETWEEN %s AND %s
        """, (agreement_id, start_date, end_date))
        
        records = cursor.fetchall()

        # 3. Use Pandas to Pivot
        df_records = pd.DataFrame(records)
        
        # Create a complete grid of all employees and all days in the month
        employee_ids = [emp['id'] for emp in employees]
        date_range = pd.to_datetime(pd.date_range(start=start_date, end=end_date))
        
        # Create a multi-index DataFrame to ensure all cells are present
        multi_index = pd.MultiIndex.from_product([employee_ids, date_range], names=['employee_id', 'attendance_date'])
        df_full_grid = pd.DataFrame(index=multi_index).reset_index()

        # Merge the actual records
        if not df_records.empty:
            df_records['attendance_date'] = pd.to_datetime(df_records['attendance_date'])
            df_full_grid = pd.merge(df_full_grid, df_records, on=['employee_id', 'attendance_date'], how='left')
        else:
            df_full_grid['status'] = None

        # Pivot the table
        pivot_df = df_full_grid.pivot(index='employee_id', columns='attendance_date', values='status')
        
        # Add day number as column headers
        pivot_df.columns = [d.day for d in pivot_df.columns]
        
        # Convert NaN to a default value (e.g., 'A' for Absent)
        pivot_df = pivot_df.fillna('A')

        # 4. Combine with employee details and calculate summary
        final_data = []
        for emp in employees:
            emp_id = emp['id']
            row = {
                "employee_id": emp_id,
                "full_name": emp['full_name'],
                "designation": emp['designation'],
                "days": {}
            }
            
            summary = {'P': 0, 'A': 0, 'R': 0, 'L': 0}
            
            for day in range(1, num_days + 1):
                status = pivot_df.at[emp_id, day] if emp_id in pivot_df.index and day in pivot_df.columns else 'A'
                row['days'][day] = status
                summary[status] += 1
            
            row['summary'] = summary
            final_data.append(row)

        return jsonify(final_data)

    finally:
        cursor.close()
        conn.close()

if __name__ == '__main__':
    # Run on an internal microservice port (default 5001)
    host = os.getenv('FLASK_HOST', '0.0.0.0')
    port = int(os.getenv('PYTHON_ATTENDANCE_PORT', 5001))
    app.run(host=host, port=port, debug=False)
