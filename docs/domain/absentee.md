# Absentee Module - Technical Summary

The **Absentee Module** in ModPyPhp is an integrated sub-system responsible for tracking employee attendance, generating statutory absentee statements, and calculating financial penalties for manpower shortages in contractor bills.

## 1. Core Data Structure
The module relies primarily on the `attendance_records` table:

```sql
CREATE TABLE attendance_records (
    id SERIAL PRIMARY KEY,
    officeid INT NOT NULL,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status VARCHAR(10) NOT NULL, -- 'P' (Present), 'A' (Absent), 'R' (Rest Day), 'L' (Leave)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_emp_date UNIQUE (employee_id, attendance_date)
);
```

## 2. Functional Components

### A. Attendance Marking (`AttendanceService`)
- **Bulk Entry:** Supports range-based entry (e.g., "1-10, 15, 20-25") for different statuses.
- **Stored Procedure:** Utilizes a database-side function `fill_employee_attendance` (or similar PL/pgSQL logic) to efficiently populate monthly records.
- **Validation:** Ensures attendance is not marked before an employee's `joining_date`.
- **Default State:** Any day within a month not explicitly marked for an active employee is treated as **Absent (A)** in reporting and billing logic.

### B. Absentee Statement (`AttendanceReportService`)
- **Report Generation:** Generates a 31-day grid for a specific Agreement and Month.
- **Sorting Logic:** Groups employees by designation and separates "Relievers" from regular staff.
- **Summary:** Provides a monthly tally of P/A/R/L days per employee.
- **Template:** Rendered via `modules/ReportPrinting/Views/partials/attendance_report.php`.

### C. Financial Integration (`PaymentService`)
- **Absentee Penalty:** During bill generation, the system calculates a deduction for "Shortage of Staff".
- **Formula:** `Total Absent Days (A) * Effective Daily Wage Rate`.
- **Impact:** This penalty is deducted from the "Total Basic Amount" before applying contractor profit/service charges.

### D. Agreement Transitions (Renewals)
- **Mid-Month Continuity:** If an agreement is renewed mid-month for the same agency, employees are cloned to the new agreement with an `inherited_from_id` link.
- **Unified Reporting:** The `AttendanceReportService` automatically merges attendance records from both the current employee ID and the predecessor ID for the specified month.
- **Visual Integrity:** This ensures a single 31-day row for each person in the "Absentee Statement" regardless of the underlying agreement change.

## 3. Business Rules
1. **Preservation of History:** Attendance records are strictly linked to `officeid` and `employee_id`.
2. **Inheritance Logic:** When a person transitions to a new agreement, their identity is preserved for reporting via the `inherited_from_id` field, while financial records (bills) remain isolated to the specific agreement period.
3. **Rest Days (R):** Marked based on the employee's `default_rest_day`.
4. **Joining Date Enforcement:** The system automatically marks days as 'Leave' (L) if the date is prior to the employee's official joining date (unless inherited).

## 4. Key Files
- **Service:** `modules/Attendance/Services/AttendanceService.php`
- **Report Service:** `modules/ReportPrinting/Services/AttendanceReportService.php`
- **Billing Logic:** `modules/Payment/Services/PaymentService.php`
- **View:** `modules/ReportPrinting/Views/partials/attendance_report.php`
- **Database Table:** `schema/tables/attendance_records.sql`
