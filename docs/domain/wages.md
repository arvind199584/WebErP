# Wages & WageRates Module Documentation

## Overview
The Wages system is a data-centric financial engine that converts attendance into ledger-based earnings. It handles temporal rate changes and automated batch processing of payroll.

## Key Components

### 1. Configuration (WageRates)
- **`wage_items`**: Master list of designations (e.g., Sweeper, Driver).
- **`wage_orders`**: Stores rates in a `jsonb` column. Each order has a validity period (`valid_from` to `valid_to`).
- **`add_wage_order()` (SQL Function)**: Handles the "closing" of old orders when a new one is inserted to ensure no overlaps.
- **`get_effective_rate(date, item_id)` (SQL Function)**: The core lookup logic that finds the correct rate for any given designation on any specific date.

### 2. Transactional (Wages)
- **`wages_ledger`**: The financial source of truth.
    - `cr_amount`: Earnings credited based on attendance.
    - `dr_amount`: Payments/Deductions debited.
- **`generate_wages_for_period()` (SQL Function)**: 
    - Scans `attendance_records` for a given employee.
    - Calculates daily earnings using `get_effective_rate`.
    - Posts a monthly total to the ledger.

## Workflow
1. **Rate Update**: User uploads a new government order via the `WageRate` module.
2. **Attendance**: Field staff mark attendance (P, A, R, L).
3. **Generation**: The `WagesService` triggers the SQL generation logic which populates the ledger.
4. **Disbursement**: Batch debit entries are posted when payments are made to employees.

## Data Integrity
- **JSON Validation**: `trg_validate_wage_rates` ensures the JSON blob in a wage order matches existing items.
- **Ledger Constraints**: `trg_validate_wages_entry` prevents inconsistent financial postings.
- **Audit Trail**: Every change is captured by the global `activity_log` trigger.
