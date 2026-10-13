# Workshop Fleet & Spare Parts Management Roadmap
**Project:** ModPyPhp / Integrated Office Management System  
**Module:** Workshop -> Machine -> Turf Management & Store Inventory  
**Date of Discussion:** October 12, 2026  
**Document Status:** Actionable Architecture & Reference Blueprint  

---

## 1. Executive Summary & Ground-Reality Principles

During our architectural discussion, we established core principles to align **official manufacturer engineering catalogs** with **real-world workshop ground reality**:

1. **Dual-Layer Naming (The "Bearing 6405" Principle):**  
   Workshop mechanics and store helpers use colloquial shop-floor terms (*"Bearing 6405"*, *"3T Blade"*, *"Fuel Filter (Kapda/Cloth)"*). Engineering catalogs use long procurement specs (*"Radial Deep Groove Ball Bearing Stainless Steel Double Sealed 6405-2RS"*). The system must store **both**:
   * **`workshop_name` (Simple / Vernacular):** Displayed on screen, in dropdowns, and search bars.
   * **`nomenclature` (Technical Spec):** Used for official procurement, quotations, and purchase orders.
   * **`part_no`:** Standard OEM / manufacturer part number.

2. **Strict Data Safety (Separate Table First):**  
   We will never manipulate or risk the live production table directly. We will stage and verify all merges inside a dedicated master table: `turf_machine_spare_parts_master`.

3. **No Backlog Data Entry (The Cutover Baseline):**  
   Because the workshop has been operating for 2 years, staff will **not** back-enter historical RV/EV/Job cards. We will establish a single **Cutoff Date (Opening Balance)** snapshot. Past records remain archived in physical registers.

---

## 2. Fleet & Excel Catalog Mapping Matrix

The 6 Excel catalogs in `Repair Management/` contain **161+ spare parts and maintenance schedules**. Currently, **0 parts** exist in the database for these 6 equipment types.

| Excel Catalog | Total Parts | Target DB Machine (`turf_machines`) | Matching Strategy & Fleet Compatibility |
| :--- | :---: | :--- | :--- |
| **`Mahindra_265_DI_Tractor_Spare_Parts_Catalog.xlsx`** | **34** (+ 7 fluids) | **ID 55: `Mahindra 265`** (Make: Mahindra) | **Direct 1:1 Match**: Primary tractor in fleet. |
| **`Mahindra_Pole_Saw_Pruner_Spare_Parts_Catalog.xlsx`** | **24** | **ID 40: `Pole Saw-1`**<br>**ID 41: `Pole Saw-2`**<br>**ID 42: `Pole Saw-3`** | **1-to-Many Fleet Match**: Stored once with `compatible_machine_ids: [40, 41, 42]`. |
| **`Mahindra_Power_Brush_Cutter_Spare_Parts_Catalog.xlsx`** | **28** | **ID 9: `Brush Cutter 1`**<br>**ID 10: `Brush Cutter 2`**<br>**ID 11: `Brush Cutter 3`**<br>**ID 12: `Brush Cutter 4`**<br>**ID 13: `Brush Cutter 5`**<br>**ID 54: `Brush Cutter 7`** | **1-to-Many Fleet Match**: Stored once with `compatible_machine_ids: [9, 10, 11, 12, 13, 54]`. |
| **`Mahindra_Power_Chainsaw_Spare_Parts_Catalog.xlsx`** | **25** | **ID 14: `Chain Saw`** | **Direct 1:1 Match**: Primary woodcutting powerhead. |
| **`Mahindra_Shrub_Master_Spare_Parts_Catalog.xlsx`** | **25** | **New Equipment Record**<br>*(or ID 27/28: Rotary Slasher)* | **Tractor Implement Match**: Rotary slasher implement driven by tractor PTO. |
| **`Tractor_Gang_Mower_Spare_Parts_Catalog.xlsx`** | **25** | **New Equipment Record**<br>*(Tractor Trailed Gang Mower)* | **Tractor Implement Match**: 3/5-unit trailing cutting reel implement. |

---

## 3. Real-World Part Naming Examples

