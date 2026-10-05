# Agreement Module Documentation

## Overview
The **Agreement Module** manages the legal and financial contracts between the office and external agencies. It transforms administrative approvals into executable contracts with defined financial terms and resource allocations.

## Database Architecture

### `agreements` Table
The core table storing contractual data.
- **Primary Key:** `id` (serial)
- **Foreign Keys:**
  - `officeid` -> `office.officeid`: The administrative unit responsible.
  - `agency_id` -> `agencies.id`: The executing contractor.
  - `aa_es_id` -> `aa_es.id`: The source administrative approval.
- **Financial Columns:**
  - `tendered_amount` (numeric 15,2): The final contract value.
  - `service_charge_percent` (numeric 5,2): Agency fee (common in manpower contracts).
- **Temporal & Statutory:**
  - `period` (daterange): Start and end dates of the contract.
  - `bill_type_esic` / `bill_type_epf` (boolean): Flags to enable statutory deductions.
- **Dynamic Content:**
  - `scope` (jsonb): Stores the history of manpower and item allocations over time.

### Database Logic (Triggers)
- **`trg_audit_agreements`**: Captures every INSERT, UPDATE, or DELETE in the global `activity_log`.
- **`trg_restrict_agreement_edit`**: Prevents modifications to critical contract terms once the agreement is active or has linked financial transactions.
- **`chk_period_not_empty`**: A check constraint ensuring the contract `period` daterange is valid and not empty.

## Functional Workflow

### 1. Agreement Creation
A 3-step wizard manages the complex data entry required for a new contract:
- **Step 1:** Basic linkage between Agency and AA & ES.
- **Step 2:** Financial configuration (Service charge, tendered amount, and statutory flags).
- **Step 3:** Period definition and final summary.

### 2. Scope Management
Agreements in ModPyPhp are "live." The `scope` field allows for temporal resource management:
- On creation, the scope is synced from the parent AA & ES BOQ.
- Managers can update the scope (e.g., adding an extra security guard) mid-contract.
- These changes are stored as a versioned JSON array, allowing the system to know the "effective capacity" for any historical date.

### 3. Service Layer Integration
- **`AgreementService`**: Primary CRUD handler.
- **`AgreementScopeService`**: Recalculates the "Effective BOQ" by merging the base contract with approved **Deviations** (quantity changes) and **Extra Items**.

## Immutability & Audit Integrity
Agreements serve as the legal foundation for financial expenditures. To ensure compliance with government auditing standards:
- **Deletion Lock:** An agreement cannot be deleted if it has any linked employees, bills, or work orders. The database uses `RESTRICT` constraints to enforce this.
- **Modification Lock:** Once transactions (wages or payments) are posted against an agreement, critical columns (Agency, AA_ES, Agreement No) are locked by the `trg_restrict_agreement_edit` trigger.
- **Historical Continuity:** Instead of deleting and re-creating, the system uses the `predecessor_id` to chain agreements together, preserving the full lifecycle of a manpower project.
