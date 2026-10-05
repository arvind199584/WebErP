# Project: ModPyPhp - Integrated Office Management System

## Architectural Overview
ModPyPhp is a modular web application designed for government office management, integrating Finance, HR, Works, and Administration. It follows a custom MVC pattern in PHP and utilizes a Python service for AI-driven data interactions.

## Technical Stack
- **Backend:** PHP 8.x (Custom MVC)
- **Frontend:** HTML5, CSS3 (Vanilla), Bootstrap 5, HTMX
- **Database:** PostgreSQL (utilizing PL/pgSQL, Triggers, RLS, and Daterange types)
- **AI/ML:** Python 3 (Flask, scikit-learn, fuzzywuzzy)
- **Dependencies:** 
  - PHP: PHPWord, PhpSpreadsheet, GuzzleHttp
  - Python: Pandas, Psycopg2, Flask-Cors

## Module Structure
Each module follows a consistent directory structure:
- `Controller/`: Action handlers and routing logic.
- `Models/`: Data access objects using PDO.
- `Services/`: Business logic layer.
- `Views/`: HTMX-compatible UI templates.
- `Security/`: Module-specific access control.

## AI Assistant Workflow
1. **PHP (AIMLService):** Captures user input and sends it to the Python NLP service.
2. **Python (Brain):** Maps intent and entities to SQL using a Knowledge Base and Fuzzy Matching.
3. **Validation:** PHP validates SQL safety (SELECT-only, no forbidden keywords) before execution.
4. **Training:** Superusers can resolve failed queries by mapping them to correct SQL in the `nlp_knowledge_base`.

## Database Conventions
- **Naming:** Lowercase with underscores.
- **Auditing:** `activity_log` table tracks all mutations via triggers.
- **Constraints:** Heavy use of foreign keys and custom check functions (e.g., `validate_budget_availability`).
- **Preservation of History (No Deletions):** The system enforces strict referential integrity. Any record linked to a financial transaction (ledger entry, bill, or attendance) is protected from deletion via `RESTRICT` constraints to preserve a complete audit trail.
