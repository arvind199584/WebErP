import os
import json
import datetime

def build_golden_seed_sql():
    sql = []
    sql.append("-- ============================================================")
    sql.append("-- GOLDEN SEED DATA FOR DEMO SANDBOX DATABASE (modpyphp_demo)")
    sql.append("-- ============================================================")
    sql.append("SET client_encoding = 'UTF8';")
    sql.append("SET standard_conforming_strings = on;")
    sql.append("SET session_replication_role = replica; -- Disable triggers during bulk seed")
    sql.append("")

    # List of tables to truncate
    tables = [
        "turf_servicing_log", "turf_machine_spare_parts", "turf_machine_repair_items", "turf_machine_repairs",
        "turf_consumption_log", "water_log", "tm", "turf_machines",
        "turf_inventory_ledger", "turf_inventory_items", "turf_inventory_categories",
        "bills", "hand_receipts", "esic_ledger", "epf_ledger", "wages_ledger",
        "attendance_records", "employees", "agreements", "supply_orders", "work_orders",
        "agencies", "aa_es", "budget", "opening_balances",
        "users", "office", "noticeboard", "wage_items", "wage_orders"
    ]
    for t in tables:
        sql.append(f"TRUNCATE TABLE public.{t} CASCADE;")
    sql.append("")

    # 1. OFFICES
    sql.append("-- 1. OFFICES")
    sql.append("INSERT INTO public.office (officeid, officename, officecode, address, phone, email, contactperson, has_finance, has_store, has_workshop, has_hr) VALUES")
    sql.append("(1, 'Metro Central Headquarters (Demo HQ)', 'MCD-HQ', 'Connaught Place, Central Complex, New Delhi', '+91-11-23456789', 'hq@enterprise-erp.demo', 'Sh. Rajesh Verma (CE)', true, true, true, true),")
    sql.append("(2, 'West Sub-Division Operations (Field Office)', 'WSD-02', 'Sector 24, Dwarka Complex, New Delhi', '+91-11-28080123', 'west@enterprise-erp.demo', 'Er. Amit Sharma (EE)', true, true, true, true);")
    sql.append("SELECT setval('office_officeid_seq', 2, true);")
    sql.append("")

    # 2. USERS
    admin_hash = '$2y$10$7rT1VvrvTKvo4IBcMgXeTuesvsmf9rLNN9QhE7u8cOW2NV0AbDiwu' # admin123
    guest_hash = '$2y$10$I6eXVzqmwJyo4dehSIxP0.G2Mvmi3avoXA2DP4BGMj5Gg7pUktcha' # guest123
    sql.append("-- 2. USERS")
    sql.append("INSERT INTO public.users (id, officeid, firstname, lastname, usrname, password, phone, email, role) VALUES")
    sql.append(f"(1, 1, 'Chief', 'Administrator', 'daredevil', '{admin_hash}', '9876543210', 'admin@modpyphp.local', 'superuser'),")
    sql.append(f"(2, 1, 'Guest', 'Evaluator', 'guest_demo', '{guest_hash}', '9999900000', 'guest@modpyphp.local', 'superuser'),")
    sql.append(f"(3, 2, 'Vikram', 'Malhotra', 'manager_vikram', '{admin_hash}', '9811122233', 'vikram@modpyphp.local', 'manager'),")
    sql.append(f"(4, 2, 'Rahul', 'Verma', 'engineer_rahul', '{admin_hash}', '9822233344', 'rahul@modpyphp.local', 'staff'),")
    sql.append(f"(5, 1, 'Sunita', 'Rao', 'store_officer', '{admin_hash}', '9833344455', 'sunita@modpyphp.local', 'staff');")
    sql.append("SELECT setval('users_id_seq', 5, true);")
    sql.append("")

    # 3. BUDGET
    sql.append("-- 3. BUDGET")
    sql.append("INSERT INTO public.budget (id, officeid, fy, code, provision, name_of_work) VALUES")
    sql.append("(1, 1, '2026-27', 'B-1010-HQ', 4500000.00, 'Comprehensive Facilities & Manpower Maintenance HQ 2026-27'),")
    sql.append("(2, 2, '2026-27', 'B-2020-FLD', 8500000.00, 'Operations & Field Infrastructure Upkeep - West Division'),")
    sql.append("(3, 2, '2026-27', 'B-3030-TRF', 3200000.00, 'Machinery Servicing, Turf Consumables & Workshop Spares');")
    sql.append("SELECT setval('budget_id_seq', 3, true);")
    sql.append("")

    # 4. AA & ES (Sanctions)
    boq_manpower = json.dumps({
        "EPF": True, "ESIC": True, "Bonus": True, "Period": 12,
        "Items": [
            {"qty": 4, "rate": 32000, "description": "Assistant Engineer / Supervisor"},
            {"qty": 6, "rate": 26000, "description": "Computer Operator / Clerk"},
            {"qty": 8, "rate": 22000, "description": "Technical Operator / Store Assistant"}
        ]
    })
    boq_works = json.dumps({
        "EPF": True, "ESIC": True, "Bonus": False, "Period": 6,
        "Items": [
            {"qty": 1, "rate": 1800000, "description": "Annual Maintenance & Civil Renovation Contract"}
        ]
    })
    sql.append("-- 4. AA_ES (Administrative Approvals & Expenditure Sanctions)")
    sql.append("INSERT INTO public.aa_es (id, officeid, budgetid, sub_head, alias, type, boq, estimated_cost, justified_amount, aa_es_amount, status) VALUES")
    sql.append(f"(1, 1, 1, 'Deployment of Supervisory & Operations Manpower HQ', 'HQ Manpower 26-27', 'Manpower', '{boq_manpower}', 3850000.00, 3850000.00, 3850000.00, 'Approved'),")
    sql.append(f"(2, 2, 2, 'Field Infrastructure Maintenance & Civil Upkeep', 'West Field AMC', 'Market', '{boq_works}', 2400000.00, 2400000.00, 2400000.00, 'Approved');")
    sql.append("SELECT setval('aa_es_id_seq', 2, true);")
    sql.append("")

    # 5. AGENCIES (Vendors / Contractors)
    sql.append("-- 5. AGENCIES")
    sql.append("INSERT INTO public.agencies (id, name, account_no, ifsc, bank_name, gst_no, pan_no, address, contact_person, email) VALUES")
    sql.append("(1, 'Apex Facility & Manpower Solutions Pvt Ltd', '91802003445511', 'SBIN0001234', 'State Bank of India', '07AAAAA1234A1Z5', 'AAAAA1234A', 'Plot 45, Okhla Industrial Area Ph-III, New Delhi', 'Sh. R.K. Singhania', 'apex@solutions.demo'),")
    sql.append("(2, 'National Infra Buildcon Associates', '05441010009876', 'PUNB0054400', 'Punjab National Bank', '07BBBBB5678B1Z2', 'BBBBB5678B', 'B-12 Nehru Place, Commercial Complex, New Delhi', 'Er. Sunil Khatri', 'infra@buildcon.demo'),")
    sql.append("(3, 'Precision Turf Equipments & Spares Co.', '20000455667788', 'HDFC0000123', 'HDFC Bank', '07CCCCC9012C1Z8', 'CCCCC9012C', 'G-14 Mayapuri Phase II, New Delhi', 'Gurpreet Singh', 'spares@precisionturf.demo');")
    sql.append("SELECT setval('agencies_id_seq', 3, true);")
    sql.append("")

    # 6. AGREEMENTS
    sql.append("-- 6. AGREEMENTS")
    sql.append("INSERT INTO public.agreements (id, officeid, agreement_no, agency_id, aa_es_id, service_charge_percent, tendered_amount, period, status, bill_type_esic, bill_type_epf, scope) VALUES")
    sql.append("(1, 1, '01/EE/MCD-HQ/2026-27', 1, 1, 4.50, 3680000.00, '[2026-04-01,2027-03-31)', 'Active', true, true, '{\"description\": \"Outsourced skilled manpower deployment for HQ & West Sub-Division\"}'),")
    sql.append("(2, 2, '02/EE/WSD-02/2026-27', 2, 2, 2.00, 2350000.00, '[2026-05-01,2026-11-30)', 'Active', false, false, '{\"description\": \"Periodic Civil & Field Infrastructure Maintenance\"}');")
    sql.append("SELECT setval('agreements_id_seq', 2, true);")
    sql.append("")

    # 7. EMPLOYEES (18 Employees across office 1 & office 2)
    employees = [
        (1, 1, 1, "Rohan Deshmukh", "Supervisor", "Sunday", "1001", "2024-01-10"),
        (2, 1, 1, "Pooja Kulkarni", "Receptionist", "Sunday", "1002", "2024-02-15"),
        (3, 1, 1, "Kavita Nair", "Computer Operator", "Sunday", "1003", "2024-03-01"),
        (4, 1, 1, "Mohit Saxena", "Accountant Clerk", "Sunday", "1004", "2024-03-10"),
        (5, 1, 1, "Sanjay Rawat", "Store Keeper", "Sunday", "1005", "2024-04-01"),
        (6, 1, 1, "Anil Meena", "Dak Rider", "Sunday", "1006", "2024-04-15"),
        (7, 2, 1, "Dinesh Kumar", "Supervisor", "Sunday", "1007", "2024-05-01"),
        (8, 2, 1, "Manoj Tiwari", "Machine Operator", "Monday", "1008", "2024-05-10"),
        (9, 2, 1, "Gopal Das", "Machine Operator", "Monday", "1009", "2024-06-01"),
        (10, 2, 1, "Ramesh Chand", "Technician", "Tuesday", "1010", "2024-06-15"),
        (11, 2, 1, "Raj Kumar", "Electrician", "Wednesday", "1011", "2024-07-01"),
        (12, 2, 1, "Satish Pal", "Plumber", "Wednesday", "1012", "2024-07-15"),
        (13, 2, 1, "Vijay Bahadur", "Security Guard", "Thursday", "1013", "2024-08-01"),
        (14, 2, 1, "Suresh Yadav", "Security Guard", "Thursday", "1014", "2024-08-10"),
        (15, 2, 1, "Harish Shankar", "Turf Assistant", "Friday", "1015", "2024-09-01"),
        (16, 2, 1, "Mukesh Saini", "Mali / Gardner", "Friday", "1016", "2024-09-15"),
        (17, 2, 1, "Babu Lal", "Mali / Gardner", "Saturday", "1017", "2024-10-01"),
        (18, 2, 1, "Deepak Joshi", "Field Assistant", "Saturday", "1018", "2024-10-15"),
    ]
    sql.append("-- 7. EMPLOYEES")
    sql.append("INSERT INTO public.employees (id, officeid, agreement_id, full_name, designation, default_rest_day, uan_no, esic_no, account_no, ifsc, bank_name, joining_date, is_reliever) VALUES")
    emp_rows = []
    for emp in employees:
        e_id, off, agr, name, desig, rest_day, code, jdate = emp
        acc = f"501004{code}8899"
        uan = f"101200{code}33"
        esic = f"201900{code}11"
        emp_rows.append(f"({e_id}, {off}, {agr}, '{name}', '{desig}', '{rest_day}', '{uan}', '{esic}', '{acc}', 'SBIN0005521', 'State Bank of India', '{jdate}', false)")
    sql.append(",\n".join(emp_rows) + ";")
    sql.append("SELECT setval('employees_id_seq', 18, true);")
    sql.append("")

    # 8. WAGE ORDERS & WAGE ITEMS
    sql.append("-- 8. WAGE ITEMS & WAGE ORDERS")
    wage_items = [
        (1, "Central Govt", "Supervisor", "Supervisor", "Per Person Per Month", 0.00),
        (2, "Central Govt", "Receptionist", "Receptionist", "Per Person Per Month", 0.00),
        (3, "Central Govt", "Computer Operator", "Computer Operator", "Per Person Per Month", 0.00),
        (4, "Central Govt", "Accountant Clerk", "Accountant Clerk", "Per Person Per Month", 0.00),
        (5, "Central Govt", "Store Keeper", "Store Keeper", "Per Person Per Month", 0.00),
        (6, "Central Govt", "Machine Operator", "Machine Operator", "Per Person Per Month", 0.00),
        (7, "Central Govt", "Technician", "Technician", "Per Person Per Month", 0.00),
        (8, "Central Govt", "Security Guard", "Security Guard", "Per Person Per Month", 0.00),
        (9, "Central Govt", "Turf Assistant", "Turf Assistant", "Per Person Per Month", 0.00),
        (10, "Central Govt", "Mali / Gardner", "Mali / Gardner", "Per Person Per Month", 0.00),
    ]
    sql.append("INSERT INTO public.wage_items (id, authority, item_name, json_key, unit, fixed_allowance) VALUES")
    wi_rows = [f"({w[0]}, '{w[1]}', '{w[2]}', '{w[3]}', '{w[4]}', {w[5]})" for w in wage_items]
    sql.append(",\n".join(wi_rows) + ";")
    sql.append("SELECT setval('wage_items_id_seq', 10, true);")
    sql.append("")

    wage_rates_json = json.dumps({
        "Supervisor": 32000.0, "Receptionist": 25500.0, "Computer Operator": 26000.0,
        "Accountant Clerk": 28000.0, "Store Keeper": 25000.0, "Machine Operator": 24500.0,
        "Technician": 24000.0, "Security Guard": 21500.0, "Turf Assistant": 21000.0, "Mali / Gardner": 20000.0
    })
    sql.append("INSERT INTO public.wage_orders (id, authority, letter_no, letter_date, valid_from, valid_to, rates) VALUES")
    sql.append(f"(1, 'Central Govt', 'CLC/ND/MW/2026/01', '2026-03-25', '2026-04-01', '9999-12-31', '{wage_rates_json}');")
    sql.append("SELECT setval('wage_orders_id_seq', 1, true);")
    sql.append("")

    # 9. ATTENDANCE RECORDS (Past 30 Days: 2026-09-01 to 2026-09-30)
    sql.append("-- 9. ATTENDANCE RECORDS (September 2026)")
    att_rows = []
    att_id = 1
    day_names = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"]
    start_dt = datetime.date(2026, 9, 1)
    for day_offset in range(30):
        cur_date = start_dt + datetime.timedelta(days=day_offset)
        weekday_idx = cur_date.weekday()
        cur_day_name = day_names[weekday_idx]

        for emp in employees:
            e_id, off, agr, name, desig, rest_day, code, jdate = emp
            if cur_day_name == rest_day:
                status = 'R'
            elif (e_id + day_offset) % 27 == 0:
                status = 'L'
            elif (e_id + day_offset) % 19 == 0:
                status = 'A'
            else:
                status = 'P'
            att_rows.append(f"({att_id}, {off}, {e_id}, '{cur_date}', '{status}')")
            att_id += 1

    sql.append("INSERT INTO public.attendance_records (id, officeid, employee_id, attendance_date, status) VALUES")
    for i in range(0, len(att_rows), 200):
        chunk = att_rows[i:i+200]
        sql.append(",\n".join(chunk) + (";" if i+200 >= len(att_rows) else ",\n"))
    sql.append(f"SELECT setval('attendance_records_id_seq', {att_id}, true);")
    sql.append("")

    # 10. WAGES, EPF, ESIC LEDGERS (Actual columns: id, officeid, employee_id, transaction_date, remark, cr_amount, dr_amount)
    sql.append("-- 10. WAGES LEDGER, EPF LEDGER, ESIC LEDGER")
    wages_rows = []
    epf_rows = []
    esic_rows = []
    ledger_id = 1
    for emp in employees:
        e_id, off, agr, name, desig, rest_day, code, jdate = emp
        basic_rate = 24000.00
        days_worked = 26
        earned_wages = round((basic_rate / 30) * days_worked, 2)
        epf_total = round(earned_wages * 0.25, 2)
        esic_total = round(earned_wages * 0.04, 2)

        wages_rows.append(f"({ledger_id}, {off}, {e_id}, '2026-09-30', 'Wages earned for September 2026 ({days_worked} days)', {earned_wages}, 0.00)")
        epf_rows.append(f"({ledger_id}, {off}, {e_id}, '2026-09-30', 'EPF remittance for September 2026', {epf_total}, 0.00)")
        esic_rows.append(f"({ledger_id}, {off}, {e_id}, '2026-09-30', 'ESIC contribution for September 2026', {esic_total}, 0.00)")
        ledger_id += 1

    sql.append("INSERT INTO public.wages_ledger (id, officeid, employee_id, transaction_date, remark, cr_amount, dr_amount) VALUES")
    sql.append(",\n".join(wages_rows) + ";")
    sql.append(f"SELECT setval('wages_ledger_id_seq', {ledger_id}, true);")

    sql.append("INSERT INTO public.epf_ledger (id, officeid, employee_id, transaction_date, remark, cr_amount, dr_amount) VALUES")
    sql.append(",\n".join(epf_rows) + ";")
    sql.append(f"SELECT setval('epf_ledger_id_seq', {ledger_id}, true);")

    sql.append("INSERT INTO public.esic_ledger (id, officeid, employee_id, transaction_date, remark, cr_amount, dr_amount) VALUES")
    sql.append(",\n".join(esic_rows) + ";")
    sql.append(f"SELECT setval('esic_ledger_id_seq', {ledger_id}, true);")
    sql.append("")

    # 11. BILLS
    sql.append("-- 11. BILLS & VOUCHERS")
    bill_items_1 = json.dumps([{"description": "Deployment of 18 Outsourced Personnel for Sep 2026", "amount": 384500.00}])
    deductions_1 = json.dumps([{"type": "TDS", "amount": 7690.00}, {"type": "GST TDS", "amount": 7690.00}])
    sql.append("INSERT INTO public.bills (id, officeid, office_bill_no, agency_bill_no, bill_date, bill_type, agreement_id, budget_id, bill_items, deductions, gross_amount, total_deduction, net_amount, status, period_from, period_to) VALUES")
    sql.append(f"(1, 1, 'BILL/HQ/2026/01', 'APX/INV/09-01', '2026-10-02', 'Item', 1, 1, '{bill_items_1}', '{deductions_1}', 384500.00, 15380.00, 369120.00, 'Processed', '2026-09-01', '2026-09-30'),")
    sql.append(f"(2, 2, 'BILL/WSD/2026/01', 'NAT/AMC/26-04', '2026-10-04', 'Item', 2, 2, '{bill_items_1}', '{deductions_1}', 195000.00, 7800.00, 187200.00, 'Draft', '2026-09-01', '2026-09-30');")
    sql.append("SELECT setval('bills_id_seq', 2, true);")
    sql.append("")

    # 12. STORE & INVENTORY (Categories, Items, Ledger)
    sql.append("-- 12. STORE INVENTORY (Categories, Items, Stock Ledger)")
    sql.append("INSERT INTO public.turf_inventory_categories (id, name, code) VALUES")
    sql.append("(1, 'Fuel & Petroleum', 'fuel'),")
    sql.append("(2, 'Oils & Lubricants', 'lubricants'),")
    sql.append("(3, 'Fertilizers & Nutrients', 'fertilizer'),")
    sql.append("(4, 'Chemicals & Crop Care', 'chemicals'),")
    sql.append("(5, 'Machine Spare Parts', 'spares'),")
    sql.append("(6, 'Tools & PPE Safety Gear', 'tools');")
    sql.append("SELECT setval('turf_inventory_categories_id_seq', 6, true);")
    sql.append("")

    store_items = [
        (1, "High Speed Diesel (HSD)", "Litre", 1),
        (2, "Motor Spirit (Petrol)", "Litre", 1),
        (3, "Engine Oil 15W40 CI-4", "Litre", 2),
        (4, "Hydraulic Oil ISO VG 68", "Litre", 2),
        (5, "Chassis Grease EP-2", "Kg", 2),
        (6, "Urea (46% Nitrogen)", "Bag (50kg)", 3),
        (7, "DAP (18:46:00)", "Bag (50kg)", 3),
        (8, "NPK 19:19:19 Water Soluble", "Kg", 3),
        (9, "Amistar Fungicide (Azoxystrobin)", "Litre", 4),
        (10, "Aliette Fungicide (Fosetyl-Al)", "Kg", 4),
        (11, "Mower Bedknife 21 inch Hardened", "Nos", 5),
        (12, "Heavy Duty V-Belt B-64", "Nos", 5),
        (13, "Fuel Filter Spin-On Element", "Nos", 5),
        (14, "Spark Plug NGK BPR6ES", "Nos", 5),
        (15, "Safety Helmets & Visors", "Nos", 6),
        (16, "Nitrile Heavy Duty Work Gloves", "Pairs", 6)
    ]
    sql.append("INSERT INTO public.turf_inventory_items (id, description, ac_unit, category_id) VALUES")
    si_rows = [f"({si[0]}, '{si[1]}', '{si[2]}', {si[3]})" for si in store_items]
    sql.append(",\n".join(si_rows) + ";")
    sql.append("SELECT setval('turf_inventory_items_id_seq', 16, true);")
    sql.append("")

    # Store Inventory Ledger (RECEIPT / ISSUE)
    sql.append("INSERT INTO public.turf_inventory_ledger (id, officeid, item_id, transaction_type, quantity, transaction_date, voucher_no, party, remarks) VALUES")
    ledger_entries = [
        (1, 1, 1, 'RECEIPT', 1200.000, '2026-09-01', 'PO-FUEL-01', 'Indian Oil Corporation', 'Monthly Bulk Diesel Tanker Delivery'),
        (2, 1, 2, 'RECEIPT', 350.000, '2026-09-01', 'PO-FUEL-02', 'Bharat Petroleum Corp', 'Petrol for Portable Brush Cutters'),
        (3, 1, 3, 'RECEIPT', 120.000, '2026-09-05', 'PO-LUB-01', 'Castrol India Ltd', 'Engine oil 20L Buckets x 6'),
        (4, 1, 4, 'RECEIPT', 80.000, '2026-09-05', 'PO-LUB-02', 'Servo Lubricants', 'Hydraulic Oil Drum'),
        (5, 2, 6, 'RECEIPT', 50.000, '2026-09-10', 'PO-AGR-01', 'IFFCO Farmers Cooperative', 'Field Urea Bags'),
        (6, 2, 7, 'RECEIPT', 40.000, '2026-09-10', 'PO-AGR-02', 'KRIBHCO Fertilizer Depot', 'DAP Base Dressing Bags'),
        (7, 2, 9, 'RECEIPT', 15.000, '2026-09-12', 'PO-CHEM-01', 'Syngenta Agro Dealer', 'Fungicide Bottles'),
        (8, 2, 11, 'RECEIPT', 12.000, '2026-09-15', 'PO-SPR-01', 'Precision Turf Spares', 'Mower replacement bedknives'),
        (9, 2, 1, 'ISSUE', 240.000, '2026-09-20', 'ISS-TRF-01', 'Tractor #1 & Green Mower', 'Issued for fairways mowing'),
        (10, 2, 6, 'ISSUE', 10.000, '2026-09-22', 'ISS-AGR-01', 'Greens Section Team', 'Turf nutrition application')
    ]
    sil_rows = [f"({le[0]}, {le[1]}, {le[2]}, '{le[3]}', {le[4]}, '{le[5]}', '{le[6]}', '{le[7]}', '{le[8]}')" for le in ledger_entries]
    sql.append(",\n".join(sil_rows) + ";")
    sql.append("SELECT setval('turf_inventory_ledger_id_seq', 10, true);")
    sql.append("")

    # 13. WORKSHOP & TURF MACHINES (Working / OffRoad / Decommissioned)
    sql.append("-- 13. WORKSHOP MACHINES & REPAIRS")
    machines = [
        (1, 2, "Toro Greensmaster 3150", "Toro USA", "Working", 1, "GM-3150", 120.0, 150.0, 120.0),
        (2, 2, "John Deere 2500B Riding Greens Mower", "John Deere", "Working", 1, "JD-2500B", 85.0, 100.0, 85.0),
        (3, 2, "Kubota L3902 Utility Tractor 4WD", "Kubota", "Working", 1, "KUB-3902", 210.0, 250.0, 210.0),
        (4, 2, "Honda HRU216 Commercial Mower", "Honda Power", "Working", 2, "HONDA-216", 45.0, 100.0, 45.0),
        (5, 2, "Stihl FS 250 Heavy Duty Brush Cutter", "Stihl Germany", "OffRoad", 2, "STIHL-FS250", 60.0, 50.0, 60.0),
        (6, 2, "Cushman Hauler 1200X Utility Vehicle", "Textron Cushman", "Working", 2, "CUSH-1200", 95.0, 150.0, 95.0),
        (7, 1, "Kirloskar High-Pressure 15HP Irrigation Pump", "Kirloskar", "Working", 1, "KIRL-P15", 310.0, 500.0, 310.0),
        (8, 2, "Redexim Verti-Drain 7521 Aerator", "Redexim", "Working", 1, "AER-7521", 40.0, 100.0, 40.0),
    ]
    sql.append("INSERT INTO public.turf_machines (id, officeid, name, make, status, fuel_item_id, shortname, service_done, service_due, service_interval) VALUES")
    m_rows = [f"({m[0]}, {m[1]}, '{m[2]}', '{m[3]}', '{m[4]}', {m[5]}, '{m[6]}', {m[7]}, {m[8]}, {m[9]})" for m in machines]
    sql.append(",\n".join(m_rows) + ";")
    sql.append("SELECT setval('turf_machines_id_seq', 8, true);")
    sql.append("")

    # Workshop Repairs (Job Cards)
    sql.append("INSERT INTO public.turf_machine_repairs (id, officeid, machine_id, repair_date, running_hours, work_done, spare_parts_used, cost, repaired_by, status, job_card_no, work_type) VALUES")
    sql.append("(1, 2, 5, '2026-10-01', 340.50, 'Carburetor ultrasonic cleaning, throttle cable adjustment and fuel line replacement', 'Fuel Line Pipe, Primer Bulb, NGK Spark Plug', 1850.00, 'Ramesh Chand (Tech)', 'In Progress', 'JC-2026-001', 'repair'),")
    sql.append("(2, 2, 1, '2026-09-24', 850.00, 'Reel sharpening, bedknife grinding, hydraulic fluid top-up and test cut on putting green', 'Bedknife Screws, 15W40 Oil, Hydraulic Seal', 4200.00, 'Precision Turf Service Eng', 'Completed', 'JC-2026-002', 'servicing');")
    sql.append("SELECT setval('turf_machine_repairs_id_seq', 2, true);")
    sql.append("")

    # 14. NOTICEBOARD
    sql.append("-- 14. NOTICEBOARD ANNOUNCEMENTS")
    sql.append("INSERT INTO public.noticeboard (id, title, content, badge_type, priority, is_active, created_at) VALUES")
    sql.append("(1, 'Welcome to Enterprise ERP Demo Portal', 'Welcome to the interactive preview environment! Explore Finance, Human Resources, Store Management, Workshop Repairs, and Works with full CRUD capabilities.', 'Announcement', 10, true, '2026-10-01 10:00:00'),")
    sql.append("(2, 'Native Mobile App v1.0 Available for Download', 'Download our official Android APK directly from the download section below or scan the QR code to experience mobile-native productivity.', 'Circular', 8, true, '2026-10-03 11:30:00'),")
    sql.append("(3, 'Scheduled Sandbox Refresh Policy', 'This sandbox database allows free data creation and modification for demonstration purposes. Reset the dataset back to its pristine state at any time using the Reset Demo Data button.', 'Maintenance', 5, true, '2026-10-05 09:00:00');")
    sql.append("SELECT setval('noticeboard_id_seq', 3, true);")
    sql.append("")

    sql.append("SET session_replication_role = DEFAULT; -- Re-enable triggers & foreign keys")
    sql.append("-- ============================================================")
    sql.append("-- END OF GOLDEN SEED DATA")
    sql.append("-- ============================================================")
    return "\n".join(sql)

if __name__ == '__main__':
    out_dir = r"E:\Research\ModPyPhp\database\dumps"
    os.makedirs(out_dir, exist_ok=True)
    out_file = os.path.join(out_dir, "demo_golden_seed.sql")
    content = build_golden_seed_sql()
    with open(out_file, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"Golden seed SQL generated at: {out_file} ({len(content)} bytes)")
