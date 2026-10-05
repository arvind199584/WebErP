# Employee Module - Technical Summary

The **Employee Module** in ModPyPhp manages the lifecycle, statutory details, and contract alignment of all manpower deployed across various office locations. It serves as the master data source for attendance, payroll (wages), and statutory compliance (EPF/ESIC).

## 1. Core Data Structure
The `employees` table stores personal, banking, and statutory information:

```sql
CREATE TABLE employees (
    id SERIAL PRIMARY KEY,
    officeid INT NOT NULL,          -- Office assigned
    agreement_id INT NOT NULL,      -- Contract linked to
    full_name VARCHAR(255) NOT NULL,
    designation VARCHAR(150) NOT NULL,
    account_no VARCHAR(50),         -- Bank Details
    ifsc VARCHAR(20),
    bank_name VARCHAR(100),
    uan_no VARCHAR(50),             -- EPF Universal Account Number
    esic_no VARCHAR(50),            -- ESIC Insurance Number
    joining_date DATE NOT NULL,
    leaving_date DATE,              -- Resignation/Termination date
    default_rest_day day_of_week,   -- Preferred weekly off
    is_reliever BOOLEAN DEFAULT FALSE,
    inherited_from_id INT,          -- Link to predecessor record for renewals
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_office FOREIGN KEY(officeid) REFERENCES office(Officeid) ON DELETE RESTRICT,
    CONSTRAINT fk_emp_agreement FOREIGN KEY(agreement_id) REFERENCES agreements(id) ON DELETE RESTRICT,
    CONSTRAINT fk_emp_inheritance FOREIGN KEY(inherited_from_id) REFERENCES employees(id),
    CONSTRAINT uq_name_agreement UNIQUE (full_name, agreement_id)
);
```

## 2. Functional Components

### A. Lifecycle Management (`EmployeeService`)
- **CRUD Operations:** Standard management of employee records via `EmployeeModel`.
- **Batch Creation:** Supports bulk importing of employee data.
- **Inheritance (`inheritEmployees`):** Facilitates seamless renewal of agreements by cloning employees from a source agreement to a target agreement while maintaining an `inherited_from_id` link. This allows unified reporting across mid-month transitions.
- **Validation:** Enforces name uniqueness within the same agreement to prevent duplicate billing entries.
- **Contractual Alignment:** Every employee is mapped to an `agreement_id`, ensuring they are only billed under the correct work order.

### B. Reliever Logic
- **`is_reliever` Flag:** Distinguishes between fixed-post staff and standby (reliever) staff.
- **Reporting Impact:** In the Absentee Statement and Wage Reports, relievers are typically sorted separately or identified with specific markers.

### C. Statutory Compliance Integration
- **EPF/ESIC:** Stores `uan_no` and `esic_no` which are essential for generating the Electronic Challan cum Return (ECR) files for statutory bodies.
- **Banking:** Bank details are used for generating Bank Disbursement Sheets for direct wage transfers.

## 3. Security & Isolation
- **Row Level Security (RLS):** Employees are isolated by `officeid`. An office user can only see/manage employees assigned to their specific office. Superusers have global visibility.
- **Audit Logging:** All changes to employee records (insert, update, delete) are tracked in the `activity_log` via the `log_activity()` trigger.

## 4. Key Relationships
- **Attendance:** Attendance is recorded against `employee_id` in the `attendance_records` table.
- **Wages:** Monthly wages are calculated by joining `employees` with `wage_items` (via designation) and `attendance_records`.
- **Agreements:** Linked to `agreements` table to determine the active contract period and agency.

## 5. Key Files
- **Service:** `modules/Employee/Services/EmployeeService.php`
- **Model:** `modules/Employee/Models/EmployeeModel.php`
- **DTO:** `modules/Employee/DTO/EmployeeDTO.php`
- **Schema:** `schema/tables/employees.sql` (Master table definition)
