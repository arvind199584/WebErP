# ModPyPhp Database Schema & Module Architecture Tree

Each PostgreSQL database table forms a specific functional unit (submodule) integrated into a primary module. Below is the complete hierarchical Tree View showing parent-child referential dependencies (`FOREIGN KEY` relations) and module organization.

---

```
ModPyPhp System Core
│
├── 🏢 Core Administrative Jurisdiction
│   └── office (Primary Office / Division Jurisdiction Node)
│
├── 💙 Finance Module
│   └── office
│       └── budget (Financial Budget Provisions & Head Allocations)
│           ├── aa_es (Administrative Approvals & Expenditure Sanctions)
│           ├── bills (Office Processing Bills & Contractor Claims)
│           └── hand_receipts (Expenditure Vouchers & TDS Deductions)
│
├── 💙 Admin & Contracting Module
│   └── office
│       ├── agencies (Vendor & Contractor Agencies Directory)
│       └── aa_es (Administrative Approvals)
│           └── agreements (Contractor & Legal Agency Agreements)
│               ├── work_orders (Work Execution Orders)
│               ├── supply_orders (Material Procurement & Delivery Orders)
│               ├── bills (Contractor Settlement Bills)
│               └── opening_balances (Contractor Financial Balances)
│
├── 💚 HR Management Module
│   └── office
│       └── employees (Staff Registry, Designations & Accounts)
│           ├── attendance_records (Daily Attendance & Absentee Logs)
│           ├── wages_ledger (Calculated Gross & Net Monthly Wages)
│           ├── epf_ledger (Employee Provident Fund Ledger)
│           ├── esic_ledger (Employee State Insurance Ledger)
│           └── agreements (Linked Outsourcing Agency Agreement)
│
├── 🧡 Workshop Management Module
│   └── office
│       └── turf_machines (Workshop Machinery, Make & Maintenance Intervals)
│           ├── turf_consumption_log (Daily Machine Operating & Fuel Logs)
│           └── turf_servicing_log (Repair, Servicing & Maintenance Cost Logs)
│
├── 💜 Turf Management Module
│   └── turf_inventory_categories (Chemical & Fertilizer Categories)
│       └── turf_inventory_items (Soil Treatments, Fertilizers, Pesticides & Fuels)
│           ├── turf_inventory_ledger (Chemical Stock Balances & Usage)
│           ├── water_log (Daily Irrigation Water Meter Log)
│           └── turf_machines (Linked Fuel Type Reference)
│
├── 📦 Store Module (View-Driven Architecture)
│   └── Dynamically managed by means of PostgreSQL Database Views:
│       ├── gc_view_main_stock_balance (Aggregated Main Stock Live Balances)
│       ├── gc_view_sub_ledger_balance (Sub-Ledger Balances per Consuming Division)
│       └── gc_view_machinery_consumption (Machinery Consumption Audit Summary)
│
└── 💖 SuperAdmin Governance & System Support
    └── office (Primary Office & Division Governance)
        ├── users (User Accounts & Privileges: Staff, Manager, SuperUser)
        │   └── internal_emails (Internal Messaging & Audit Notifications)
        ├── wage_orders & wage_items (Government Minimum Wage Scale Master)
        ├── nlp_knowledge_base (Natural Language AI Translation Rules)
        ├── activity_logs (PostgreSQL Trigger Audit Stream)
        └── System Schema Registries:
            ├── registry_tables
            ├── registry_constraints
            ├── registry_triggers
            ├── registry_procedures
            └── registry_enums
```

---

## Detailed Table & View Dictionary

| Module Name | Table / View Name | Key Dependencies | Module Description |
| :--- | :--- | :--- | :--- |
| **Core Base** | `office` | Root Jurisdiction | Central office division & jurisdiction master node |
| **Finance** | `budget` | `office.officeid` | Budget provisions and head-of-account allocations |
| **Finance** | `bills` | `budget.id`, `agreements.id`, `work_orders.id`, `supply_orders.id` | Office bill processing, gross/net calculations & approvals |
| **Finance** | `hand_receipts` | `budget.id`, `office.officeid` | Expenditure vouchers, hand receipts & TDS deductions |
| **Admin** | `aa_es` | `budget.id`, `office.officeid` | Administrative Approvals & Expenditure Sanctions |
| **Admin** | `agencies` | `office.officeid` | Contractor, vendor, and supplier directory |
| **Admin** | `agreements` | `aa_es.id`, `agencies.id` | Contractor legal agreements and scope definitions |
| **Admin** | `work_orders` | `aa_es.id`, `agencies.id` | Work execution orders issued under AA/ES |
| **Admin** | `supply_orders` | `aa_es.id`, `agencies.id` | Material procurement and delivery orders |
| **HR Management** | `employees` | `agreements.id`, `office.officeid` | Staff directory, bank details, and employment history |
| **HR Management** | `attendance_records` | `employees.id`, `office.officeid` | Daily attendance, leave, and absenteeism records |
| **HR Management** | `wages_ledger` | `employees.id`, `office.officeid` | Calculated monthly wages via PL/pgSQL procedures |
| **HR Management** | `epf_ledger` | `employees.id` | Employee Provident Fund deduction ledger |
| **HR Management** | `esic_ledger` | `employees.id` | Employee State Insurance deduction ledger |
| **Workshop Management** | `turf_machines` | `office.officeid`, `turf_inventory_items.id` | Machinery master, service intervals & operational status |
| **Workshop Management** | `turf_consumption_log` | `turf_machines.id` | Daily machine operating hours & fuel consumption (L) |
| **Workshop Management** | `turf_servicing_log` | `turf_machines.id` | Servicing, repair, parts replaced & cost records |
| **Turf Management** | `turf_inventory_categories` | None | Categories for chemicals, fertilizers & pesticides |
| **Turf Management** | `turf_inventory_items` | `turf_inventory_categories.id` | Chemical stock master (`description`, `ac_unit`) |
| **Turf Management** | `turf_inventory_ledger` | `turf_inventory_items.id` | Soil treatment application & stock balance ledger |
| **Turf Management** | `water_log` | `office.officeid` | Daily irrigation water meter readings (KL) |
| **Store Module** | `gc_view_main_stock_balance` *(View)* | `gc_main_stock_transactions` | Computed live stock balance per office |
| **Store Module** | `gc_view_sub_ledger_balance` *(View)* | `gc_sub_ledger_consumption` | Computed sub-ledger consumption balances per party |
| **Store Module** | `gc_view_machinery_consumption` *(View)* | `gc_sub_ledger_consumption`, `gc_machinery` | Computed machinery parts and fuel consumption summary |
| **SuperAdmin** | `office` | Root Jurisdiction | Office division master management (`officename`, `officecode`, `address`) |
| **SuperAdmin** | `users` | `office.officeid` | System user accounts, passwords, and roles (`superuser`, `manager`, `staff`) |
| **SuperAdmin** | `wage_orders` / `wage_items` | None | Government minimum wage scale notifications & skill scales |
| **SuperAdmin** | `nlp_knowledge_base` | `users.id` | Natural language NLP AI mapping rules and query overrides |
| **SuperAdmin** | `activity_logs` | None | PostgreSQL trigger audit stream for system data mutations |