| Equipment | Official Manufacturer Nomenclature | Simple Workshop / Mechanic Name | Part Number / Code |
| :--- | :--- | :--- | :--- |
| **Mahindra 265** | `Engine Oil Filter Element (Spin-on)` | **Engine Oil Filter** | `005553198R91` |
| **Mahindra 265** | `Primary Fuel Filter (Cloth/Felt Insert)` | **Fuel Filter (Cloth / Primary)** | `005554201R91` |
| **Mahindra 265** | `Secondary Fuel Filter (Paper Insert)` | **Fuel Filter (Paper / Secondary)** | `005554202R91` |
| **Mahindra 265** | `Clutch Drive Plate (Single, 280 mm / 11")` | **Clutch Plate (11 Inch)** | `005554311R91` |
| **Brush Cutter** | `3-Tooth (3T) Hardened Steel Brush Blade` | **3T Blade** | `BC-BLD-3T255` |
| **Brush Cutter** | `Auto-Cut Bump Feed Nylon Trimmer Head` | **Nylon Trimmer Head (Bump Feed)** | `BC-TH-BUMP01` |
| **Brush Cutter** | `Lower Blade Locking Nut (LH Thread)` | **Blade Nut (Ulta Chudi / LH)** | `BC-FST-NUT-M10L` |
| **Chainsaw** | `20-Inch Laminated Guide Bar (Sprocket Nose)` | **20" Guide Bar (Patti)** | `CS-GB-20-325` |
| **Chainsaw** | `Full Chisel Saw Chain Loop (76 Drive Links)` | **Saw Chain (76 Link)** | `CS-CHN-325-76DL` |
| **Shrub Master** | `Slashing Blade (Swing Type)` | **Slasher Blade (Patti)** | `MFM-SM-BLD01` |
| **Gang Mower** | `Bottom Bedknife / Bottom Blade` | **Bedknife / Bottom Blade (30")** | `GM-BDK-30HD` |
| **Gang Mower** | `Overrunning Ratchet Drive Pawl Set` | **Ratchet Pawl & Spring (Chidiya)** | `GM-CLT-RPT01` |
| **General** | `Radial Deep Groove Ball Bearing 6405-2RS` | **Bearing 6405** | `6405` |

---

## 4. Visual Spare Parts (Photo Integration)

### Technical Implementation:
1. **Database Column:** `photo_path VARCHAR(255)` in the spare parts table.
2. **Storage Location:** `storage/spare_parts/` on the server filesystem.
3. **Automatic Bulk Linking:** Name downloaded catalog photos by their part number (e.g. `BC-BLD-3T255.jpg`, `6405.jpg`). A 1-time script links all photos to DB records in seconds.
4. **Mobile Camera Capture:** In the UI, add `<input type="file" accept="image/*" capture="environment">` so storekeepers can snap photos of physical parts on the rack using their smartphones.
5. **UI Display:** Small 40×40 px thumbnail in job card dropdowns and catalog lists, clicking which opens a high-resolution preview.

---

## 5. Non-Fuel Equipment & Electric Vehicles

### Equipment Classification:
* **Engine Prime Movers (Tractors, Greensmowers):** Require Fuel (`fuel_item_id = 1` for Petrol, `2` for Diesel), have running hours, use Engine Oil.
* **Towed Implements (Shrub Master, Dethatcher, Gang Mower):** **Zero Fuel (`fuel_item_id = NULL`)**, no hour meter, no engine oil. Servicing requires **Gearbox Oil (EP-90/EP-140)**, greasing, and blade sharpening.
* **Electric Vehicles (1 E-Rickshaw, 2 Electric Golf Carts):** **Zero Fuel (`fuel_item_id = NULL`)**, no engine oil. Servicing requires battery distilled water top-ups, terminal greasing, brake shoe lining replacements, and tire pressure checks.

### Safe Database Handling:
In PostgreSQL, `fuel_item_id` has a Foreign Key constraint referencing `turf_inventory_items(id)`.
* Do **NOT** use `fuel_item_id = 0` (this triggers a Foreign Key Violation error).
* Use **`fuel_item_id = NULL`**. In SQL, `NULL` represents *"No Fuel / Not Applicable"* and is natively supported by the foreign key constraint.

---

## 6. Multi-RV Staging & The 40-Liter Fuel Statutory Challenge

### The Real-World Statutory Challenge:
* Retail petrol pumps cannot legally dispense more than 40 Litres (or 50 Litres) of petrol in loose containers on a single cash memo / receipt.
* To purchase 160 or 200 Litres, the pump gives **4 to 5 separate Cash Memos / Invoices** (e.g. 40L each).
* Previously, the single-voucher RV screen treated multiple 40L entries as duplicates under the same voucher and blocked them.

### The Solution: Multi-RV Batch Staging with JSONB Items
1. **1 RV = 1 Row / 1 ID:**
   In table `turf_receipt_vouchers`, each voucher takes exactly one row, with line items stored in an **`items JSONB`** column.
2. **Client-Side Staging:**
   The user enters `Date`, `RV No`, `Supplier`, selects `Item` and `Quantity`.
   * Staging under `RV-101`: 40L Petrol.
   * Changing input to `RV-102`: 40L Petrol.
   * Duplicate rule: *"Within any single RV, an item cannot appear twice. Across distinct RVs, same items are completely allowed."*
3. **Database Commit:**
   Commits 4 distinct RV records (`RV-101` to `RV-104`), each with its own JSONB items array, and logs signed entries in the perpetual inventory ledger.

---

## 7. Central Perpetual Inventory Ledger & On-the-Fly Balance

### Multi-Channel Depletion:
Stock is depleted through 3 distinct operational channels:
1. **Stationery, Sanitary, Agronomy:** Issued in hand to a person via **Issue Vouchers (IV)**.
2. **Fuel (Petrol / Diesel / 2T):** Issued to machines or gang pools via the **Daily Fuel Consumption Log**.
3. **Spare Parts:** Issued to machines via **Workshop Job Cards / Repairs**.

### Calculating Balance at Day X:
The central stock ledger records signed quantities: **`+ve` for Receipts (RV)** and **`-ve` for Depletions (IV, Fuel Log, Job Card)**.
$$\text{Stock as of Day X} = \sum_{\text{date} \le \text{Day X}} (\text{Receipts}) - \sum_{\text{date} \le \text{Day X}} (\text{All Depletions})$$

Because every transaction is dated, querying `SUM(qty)` where `transaction_date <= 'Day X'` returns the exact mathematical balance on any past day in milliseconds, complete with a bank-style passbook statement.

---

## 8. Procurement-to-Payment (P2P) Closed Loop: Supply Order -> RV -> Bill

ModPyPhp already has `aa_es.boq JSONB` and `bills.bill_items JSONB`. Integrating RVs creates a complete, automated government procurement pipeline:

1. **Requisition & A/A & E/S:** Workshop requisitions generate `aa_es` with BOQ line items in JSONB.
2. **Supply Order:** Contract issued to vendor/fuel supplier referencing `aa_es_id`.
3. **Fetch from Supply Order in RV:**
   * Storekeeper selects Supply Order and clicks **"Fetch Items"**.
   * System populates sanctioned items, contract rates, and remaining sanctioned quantities.
   * Storekeeper inputs actual delivered quantity (e.g. 40L Petrol), enters RV No, and saves.
4. **Supply Status Flag:**
   * Marked as **`Incomplete / Partial Supply`** (for ongoing deliveries) or **`Complete Supply`** (final consignment).
5. **Automated Monthly Part Bills (Annual 1-Year Rate Contracts):**
   * For long-term fuel contracts (e.g. 50,000L annual contract), 30–40 individual 40L RVs are generated throughout the month.
   * At month-end, the billing module aggregates:
     `SUM(qty) FROM turf_receipt_vouchers WHERE supply_order_id = :id AND date BETWEEN start_of_month AND end_of_month`
   * Automatically generates the **Part Bill / Running Account (R/A) Bill**:
     * Multiplies monthly volume by agreed contract rates.
     * Computes statutory deductions (TDS, GST-TDS).
     * Attaches all forty RV numbers in the bill annexure for audit approval.
     * Automatically feeds into `view_agreement_financial_progress`.

---

## 9. Operational Workflow: Printable Logs & 2-Stroke Pool Issues

### Problem A: Officers & Auditors Require Physical Signed Logs
* **Solution:** Create an **A4 Printable Monthly Log Sheet** (`@media print` CSS).
* At month end, click **"Print Monthly Log"**. It prints clean tables with machine name, daily hours, fuel issued, operator signature lines, and officer sign/stamp boxes ready to be punched into guard files.

### Problem B: Handhelds Have No Hour Meters & Consume 2T Oil in Daily Bulk Batches
* **Physical Reality:** In the morning, storekeepers issue a single pooled figure (e.g. **8, 10, or 12 pouches**) for the entire 2-stroke gang. They do not split fractions of pouches between individual brush cutters.
* **Solution:** A dedicated **"2-Stroke Fleet Daily Pool Entry"**:
  1. Date: `12-Oct-2026`
  2. Fleet: `2-Stroke Handheld Fleet Pool`
  3. Petrol: `25.0 Liters`
  4. 2T Pouches: `10 Pouches` (single daily count)
  5. Operator / Gang Head: `Ramu / Horticulture Team`
* **Result:** Takes 5 seconds to enter, stock deducts accurately, and the printed monthly sheet shows daily pouch totals ready for physical signatures.

---

## 10. Migration & Implementation Checklist

When ready to implement:

- [ ] **Step 1:** Create staging table `turf_machine_spare_parts_master` and copy the 219 current records into it.
- [ ] **Step 2:** Parse all Excel catalogs and populate new records with `current_stock = 0.00` and dual names (`workshop_name` + `nomenclature`).
- [ ] **Step 3:** Perform physical shelf audit to set opening balances (`current_stock`).
- [ ] **Step 4:** Calibrate current running hours on all machines once (`current_meter`, `service_done`, `service_due`).
- [ ] **Step 5:** Create the `view_machine_service_history` SQL view.
- [ ] **Step 6:** Add A4 printable monthly log templates for punch filing.
- [ ] **Step 7:** Set up `storage/spare_parts/` folder for photo uploads and auto-linking.
- [ ] **Step 8:** Implement Multi-RV Staging with `items JSONB` and the Supply Order integration hook.
