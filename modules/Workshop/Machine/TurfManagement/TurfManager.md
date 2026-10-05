# TurfManagement Module Technical Summary

## Overview
The **TurfManagement** module is a comprehensive system designed to handle various aspects of turf maintenance and operations, including inventory management, resource tracking (water, indents, receipts), machine logging, and automated reporting. It is built as a modular component within the larger application framework.

## Architecture
The module follows a structured **MVC + Service + DTO** architectural pattern, ensuring separation of concerns and maintainability.

### 1. Controller Layer
- Controllers (e.g., `TurfManagementController`, `InventoryItemController`) extend `App\Core\BaseController`.
- They handle HTTP requests, coordinate with services, and render PHP-based views.
- Includes specialized AI controllers (`AIEntryController`, `AIReportController`) for assisted data processing.

### 2. Service Layer
- Contains the core business logic (e.g., `InventoryItemService`, `LogService`).
- Acts as an intermediary between Controllers and Models.
- Uses DTOs to pass data between layers, ensuring type safety.

### 3. Data Transfer Objects (DTO)
- Simple objects (e.g., `InventoryItemDTO`, `WaterLogDTO`) used to represent data structures across the system.
- Typically include properties and a `toArray()` method for serialization.

### 4. Model Layer
- Models (e.g., `InventoryItemModel`, `MachineModel`) extend `App\Core\BaseModel`.
- Directly interact with the database using PDO.
- Tables follow a `turf_` prefixing convention (e.g., `turf_inventory_items`).

### 5. View Layer
- PHP files located in `Views/` and sub-module `Views/` directories.
- Responsible for rendering the UI and providing data entry forms.

## Core Features & Sub-Modules
- **InventoryItems**: Manages the catalog of items used in turf maintenance (descriptions, units).
- **Indents**: Tracks requests and requisitions for resources.
- **Receipts**: Records incoming stock and supplies.
- **Machines**: Monitors machinery usage, maintenance, and status.
- **WaterLog**: Specialized tracking for water consumption and irrigation activities.
- **Logs & Audit**: Maintains a history of activities and provides an audit screen for corrections and backfilling.
- **Reports**: Generates operational summaries, including AI-powered insights and printable versions.

## Security & Access Control
- **PermissionManager**: A centralized security component that defines role-based access for various actions (`list`, `create`, `update`, `delete`, `auditLogs`, etc.).
- Roles typically include `superuser`, `admin`, `manager`, and `user`.

## Frontend Assets
- **CSS**: `assets/turf_style.css` provides module-specific styling.
- **JavaScript**: `assets/turf_script.js` handles interactive elements, AJAX requests, and UI logic.

## AI Integration
The module features integrated AI capabilities for:
- **Data Entry**: Assisting in the creation of logs and entries via `AIEntryController`.
- **Reporting**: Generating automated, intelligent summaries via `AIReportController`.

## Technical Standards
- **Namespacing**: `App\Modules\TurfManagement`
- **Typing**: Uses PHP 7/8 features including strict typing and return type hints.
- **Database**: SQL-driven via PDO with parameter binding for security.
