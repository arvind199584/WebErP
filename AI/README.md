# ModPyPhp Hinglish SQL Translation Service

This directory contains the tools and scripts to train, configure, and test a customized **Ollama** model (`hinglish-crud-llama`) optimized to translate Hinglish (Hindi + English) prompts into correct PostgreSQL CRUD (SELECT, INSERT, UPDATE, DELETE) statements matching the ModPyPhp schema.

## Directory Contents

1. **[generator.py](file:///F:/Research/ModPyPhp/AI/generator.py)**: A compiler that parses your active schema metadata ([modpyphp_schema.json](file:///F:/Research/ModPyPhp/schema/modpyphp_schema.json)) and compiles a custom **`Modelfile`** with complete DDL representations, constraint warnings, and Hinglish few-shot translation examples.
2. **[build_model.py](file:///F:/Research/ModPyPhp/AI/build_model.py)**: Automates running `generator.py` and executing the `ollama create` process to build/register the model.
3. **[test_client.py](file:///F:/Research/ModPyPhp/AI/test_client.py)**: An interactive CLI testing console. You can write Hinglish sentences, inspect the generated SQL, and choose to run it against your database (mutating operations ask for explicit confirmation).

---

## Instructions: How to Build and Train

### Step 1: Export Database Schema
First, ensure you have the latest schema JSON exported by running:
```powershell
python scripts/export_schema_json.py
```
*(This writes the active PostgreSQL database structure to `schema/modpyphp_schema.json`).*

### Step 2: Build the Custom Ollama Model
Run the model builder script to compile the Modelfile and create the model inside Ollama:
```powershell
python AI/build_model.py
```
This will:
* Scan the JSON schema and generate the `Modelfile`.
* Call `ollama create hinglish-crud-llama -f AI/Modelfile`.

Verify the model is registered using:
```powershell
ollama list
```
You should see `hinglish-crud-llama:latest` listed.

---

## Instructions: How to Test

Run the testing utility:
```powershell
python AI/test_client.py
```
This opens an interactive shell prompt. Try inputs like:

* **Select**: `"Ramesh driver ki designation or salary detail dikhao"`
* **Insert**: `"Mower category me item register karo code 'MWR-PL-9' name 'Blade Standard' qty 5 unit 'Pcs'"`
* **Update**: `"Karan Malhotra ka designation 'Superintendent' set kardo"`
* **Delete (Audit-Aware)**: `"Employee ID 10 ko delete karo"`
  *(The model is trained to generate an `UPDATE` setting `leaving_date = CURRENT_DATE` instead of raw `DELETE` for referential integrity).*

---

## Future Integration Steps

To replace the existing AI assistant pipeline in ModPyPhp (which currently only allows SELECT operations and relies on keyword parsing):

1. **Backend Service Hook**: 
   Modify `modules/AIML/Python/nlp/core_nlp.py` under the Flask server.
   Replace:
   ```python
   LLM_MODEL = "gemma2:9b"
   ```
   with:
   ```python
   LLM_MODEL = "hinglish-crud-llama"
   ```
2. **Loosen Safe SQL Verification**:
   The validation function (`validate_sql` in `core_nlp.py`) currently blacklists `INSERT`, `UPDATE`, and `DELETE`. To support full CRUD queries via natural language, you must update the safety check list (`_FORBIDDEN_KEYWORDS`) while retaining safety checks against SQL injection like statement stacking (`;`), system function calls, or unauthorized metadata table reads.
