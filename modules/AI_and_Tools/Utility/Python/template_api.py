from flask import Flask, request, jsonify
import os
import json
import re

app = Flask(__name__)

def generate_sql_from_schema(query, schema):
    """
    Advanced logic to map natural language to SQL using the database schema.
    """
    query = query.lower()
    
    # 1. Identify the Table
    target_table = None
    for table in schema.keys():
        if table.replace('_', ' ') in query or table in query:
            target_table = table
            break
    
    # Fallback table detection (common synonyms)
    if not target_table:
        if any(w in query for w in ['staff', 'person', 'worker']): target_table = 'employees'
        elif any(w in query for w in ['payment', 'invoice']): target_table = 'bills'
        elif any(w in query for w in ['contract', 'tender']): target_table = 'agreements'

    if not target_table:
        return None

    # 2. Identify Columns
    columns = schema[target_table]
    selected_cols = []
    
    # If "count" or "how many"
    if any(w in query for w in ['count', 'how many']):
        selected_cols = ["COUNT(*) as total"]
    # If "total" or "sum"
    elif any(w in query for w in ['total', 'sum']):
        # Find numeric columns
        numeric_cols = [c for c in columns if any(x in c for x in ['amount', 'cost', 'rate', 'due', 'drawn'])]
        if numeric_cols:
            selected_cols = [f"SUM({numeric_cols[0]}) as total"]
    else:
        # Default: Select name-like columns or all
        name_cols = [c for c in columns if any(x in c for x in ['name', 'no', 'code', 'title', 'head'])]
        selected_cols = name_cols if name_cols else ["*"]

    # 3. Build Query
    sql = f"SELECT {', '.join(selected_cols)} FROM {target_table}"
    
    # 4. Simple Filters (Where Clause)
    where_clauses = []
    
    # Status filters
    if 'active' in query: where_clauses.append("status = 'Active'")
    elif 'paid' in query: where_clauses.append("status = 'Paid'")
    elif 'draft' in query: where_clauses.append("status = 'Draft'")
    
    # Name filters (fuzzy)
    match_name = re.search(r'(?:of|for|is|name)\s+([a-z]+)', query)
    if match_name:
        name_val = match_name.group(1)
        # Find the best column to filter by name
        filter_col = next((c for c in columns if 'name' in c), columns[0])
        where_clauses.append(f"lower({filter_col}) LIKE '%{name_val}%'")

    if where_clauses:
        sql += " WHERE " + " AND ".join(where_clauses)

    return sql

@app.route('/nlp', methods=['POST'])
def nlp_query():
    try:
        data = request.get_json()
        query = data.get('query', '')
        schema = data.get('schema', {})
        
        # Try to generate SQL based on schema
        generated_sql = generate_sql_from_schema(query, schema)
        
        if generated_sql:
            return jsonify({
                'sql': generated_sql,
                'params': {},
                'type': 'table' if 'SUM' not in generated_sql and 'COUNT' not in generated_sql else 'text',
                'title': 'AI Generated Result',
                'predicted_intent': 'Schema-Based Generation'
            })
        
        return jsonify({'error': "I couldn't map your request to the database schema. Try being more specific about the table name."}), 400

    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='127.0.0.1', port=5000, debug=False)
