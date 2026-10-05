import sys
import json
import re
import os
from datetime import datetime, timedelta
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import make_pipeline

def parse_date(text):
    text = text.lower()
    today = datetime.now()
    match = re.search(r'\b(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+(\d{4}|\d{2})\b', text)
    if match:
        month_str = match.group(1)
        year_str = match.group(2)
        if len(year_str) == 2: year_str = "20" + year_str
        try:
            dt = datetime.strptime(f"{month_str} {year_str}", "%b %Y")
            return {'condition': "AND to_char(transaction_date, 'YYYY-MM') = :date_param", 'params': {'date_param': dt.strftime('%Y-%m')}}
        except: pass
    return None

def train_model():
    data_path = os.path.join(os.path.dirname(__file__), 'training_data.json')
    if not os.path.exists(data_path):
        return None
        
    with open(data_path, 'r') as f:
        training_data = json.load(f)
    
    # Lowercase everything for training
    texts = [item['text'].lower() for item in training_data]
    labels = [item['intent'] for item in training_data]
    
    model = make_pipeline(TfidfVectorizer(), MultinomialNB())
    model.fit(texts, labels)
    return model

def main():
    try:
        if len(sys.argv) < 2:
            print(json.dumps({'error': 'No query provided'}))
            return
        
        query = sys.argv[1]
        query_lower = query.lower()
        
        # 1. Hardcoded Keyword Backup (High Priority)
        # This ensures "list all offices" always works regardless of ML training
        if 'office' in query_lower and any(w in query_lower for w in ['list', 'show', 'all']):
            intent = 'list_offices'
        elif 'user' in query_lower and any(w in query_lower for w in ['list', 'show', 'all']):
            intent = 'list_users'
        else:
            # 2. ML Prediction (Fallback)
            model = train_model()
            intent = model.predict([query_lower])[0] if model else 'unknown'
        
        response = {'sql': '', 'params': {}, 'type': 'table', 'title': 'Result', 'predicted_intent': intent}

        if intent == 'get_wages':
            response['title'] = 'Wage Details'
            response['type'] = 'text'
            match_name = re.search(r'(?:of|for|is)\s+([a-z]+)', query_lower)
            name = match_name.group(1).strip() if match_name else "hemant"
            sql = "SELECT SUM(wl.cr_amount) as total FROM wages_ledger wl JOIN employees e ON wl.employee_id = e.id WHERE lower(e.full_name) LIKE :name"
            response['params']['name'] = f"%{name}%"
            date_filter = parse_date(query_lower)
            if date_filter:
                sql += " " + date_filter['condition']
                response['params'].update(date_filter['params'])
            response['sql'] = sql

        elif intent == 'list_employees':
            response['title'] = 'Employees Found'
            sql = "SELECT full_name as name, designation, is_reliever FROM employees WHERE 1=1"
            common_desigs = ['computer operator', 'data entry operator', 'security guard', 'receptionist', 'supervisor', 'clerk', 'peon']
            for d in common_desigs:
                if d in query_lower:
                    sql += " AND lower(designation) LIKE :desig"
                    response['params']['desig'] = f"%{d}%"
                    break
            response['sql'] = sql

        elif intent == 'list_bills':
            response['title'] = 'Bills Found'
            sql = "SELECT office_bill_no, bill_date, net_amount, status FROM bills WHERE 1=1"
            if 'draft' in query_lower: sql += " AND status = 'Draft'"
            elif 'paid' in query_lower: sql += " AND status = 'Paid'"
            date_filter = parse_date(query_lower)
            if date_filter:
                sql += " " + date_filter['condition'].replace('transaction_date', 'bill_date')
                response['params'].update(date_filter['params'])
            response['sql'] = sql

        elif intent == 'list_agreements':
            response['title'] = 'Agreements Found'
            sql = "SELECT agreement_no, tendered_amount, status FROM agreements WHERE 1=1"
            if 'active' in query_lower: sql += " AND status = 'Active'"
            response['sql'] = sql

        elif intent == 'list_budget':
            response['title'] = 'Budget Provisions'
            sql = "SELECT code, name_of_work, provision FROM budget WHERE 1=1"
            response['sql'] = sql

        elif intent == 'list_esic':
            response['title'] = 'ESIC Ledger'
            sql = "SELECT e.full_name, l.transaction_date, l.cr_amount as due FROM esic_ledger l JOIN employees e ON l.employee_id = e.id LIMIT 50"
            response['sql'] = sql

        elif intent == 'list_epf':
            response['title'] = 'EPF Ledger'
            sql = "SELECT e.full_name, l.transaction_date, l.cr_amount as due FROM epf_ledger l JOIN employees e ON l.employee_id = e.id LIMIT 50"
            response['sql'] = sql

        elif intent == 'list_aaes':
            response['title'] = 'AA & ES Sanctions'
            sql = "SELECT sub_head, type, aa_es_amount, status FROM aa_es WHERE 1=1"
            response['sql'] = sql

        elif intent == 'list_offices':
            response['title'] = 'Offices Found'
            sql = "SELECT OfficeName as name, OfficeCode as code, Phone FROM office"
            response['sql'] = sql

        elif intent == 'list_users':
            response['title'] = 'System Users'
            sql = "SELECT fullname, username, role FROM users"
            response['sql'] = sql

        elif intent == 'get_totals':
            response['type'] = 'text'
            sql = "SELECT SUM(net_amount) as total FROM bills WHERE status = 'Paid'"
            date_filter = parse_date(query_lower)
            if date_filter:
                sql += " " + date_filter['condition'].replace('transaction_date', 'bill_date')
                response['params'].update(date_filter['params'])
            response['sql'] = sql

        if not response['sql']:
            print(json.dumps({'error': f"I understood the intent as '{intent}', but I don't have a query for it yet."}))
        else:
            print(json.dumps(response))

    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == "__main__":
    main()
