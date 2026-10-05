# Agreement Module - ModPyPhp

## Overview
The **Agreement Module** is a critical component of the ModPyPhp system, responsible for managing the contractual phase of a project. It bridges the gap between Administrative Approval (AA & ES) and actual execution (Work Orders/Payments). An Agreement links an approved budget and scope (from AA & ES) to a specific executing agency.

## Core Responsibilities
- **Contract Management:** Capturing agreement numbers, service charges, and tendered amounts.
- **Agency Linkage:** Associating a project with a vetted agency.
- **Scope Versioning:** Managing changes in manpower or items over the lifetime of the contract.
- **Statutory Compliance:** Defining whether a contract is subject to ESIC and EPF contributions.

## Key Components

### 1. Data Structure (`agreements` table)
- `agreement_no`: Unique identifier for the contract.
- `agency_id`: Foreign key to the `agencies` table.
- `aa_es_id`: Foreign key to the `aa_es` table.
- `tendered_amount`: The final negotiated cost of the project.
- `service_charge_percent`: Percentage fee paid to the agency for manpower contracts.
- `period`: A PostgreSQL `daterange` defining the contract duration.
- `scope`: A `jsonb` field that stores the history of manpower/item allocations.
- `bill_type_esic` / `bill_type_epf`: Boolean flags for statutory contribution calculation.

### 2. Workflow
1. **Creation (3-Step Wizard):**
   - **Step 1:** Select the parent AA & ES and the executing Agency.
   - **Step 2:** Define financial terms (Tendered Amount, Service Charge, Statutory Flags).
   - **Step 3:** Define the contract period and review the summary.
2. **Scope Management:**
   - On creation, the scope is automatically initialized from the AA & ES BOQ.
   - Users can update the scope (e.g., increasing/decreasing manpower) using the `updateScope` action.
   - The system tracks these changes temporally using a JSON history.

### 3. Service Layer
- **`AgreementService`:** Handles CRUD operations and basic DTO mapping.
- **`AgreementScopeService`:** A specialized service that recalculates the "effective" BOQ by merging the original approval with any subsequent **Deviations** (quantity changes) or **Extra Items**.

## Integration Points
- **AA_ES Module:** Provides the initial scope and budget authorization.
- **Agency Module:** Provides the contractor details.
- **Deviation/Extra Item Modules:** Feed into the `AgreementScopeService` to modify the contract's effective capacity.
- **Attendance/Wages Modules:** Use the Agreement's scope to validate manpower limits and calculate service charges.

## Usage
Agreements can be viewed and managed via the main navigation under the **Registers** category.
- **List View:** Shows all active and past contracts.
- **Edit Scope:** Allows managers to adjust allocated resources based on site requirements.
