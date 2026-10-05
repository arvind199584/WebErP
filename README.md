# ModPyPhp - Integrated Office Management System

ModPyPhp is a robust, modular web application designed for government office management. It integrates Finance, HR, Works, and Administration into a single platform, powered by a PHP backend, PostgreSQL database, and a Python-based AI assistant.

## 🚀 Key Features
- **Role-Based Access Control (RBAC):** Strict separation for Superuser, Manager, and Staff.
- **Row Level Security (RLS):** Database-enforced data isolation per office.
- **AI Database Assistant:** Natural Language Processing (NLP) tool to query data using English/Hinglish.
- **Automated Workflows:** Bill generation, Attendance tracking, and Statutory (EPF/ESIC) calculations.
- **Audit Logging:** Immutable, real-time tracking of all database changes.

## 🛠️ Tech Stack
- **Backend:** PHP 8.x (Vanilla, Modular Architecture)
- **Database:** PostgreSQL 14+ (with PL/pgSQL Triggers & RLS)
- **AI/ML:** Python 3.x (Flask, Scikit-Learn, Spacy/NLTK logic)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)

## 📂 Module Structure
- **Admin:** Office, User, Agency, Wage Rates management.
- **Finance:** Budget, Payments, Wages, Hand Receipts.
- **HR:** Employee, Attendance, EPF, ESIC.
- **Works:** Agreements, Work Orders, Deviations, Extra Items, AA & ES.
- **Utilities:** AI Assistant, PDF Tools, Report Printing.

## ⚙️ Installation

### 1. Prerequisites
- PHP 8.0+
- PostgreSQL
- Python 3.8+
- Composer (for PHP dependencies)

### 2. Setup
1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-repo/modpyphp.git
   ```
2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```
3. **Install Python Dependencies:**
   ```bash
   pip install flask scikit-learn pandas psycopg2 joblib
   ```
4. **Database Initialization:**
   - Create a database named `modpyphp`.
   - Run the schema scripts in `schema/` order: Types -> Tables -> Functions -> Triggers -> Views -> Seed.
   - Or run `php temp/create_logs_schema.php` and `php temp/apply_security_triggers.php`.

### 3. Running the Application
Use the provided batch file to start both PHP and Python servers:
```bash
start-server.bat
```
- **Web App:** http://localhost:8000
- **AI API:** http://localhost:5000

## 🔒 Security Model
- **Application Layer:** `PermissionManager.php` in each module defines role-based actions.
- **Database Layer:**
  - **RLS Policies:** Ensure users only see data for their `officeid`.
  - **Triggers:** Prevent modification of 'Paid' bills and restrict Manager edits on Agreements.
  - **Immutable Logs:** `activity_logs` table tracks all changes; `DELETE` is blocked for non-postgres users.

## 🤖 AI Assistant
The AI module (`modules/AIML`) uses a hybrid approach:
1.  **Normalization:** Converts slang/Hinglish to standard keywords (`synonyms.json`).
2.  **Classification:** ML model predicts intent (e.g., `get_wages`, `list_bills`).
3.  **Entity Linking:** Fuzzy matches names and locations against the database.
4.  **SQL Generation:** Maps intent + entities to a secure SQL query.
5.  **Training:** Superusers can "teach" the AI via the Training Hub.

## 📄 License
Proprietary / Internal Use Only.
