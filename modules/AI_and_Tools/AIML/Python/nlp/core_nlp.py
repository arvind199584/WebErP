"""
core_nlp.py — Flask NLP service for ModPyPhp AIML module.

DATABASE POLICY:
  - All existing tables (nlp_knowledge_base, wages, employees, etc.) are
    accessed READ-ONLY via SELECT.
  - The ONLY table this service WRITES to is: aiml.vector_kb
    (a fully independent table in the separate 'aiml' schema).
  - No ALTER TABLE, no DROP, no modifications to any existing table.

Pipeline (in priority order for every query):
  1. Synonym normalisation
  2. pgvector semantic lookup in aiml.vector_kb (cosine similarity >= 0.72)
  3. Sync any new nlp_knowledge_base entries into aiml.vector_kb (read-only)
  4. Llama SQL generation via Ollama chat API (gemma2:9b)
     |- System prompt includes full schema context
     |- Self-correction loop: up to 2 retries if safety check fails
  5. Log unresolved queries to pending_training (existing table, INSERT only
     as the app was already doing — no schema changes)
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import os
import re
import json
import urllib.request
from dotenv import load_dotenv

# Load .env from project root
load_dotenv(os.path.abspath(os.path.join(os.path.dirname(__file__), '../../../../..', '.env')))

try:
    import psycopg2
    USE_PSYCOPG2 = True
except Exception:
    import pg8000.dbapi
    USE_PSYCOPG2 = False
import time
import threading
from functools import lru_cache

from .entity_extractor import extract_entities, set_known_offices
from .sql_generator import generate_sql_from_intent

app = Flask(__name__)
CORS(app)

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
BASE_DIR           = os.path.dirname(os.path.abspath(__file__))
UTILITY_PYTHON_PATH = os.path.abspath(os.path.join(BASE_DIR, '../../../Utility/Python'))
SYNONYM_PATH       = os.path.join(UTILITY_PYTHON_PATH, 'synonyms.json')

# Paths for the Intent Classifier
INTENT_MAP_PATH    = os.path.abspath(os.path.join(BASE_DIR, '../../../Utility/Python/intent_map.json'))
TRAINING_DATA_PATH = os.path.abspath(os.path.join(BASE_DIR, '../../../Utility/Python/training_data.json'))
MODEL_PATH         = os.path.abspath(os.path.join(BASE_DIR, '../core/model.pkl'))

nlp_engine = None

def get_nlp_engine():
    global nlp_engine
    if nlp_engine is None:
        try:
            from core.nlp_engine import NLPEngine
            nlp_engine = NLPEngine(
                model_path=MODEL_PATH,
                data_path=TRAINING_DATA_PATH,
                synonym_path=SYNONYM_PATH
            )
            print("[startup] NLPEngine loaded and initialized.")
        except Exception as e:
            print(f"[startup] Error loading NLPEngine: {e}")
    return nlp_engine

# Ollama endpoints
OLLAMA_URL  = "http://localhost:11434"
EMBED_MODEL = "all-minilm"   # 384-dim, ~45 MB, very fast
LLM_MODEL   = "gemma2:9b"    # ~5.4 GB, fits on 8 GB RAM

# Semantic similarity threshold for KB hit
KB_THRESHOLD = 0.72

# Maximum self-correction attempts when LLM output fails safety check
MAX_LLM_RETRIES = 2

DB_CONFIG = {
    "dbname":   os.getenv("DB_NAME", "modpyphp"),
    "user":     os.getenv("DB_USER", "postgres"),
    "password": os.getenv("DB_PASS", "daredevil"),
    "host":     os.getenv("DB_HOST", "localhost"),
    "port":     os.getenv("DB_PORT", "5432"),
    "sslmode":  os.getenv("DB_SSLMODE", "disable"),
}

# Tables the LLM must never query (enforced in safety check)
SENSITIVE_TABLES = {
    'USERS', 'PENDING_TRAINING', 'NLP_KNOWLEDGE_BASE',
    'ACTIVITY_LOG', 'VECTOR_KB',
}

# ── Keyword blacklist (whole-word matched against normalised SQL) ─────────────
# DML / DDL
_FORBIDDEN_KEYWORDS = [
    'INSERT', 'UPDATE', 'DELETE', 'UPSERT', 'MERGE',
    'DROP', 'TRUNCATE', 'ALTER', 'CREATE', 'REPLACE',
    'GRANT', 'REVOKE',
    # Execution / flow control
    'EXEC', 'EXECUTE', 'CALL', 'PERFORM', 'DO',
    'DECLARE', 'RAISE',
    # Dangerous set operators used for data exfiltration
    'UNION', 'INTERSECT', 'EXCEPT',
    # PostgreSQL server-side dangerous operations
    'COPY', 'VACUUM', 'ANALYZE', 'REINDEX', 'CLUSTER',
    # Dangerous built-in functions / extensions
    'PG_SLEEP', 'PG_READ_FILE', 'PG_STAT_FILE',
    'PG_LS_DIR', 'PG_EXECUTE', 'PG_RELOAD_CONF',
    'PG_ROTATE_LOGFILE', 'PG_TERMINATE_BACKEND',
    'PG_CANCEL_BACKEND', 'PG_ADVISORY_LOCK',
    'LO_IMPORT', 'LO_EXPORT', 'LO_READ', 'LO_WRITE',
    'DBLINK', 'DBLINK_EXEC',
    'CURRENT_SETTING', 'SET_CONFIG',
    # Catalog / metadata access
    'INFORMATION_SCHEMA', 'PG_CATALOG',
    'PG_CLASS', 'PG_SHADOW', 'PG_AUTHID', 'PG_USER',
    'PG_ROLES', 'PG_STAT_ACTIVITY',
    # Transaction manipulation
    'COMMIT', 'ROLLBACK', 'SAVEPOINT',
]

# ── Literal string patterns (no word-boundary needed) ────────────────────────
_FORBIDDEN_PATTERNS = [
    ';',        # statement stacking
    '--',       # SQL line comment
    '/*',       # block comment open
    '*/',       # block comment close
    '$$',       # dollar-quote (PL/pgSQL injection)
    '\x00',     # null byte
    '\\x',      # hex-escaped characters (evasion)
    'chr(',     # chr() function can spell forbidden words
    'char(',    # same
]

# Global state
synonyms: dict = {}

# ---------------------------------------------------------------------------
# DB helper
# ---------------------------------------------------------------------------

def get_db_conn():
    if USE_PSYCOPG2:
        return psycopg2.connect(**DB_CONFIG)
    else:
        import ssl
        ssl_ctx = ssl.create_default_context() if DB_CONFIG.get("sslmode") != "disable" else None
        return pg8000.dbapi.connect(
            user=DB_CONFIG["user"],
            password=DB_CONFIG["password"],
            host=DB_CONFIG["host"],
            port=int(DB_CONFIG["port"]),
            database=DB_CONFIG["dbname"],
            ssl_context=ssl_ctx
        )

# ---------------------------------------------------------------------------
# Ollama helpers
# ---------------------------------------------------------------------------

def _ollama_post(endpoint: str, payload: dict, timeout: int = 60) -> dict:
    url  = f"{OLLAMA_URL}{endpoint}"
    data = json.dumps(payload).encode("utf-8")
    req  = urllib.request.Request(
        url, data=data,
        headers={"Content-Type": "application/json"}
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


@lru_cache(maxsize=1024)
def get_embedding(text: str) -> list:
    """Returns a 384-dim embedding from Ollama all-minilm, or [] on failure."""
    try:
        res = _ollama_post("/api/embeddings", {"model": EMBED_MODEL, "prompt": text}, timeout=10)
        return res.get("embedding", [])
    except Exception as exc:
        print(f"[embed] Ollama error: {exc}")
        return []


def ask_llm(messages: list) -> str:
    """Sends a chat request to gemma2:9b. Returns '' on failure."""
    try:
        payload = {
            "model":   LLM_MODEL,
            "messages": messages,
            "stream":  False,
            "options": {"temperature": 0.0, "num_predict": 512},
        }
        res = _ollama_post("/api/chat", payload, timeout=120)
        return res.get("message", {}).get("content", "").strip()
    except Exception as exc:
        print(f"[llm] Ollama chat error: {exc}")
        return ""

# ---------------------------------------------------------------------------
# aiml.vector_kb operations  (only table we write to)
# ---------------------------------------------------------------------------

_sync_lock = threading.Lock()
_last_sync_timestamp = 0.0
SYNC_THROTTLE_SECONDS = 10.0

def sync_kb_to_vector_store(force=False):
    """
    READ-ONLY sync: reads new nlp_knowledge_base entries (SELECT only),
    generates embeddings, and inserts them into aiml.vector_kb.
    The original nlp_knowledge_base table is NEVER modified.
    """
    global _last_sync_timestamp
    now = time.time()
    if not force and (now - _last_sync_timestamp < SYNC_THROTTLE_SECONDS):
        return

    with _sync_lock:
        if not force and (time.time() - _last_sync_timestamp < SYNC_THROTTLE_SECONDS):
            return
        _last_sync_timestamp = time.time()

        try:
            conn = get_db_conn()
            cur  = conn.cursor()
            # Find entries in nlp_knowledge_base not yet mirrored in aiml.vector_kb
            cur.execute("""
                SELECT nk.question_text, nk.sql_query
                FROM   nlp_knowledge_base nk
                WHERE  NOT EXISTS (
                    SELECT 1 FROM aiml.vector_kb vk
                    WHERE  lower(vk.question_text) = lower(nk.question_text)
                );
            """)
            new_rows = cur.fetchall()
            synced = 0
            for question_text, sql_query in new_rows:
                emb = get_embedding(question_text)
                if emb:
                    cur.execute("""
                        INSERT INTO aiml.vector_kb (question_text, sql_query, source, vec_embedding)
                        VALUES (%s, %s, 'synced_from_kb', %s)
                        ON CONFLICT (question_text) DO NOTHING;
                    """, (question_text.lower(), sql_query, str(emb)))
                    synced += 1
            if synced:
                conn.commit()
                print(f"[sync] Mirrored {synced} new KB entry(ies) into aiml.vector_kb.")
            cur.close()
            conn.close()
        except Exception as exc:
            print(f"[sync] KB sync warning (non-fatal): {exc}")


def vector_kb_lookup(query: str):
    """
    Searches aiml.vector_kb using pgvector cosine distance.
    Returns the best-matching sql_query string if similarity >= KB_THRESHOLD,
    else None.
    Fallback: exact text match if Ollama is unreachable.
    """
    # Try exact match first to bypass Ollama embedding generation (for speed and 100% accuracy)
    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        cur.execute(
            "SELECT sql_query FROM aiml.vector_kb "
            "WHERE lower(question_text) = %s LIMIT 1;",
            (query.lower(),)
        )
        row = cur.fetchone()
        cur.close(); conn.close()
        if row:
            print(f"[kb] Exact match hit for: '{query}'")
            return row[0]
    except Exception as exc:
        print(f"[kb] Exact lookup error: {exc}")

    query_emb = get_embedding(query)
    if not query_emb:
        return None

    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        emb_str = str(query_emb)
        cur.execute("""
            SELECT question_text, sql_query,
                   1 - (vec_embedding <=> %s::vector) AS similarity
            FROM   aiml.vector_kb
            WHERE  vec_embedding IS NOT NULL
            ORDER  BY vec_embedding <=> %s::vector
            LIMIT  1;
        """, (emb_str, emb_str))
        row = cur.fetchone()
        cur.close(); conn.close()

        if row:
            question, sql_query, similarity = row
            print(f"[kb] Best match: '{question}' (similarity={similarity:.4f})")
            if similarity >= KB_THRESHOLD:
                return sql_query
    except Exception as exc:
        print(f"[kb] pgvector lookup error: {exc}")

    return None


def add_to_vector_kb(question_text: str, sql_query: str, source: str = 'llm_generated'):
    """
    Stores a new question→SQL pair in aiml.vector_kb with its embedding.
    Called after successful LLM generation so future identical/similar queries
    hit the KB directly.
    """
    emb = get_embedding(question_text)
    if not emb:
        return
    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        cur.execute("""
            INSERT INTO aiml.vector_kb (question_text, sql_query, source, vec_embedding)
            VALUES (%s, %s, %s, %s)
            ON CONFLICT (question_text) DO UPDATE
                SET sql_query     = EXCLUDED.sql_query,
                    vec_embedding = EXCLUDED.vec_embedding,
                    source        = EXCLUDED.source;
        """, (question_text.lower(), sql_query, source, str(emb)))
        conn.commit()
        cur.close(); conn.close()
        print(f"[kb] Stored new vector KB entry: '{question_text[:60]}'")
    except Exception as exc:
        print(f"[kb] Store error (non-fatal): {exc}")

# ---------------------------------------------------------------------------
# Schema helpers  (SELECT only)
# ---------------------------------------------------------------------------

def get_db_schema() -> dict:
    """
    Returns {table_name: [col1, col2, ...]} for all public tables.
    SELECT only — no modifications.
    """
    EXCLUDED = {
        'users', 'pending_training', 'nlp_knowledge_base', 'activity_log',
    }
    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        cur.execute("""
            SELECT table_name, column_name
            FROM   information_schema.columns
            WHERE  table_schema = 'public'
            ORDER  BY table_name, ordinal_position;
        """)
        schema = {}
        for table, col in cur.fetchall():
            if table.lower() not in EXCLUDED:
                schema.setdefault(table, []).append(col)
        cur.close(); conn.close()
        return schema
    except Exception as exc:
        print(f"[schema] Error: {exc}")
        return {}


def get_db_relations() -> list:
    """Returns a list of foreign key relations: [{'source_table': ..., 'source_column': ..., 'target_table': ..., 'target_column': ...}]"""
    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        cur.execute("""
            SELECT
                conrelid::regclass::text AS source_table,
                a.attname AS source_column,
                confrelid::regclass::text AS target_table,
                af.attname AS target_column
            FROM
                pg_constraint c
                JOIN pg_attribute a ON a.attnum = ANY(c.conkey) AND a.attrelid = c.conrelid
                JOIN pg_attribute af ON af.attnum = ANY(c.confkey) AND af.attrelid = c.confrelid
            WHERE
                c.contype = 'f';
        """)
        relations = []
        for src_tbl, src_col, tgt_tbl, tgt_col in cur.fetchall():
            src_tbl = src_tbl.split('.')[-1]
            tgt_tbl = tgt_tbl.split('.')[-1]
            relations.append({
                "source_table": src_tbl.strip('"'),
                "source_column": src_col.strip('"'),
                "target_table": tgt_tbl.strip('"'),
                "target_column": tgt_col.strip('"')
            })
        cur.close(); conn.close()
        return relations
    except Exception as exc:
        print(f"[relations] Error fetching relations: {exc}")
        return []

def get_db_schema_with_relations() -> dict:
    """
    Returns:
    {
       "tables": {
          "table1": ["col1", "col2", ...],
          ...
       },
       "relations": [
          {"source_table": "table1", "source_column": "fk_col", "target_table": "table2", "target_column": "id"},
          ...
       ]
    }
    """
    schema = get_db_schema()
    relations = get_db_relations()
    filtered_relations = []
    for r in relations:
        if r['source_table'] in schema and r['target_table'] in schema:
            filtered_relations.append(r)
    return {
        "tables": schema,
        "relations": filtered_relations
    }

def schema_to_ddl(schema_map: dict) -> str:
    """Formats schema map (tables and relations) for the LLM system prompt."""
    tables = schema_map.get("tables", {})
    relations = schema_map.get("relations", [])
    
    output = "Tables and Columns:\n"
    for tbl, cols in tables.items():
        output += f"  {tbl}({', '.join(cols)})\n"
        
    if relations:
        output += "\nRelationships (Foreign Keys):\n"
        for r in relations:
            output += f"  {r['source_table']}.{r['source_column']} -> {r['target_table']}.{r['target_column']}\n"
            
    return output

def compile_json_to_sql(sq: dict) -> str:
    if not sq or not isinstance(sq, dict):
        return ""
        
    action = sq.get("action", "SELECT").upper()
    if action == "INSERT":
        return ""

    cols = sq.get("columns", [])
    if not cols:
        cols = ["*"]
    elif isinstance(cols, str):
        cols = [cols]
    cols_str = ", ".join(cols)

    table = sq.get("table")
    if not table:
        return ""

    sql = f"{action} {cols_str} FROM {table}"

    joins = sq.get("joins", [])
    if isinstance(joins, list):
        for join in joins:
            if isinstance(join, dict):
                tbl = join.get("table")
                on_clause = join.get("on")
                if tbl and on_clause:
                    sql += f" JOIN {tbl} ON {on_clause}"

    filters = sq.get("filters", [])
    if isinstance(filters, list) and filters:
        filter_clauses = []
        for f in filters:
            if isinstance(f, str):
                filter_clauses.append(f)
            elif isinstance(f, dict):
                col = f.get("column")
                op = f.get("operator", "=")
                val = f.get("value")
                if col and val:
                    filter_clauses.append(f"{col} {op} {val}")
        if filter_clauses:
            cleaned_filters = []
            for fc in filter_clauses:
                fc_stripped = fc.strip()
                if fc_stripped.upper().startswith("AND "):
                    fc_stripped = fc_stripped[4:]
                elif fc_stripped.upper().startswith("OR "):
                    fc_stripped = fc_stripped[3:]
                cleaned_filters.append(fc_stripped)
            sql += " WHERE " + " AND ".join(cleaned_filters)

    group_by = sq.get("group_by", [])
    if group_by:
        if isinstance(group_by, list):
            sql += " GROUP BY " + ", ".join(group_by)
        elif isinstance(group_by, str):
            sql += " GROUP BY " + group_by

    order_by = sq.get("order_by")
    if order_by:
        sql += f" ORDER BY {order_by}"

    limit = sq.get("limit")
    if limit:
        sql += f" LIMIT {limit}"

    return sql

# ---------------------------------------------------------------------------
# SQL safety validator  (mirrors AIMLService.php::isSqlSafe)
# ---------------------------------------------------------------------------

def validate_sql(sql: str) -> tuple:
    """
    Robust SQL safety check. Returns (is_safe: bool, reason: str).

    Checks (in order):
      1. Must start with SELECT (after stripping whitespace)
      2. Literal dangerous patterns: comments, semicolons, dollar-quotes,
         null bytes, hex escapes, chr()/char() encoding tricks
      3. Whole-word keyword blacklist: all DDL, DML, dangerous PG functions,
         set operators, transaction control, catalog access
      4. Sensitive table guard
    """
    if not sql or not sql.strip():
        return False, "Empty query."

    # -- Step 0: normalise for comparison (collapse whitespace, upper-case)
    # Strip leading whitespace/newlines that could hide a non-SELECT start
    sql_stripped = sql.strip()
    # Collapse all internal whitespace to single spaces for keyword scanning
    sql_normalised = re.sub(r'\s+', ' ', sql_stripped).upper()

    # -- Step 1: must start with SELECT
    if not sql_normalised.startswith('SELECT'):
        return False, "Query must begin with SELECT."

    # -- Step 2: literal forbidden patterns (no word boundary needed)
    for pattern in _FORBIDDEN_PATTERNS:
        if pattern.upper() in sql_normalised:
            return False, f"Forbidden pattern detected: '{pattern}'"

    # -- Step 3: whole-word keyword blacklist
    for kw in _FORBIDDEN_KEYWORDS:
        if re.search(r'(?<![\w])' + re.escape(kw) + r'(?![\w])', sql_normalised):
            return False, f"Forbidden keyword: '{kw}'"

    # -- Step 4: sensitive table guard
    for tbl in SENSITIVE_TABLES:
        if re.search(r'(?<![\w])' + re.escape(tbl) + r'(?![\w])', sql_normalised):
            return False, f"Access to protected table '{tbl}' is not permitted."

    # -- Step 5: max length guard (very long SQL may be an obfuscation attempt)
    if len(sql_stripped) > 4000:
        return False, "Query exceeds maximum allowed length (4000 chars)."

    return True, "ok"

# ---------------------------------------------------------------------------
# LLM SQL generation with self-correction
# ---------------------------------------------------------------------------

_SYSTEM_PROMPT = """\
You are a PostgreSQL structured query builder. Your job is to output a single structured JSON object representing the SQL SELECT query tokens for a government office management database.

