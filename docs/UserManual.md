# ModPyPhp User Manual

Welcome to the ModPyPhp Office Management System. This guide will help you navigate and use the application effectively based on your role.

---

## 1. Getting Started

### Login
- Access the application at `http://localhost:8000`.
- Enter your Username and Password.
- **Note:** Your access is strictly limited to your assigned Office (unless you are a Superuser).

### Dashboard
The sidebar menu is organized into functional areas:
- **Admin:** System configuration (Superuser only).
- **Finance:** Money matters (Budget, Bills, Wages).
- **HR:** Staff management (Employees, Attendance).
- **Works:** Project management (Agreements, Deviations).
- **Utilities:** Tools like AI Assistant and Report Printing.

---

## 2. Role-Based Guides

### 👤 Staff
*Your primary focus is daily operations and data entry.*

*   **Creating a Bill:**
    1.  Go to **Finance > Payments**.
    2.  Click **+ New Bill**.
    3.  Select the Agreement/Work Order. The system will auto-fill details.
    4.  Enter the bill items and quantities.
    5.  **Note:** You cannot edit a bill once it is marked 'Paid'.

*   **Marking Attendance:**
    1.  Go to **HR > Attendance**.
    2.  Select the Agreement and Month.
    3.  Mark P (Present), A (Absent), or L (Leave) for each employee.
    4.  Click **Save**.

*   **Hand Receipts:**
    1.  Go to **Finance > Hand Receipt**.
    2.  Click **+ New Hand Receipt**.
    3.  If "Budget Implication" is checked, you **must** select a valid Budget Head.

### 👔 Manager
*You oversee operations and approve critical data.*

*   **Managing Agreements:**
    1.  Go to **Works > Agreement**.
    2.  You can Create new agreements.
    3.  **Editing:** You can only modify the Scope, Amount, and Period of existing agreements. Other fields are locked.

*   **Budget Management:**
    1.  Go to **Finance > Budget**.
    2.  Ensure budget provisions are accurate for your office.

*   **Employee Management:**
    1.  Go to **HR > Employee**.
    2.  Add new staff members and assign them to agreements.

### 🚀 Superuser
*You have full system access and configuration rights.*

*   **System Config:**
    -   **Wage Rates:** Update Central Govt/Sports Wing rates in **Admin > Wage Rates**.
    -   **Users:** Create accounts for Managers and Staff.

*   **AI Training:**
    -   Go to **Admin > Database Assistant**.
    -   Click **⚙️ Go to Training Hub**.
    -   Review failed queries and use the "No-Code Builder" to teach the AI correct SQL.

*   **Audit Logs:**
    -   Go to **Admin > Activity Logs** to see a real-time feed of all system changes.

---

## 3. Feature Deep Dives

### 🤖 AI Database Assistant
Located in **Admin > Database Assistant**, this tool allows you to ask questions in plain English or Hinglish.

*   **Examples:**
    -   "List all employees at Rohini"
    -   "Hemant ki wages kitni hai?"
    -   "Total expenditure in January 2026"
    -   "Show pending bills"

*   **If it fails:** The system will log your question. A Superuser can then "teach" it the correct answer, making it smarter for next time.

### 🖨️ Report Printing
Located in **Utilities > Report Printing**. This is the central hub for generating official documents.

*   **Bill Printing:** Generates the 4-page standard bill format (Abstract, Memo, etc.).
*   **Attendance Register:** Prints the monthly absentee statement.
*   **Wages Verification:** Prints the Due/Drawn/Balance statement for audit.
*   **AA & ES:** Prints the formal Sanction Order or Calculation Sheet.

---

## 4. Troubleshooting

*   **"Permission Denied":** You are trying to access a module or perform an action not allowed for your role. Contact your Manager.
*   **"Insufficient Budget":** You are trying to create a Bill or Hand Receipt that exceeds the available funds for that Budget Head.
*   **AI not responding:** Ensure the Python backend is running (`start-server.bat`).