Database Schema Map (Tables, columns, and relationships):
{schema_ddl}

Your output must be a single JSON object with the following keys. Do not include any markdown formatting, backticks, or explanation outside the JSON:
{{
  "action": "SELECT",
  "columns": ["col1", "col2", ...],
  "table": "table_name AS alias",
  "joins": [
     {{"table": "joined_table AS alias", "on": "alias1.col = alias2.col"}}
  ],
  "filters": [
     "alias.col = :param",
     "alias.col ILIKE :param"
  ],
  "group_by": ["alias.col1", ...],
  "order_by": "alias.col1 ASC|DESC",
  "limit": 500
}}

Rules:
1. Output ONLY the raw JSON. No explanation, no comments.
2. The action must be SELECT.
3. Use proper SQL aliases when joining tables to avoid ambiguity.
4. If a relationship/foreign key exists in the schema, use it to build correct JOIN conditions.
5. If the query cannot be generated safely or doesn't map to public tables, return exactly: {{"error": "CANNOT_GENERATE"}}
"""

_FIX_PROMPT = """\
Your previous JSON output failed compilation/validation:
Error: {error}

Original question: {question}

Output ONLY the corrected raw JSON object. If it cannot be fixed safely, reply exactly: {{"error": "CANNOT_GENERATE"}}
"""


def _strip_sql(raw: str) -> str:
    raw = raw.strip()
    raw = re.sub(r'^```(?:json)?\s*', '', raw, flags=re.IGNORECASE)
    raw = re.sub(r'\s*```$', '', raw)
    return raw.strip()


def generate_sql_with_llm(user_query: str, schema_map: dict) -> tuple:
    """
    Calls gemma2:9b to generate a safe SELECT SQL from a structured JSON.
    Returns (sql | None, source_label, error_msg | None, structured_query_dict | None).
    """
    schema_ddl = schema_to_ddl(schema_map)
    system_content = _SYSTEM_PROMPT.format(schema_ddl=schema_ddl)

    messages = [
        {"role": "system", "content": system_content},
        {"role": "user",   "content": user_query},
    ]

    raw = ask_llm(messages)
    if not raw:
        return None, "llm_failed", "LLM returned empty response.", None

    for attempt in range(MAX_LLM_RETRIES + 1):
        raw_clean = _strip_sql(raw)
        
        try:
            sq = json.loads(raw_clean)
        except Exception as e:
            print(f"[llm] Attempt {attempt}: JSON parse failed: {e}")
            reason = f"Invalid JSON format: {e}"
            if attempt < MAX_LLM_RETRIES:
                fix_content = _FIX_PROMPT.format(error=reason, question=user_query)
                messages.append({"role": "assistant", "content": raw})
                messages.append({"role": "user",      "content": fix_content})
                raw = ask_llm(messages)
                if not raw:
                    break
                continue
            else:
                return None, "llm_failed", f"JSON parse failed: {e}", None

        if sq.get("error") == "CANNOT_GENERATE":
            return None, "llm_failed", "LLM indicated query cannot be generated safely.", sq

        try:
            sql = compile_json_to_sql(sq)
        except Exception as e:
            print(f"[llm] Attempt {attempt}: Compilation failed: {e}")
            reason = f"SQL compilation failed: {e}"
            if attempt < MAX_LLM_RETRIES:
                fix_content = _FIX_PROMPT.format(error=reason, question=user_query)
                messages.append({"role": "assistant", "content": raw})
                messages.append({"role": "user",      "content": fix_content})
                raw = ask_llm(messages)
                if not raw:
                    break
                continue
            else:
                return None, "llm_failed", f"SQL compilation failed: {e}", sq

        if not sql:
            reason = "Compiled SQL is empty."
            if attempt < MAX_LLM_RETRIES:
                fix_content = _FIX_PROMPT.format(error=reason, question=user_query)
                messages.append({"role": "assistant", "content": raw})
                messages.append({"role": "user",      "content": fix_content})
                raw = ask_llm(messages)
                if not raw:
                    break
                continue
            else:
                return None, "llm_failed", reason, sq

        is_safe, reason = validate_sql(sql)
        if is_safe:
            print(f"[llm] SQL compiled and accepted on attempt {attempt}: {sql[:80]}")
            return sql, "llm", None, sq

        print(f"[llm] Attempt {attempt}: Safety check failed ({reason}). Requesting fix...")
        if attempt < MAX_LLM_RETRIES:
            fix_content = _FIX_PROMPT.format(error=reason, question=user_query)
            messages.append({"role": "assistant", "content": raw})
            messages.append({"role": "user",      "content": fix_content})
            raw = ask_llm(messages)
            if not raw:
                break
        else:
            return None, "llm_failed", f"SQL failed safety check after {MAX_LLM_RETRIES} retries: {reason}", sq

    return None, "llm_failed", "LLM self-correction exhausted without producing safe SQL.", None

# ---------------------------------------------------------------------------
# Synonym normalisation
# ---------------------------------------------------------------------------

def normalize_query(query: str) -> str:
    if not synonyms:
        return query.lower()
    q = query.lower()
    for standard, variations in synonyms.items():
        for var in variations:
            q = re.sub(r'\b' + re.escape(var) + r'\b', standard, q)
    return q

# ---------------------------------------------------------------------------
# Flask Routes
# ---------------------------------------------------------------------------

@app.route('/nlp', methods=['POST'])
def process_nlp_query():
    data       = request.json or {}
    user_query = data.get('query', '').strip()
    # Retrieve schema map with relations dynamically
    schema_map = get_db_schema_with_relations()

    if not user_query:
        return jsonify({"error": "Empty query"}), 400

    # Sync any new KB entries first (throttled)
    sync_kb_to_vector_store()

    # Initialize decisions tracking
    level_1_desc = "No match"
    level_2_desc = "Skipped"

    # 1. Synonym normalisation
    normalized = normalize_query(user_query)

    # 2. Semantic KB lookup (queries aiml.vector_kb, not nlp_knowledge_base)
    kb_sql = vector_kb_lookup(user_query)
    if not kb_sql and normalized != user_query.lower():
        kb_sql = vector_kb_lookup(normalized)

    if kb_sql:
        level_1_desc = f"Semantic KB hit: {kb_sql}"
        print(f"[nlp] Level 1 Success: {level_1_desc}")
        return jsonify({
            "sql": kb_sql, 
            "params": {}, 
            "source": "knowledge_base",
            "level_1_decision": level_1_desc,
            "level_2_decision": level_2_desc
        })

    # 3. Intent mapping system (JSON Intent Classifier + SQL Generator)
    engine = get_nlp_engine()
    if engine:
        try:
            intent = engine.predict_intent(user_query)
            print(f"[intent] Predicted intent: '{intent}' for query: '{user_query}'")
            if intent and intent != 'unknown':
                entities = extract_entities(user_query)
                print(f"[intent] Extracted entities: {entities}")
                
                intent_result = generate_sql_from_intent(intent, entities, INTENT_MAP_PATH)
                if intent_result:
                    if intent_result.get('action_type') == 'INSERT':
                        level_1_desc = f"Intent classifier matched '{intent}' -> Structured INSERT action"
                        return jsonify({
                            "action_type": "INSERT",
                            "target_table": intent_result.get('target_table'),
                            "data": intent_result.get('data'),
                            "source": "intent_mapping",
                            "level_1_decision": level_1_desc,
                            "level_2_decision": level_2_desc
                        })
                    
                    sql = intent_result.get('sql')
                    if sql:
                        is_safe, reason = validate_sql(sql)
                        if is_safe:
                            level_1_desc = f"Intent classifier matched '{intent}' -> Generated safe SQL: {sql}"
                            return jsonify({
                                "sql": sql,
                                "params": intent_result.get('params', {}),
                                "source": "intent_mapping",
                                "level_1_decision": level_1_desc,
                                "level_2_decision": level_2_desc
                            })
                        else:
                            level_1_desc = f"Intent classifier matched '{intent}' but SQL failed safety: {reason}"
                    else:
                        level_1_desc = f"Intent classifier matched '{intent}' but SQL was empty"
                else:
                    level_1_desc = f"Intent classifier matched '{intent}' but SQL generator returned None"
            else:
                level_1_desc = "No match (classified as 'unknown')"
        except Exception as e:
            level_1_desc = f"Intent mapping error: {e}"
            print(f"[intent] Error in intent mapping: {e}")

    # 4. LLM SQL generation with self-correction
    print(f"[nlp] Level 1 failed: {level_1_desc}. Proceeding to Level 2 (LLM)...")
    sql, source, err, structured_query = generate_sql_with_llm(user_query, schema_map)
    if sql:
        level_2_desc = f"LLM generated structured JSON compiled to safe SQL: {sql}"
        # Cache the successful LLM result so future similar queries are fast
        add_to_vector_kb(user_query, sql, source='llm_generated')
        return jsonify({
            "sql": sql, 
            "params": {}, 
            "source": source,
            "structured_query": structured_query,
            "level_1_decision": level_1_desc,
            "level_2_decision": level_2_desc
        })
    else:
        level_2_desc = f"LLM failed: {err}"

    # 5. Cannot generate — log for admin review (return 200 so PHP can insert it to pending_training)
    print(f"[nlp] Both levels failed. Level 1: {level_1_desc}. Level 2: {level_2_desc}")
    
    # Create the rich structured JSON query log
    log_payload = {
        "question": user_query,
        "level_1": level_1_desc,
        "level_2": level_2_desc
    }
    log_query_str = json.dumps(log_payload)
    
    return jsonify({
        "error": "I could not generate a SQL query for your request. It has been logged for review.",
        "log_query": log_query_str,
        "detail": err
    }), 200


@app.route('/nlp-report', methods=['POST'])
def nlp_report_query():
    """Alias to /nlp."""
    return process_nlp_query()


@app.route('/nlp-action', methods=['POST'])
def nlp_action_query():
    """
    Action queries (INSERT/CREATE intent) are still handled by the PHP
    form layer. For SELECT-style action queries, delegate to /nlp.
    """
    return process_nlp_query()


@app.route('/normalize-sql', methods=['POST'])
def normalize_sql_route():
    """Ask the LLM to fix a fuzzy/broken SQL string."""
    data      = request.json or {}
    fuzzy_sql = data.get('sql', '').strip()
    schema    = data.get('schema', {}) or get_db_schema()

    if not fuzzy_sql:
        return jsonify({"exact_sql": ""}), 400

    fix_messages = [
        {
            "role": "system",
            "content": (
                "You are a PostgreSQL expert. Fix the following SQL to be valid "
                f"for this schema:\n{schema_to_ddl(schema)}\n"
                "Output ONLY the corrected raw SQL, no markdown."
            ),
        },
        {"role": "user", "content": f"Fix this SQL:\n{fuzzy_sql}"},
    ]
    fixed = ask_llm(fix_messages)
    return jsonify({"exact_sql": _strip_sql(fixed) if fixed else fuzzy_sql})


@app.route('/health', methods=['GET'])
def health_check():
    ollama_ok = False
    try:
        with urllib.request.urlopen(f"{OLLAMA_URL}/api/tags", timeout=3):
            ollama_ok = True
    except Exception:
        pass
    return jsonify({
        "status":      "ok",
        "ollama":      ollama_ok,
        "embed_model": EMBED_MODEL,
        "llm_model":   LLM_MODEL,
        "db_policy":   "read-only on existing tables, write to aiml.vector_kb only",
    })

# ---------------------------------------------------------------------------
# Startup
# ---------------------------------------------------------------------------

def load_resources():
    global synonyms

    # Load synonyms (READ — no modifications)
    if os.path.exists(SYNONYM_PATH):
        with open(SYNONYM_PATH, 'r') as f:
            synonyms = json.load(f)
        print("[startup] Synonyms loaded.")

    # Initialize/Train/Load NLPEngine
    get_nlp_engine()

    # Load office names for entity extractor (SELECT only)
    try:
        conn = get_db_conn()
        cur  = conn.cursor()
        cur.execute("SELECT officename FROM office;")
        offices = [row[0] for row in cur.fetchall()]
        set_known_offices(offices)
        print(f"[startup] Loaded {len(offices)} office(s).")
        cur.close(); conn.close()
    except Exception as exc:
        print(f"[startup] Office load warning: {exc}")

    # Sync nlp_knowledge_base -> aiml.vector_kb (read-only on nlp_knowledge_base)
    sync_kb_to_vector_store()

    print("[startup] AIML service ready.")
    print(f"[startup] Using: {EMBED_MODEL} (embeddings) + {LLM_MODEL} (SQL generation)")
    print("[startup] DB policy: existing tables are read-only; aiml.vector_kb is writable.")


load_resources()
