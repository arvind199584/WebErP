# Security Audit Report - ModPyPhp

**Date:** June 1, 2026  
**Auditor:** Gemini CLI  
**Scope:** Core Architecture, Authentication, Access Control, AI/NLP Integration, and Database Security.

---

## 1. Executive Summary
ModPyPhp demonstrates a strong security-first mindset with features like Row Level Security (RLS), global CSRF protection, and consistent output escaping. However, several critical architectural flaws undermine these protections, most notably the use of a database superuser for application connections and a lack of ownership verification in the PHP layer.

---

## 2. Vulnerability Assessment

### 🚨 Critical: Row Level Security (RLS) Bypass
*   **Discovery:** The application connects to PostgreSQL using the `postgres` account (defined in `.env`).
*   **Impact:** In PostgreSQL, superusers and table owners bypass RLS policies by default. Although isolation policies are defined (e.g., `agreement_isolation_policy`), they are **not enforced**.
*   **Risk:** A user from one office can access or modify data belonging to another office if they can find the record ID.

### 🔴 High: Widespread IDOR (Insecure Direct Object Reference)
*   **Discovery:** Controllers (e.g., `AgencyController`, `AgreementController`) fetch records by ID from the request without verifying if the record belongs to the current user's `officeid`.
*   **Impact:** Combined with the RLS bypass, any authenticated user can view or edit sensitive documents (Agreements, Bills, Employee records) across the entire system by simply changing the `id` parameter in the URL.
*   **Examples:** `?module=Agreement&action=showEditForm&id=4`

### 🔴 High: Dangerous Administrative Tools (`SQLRunnerController`)
*   **Discovery:** The `SQLRunnerController` allows superusers to execute arbitrary `SELECT` and `UPDATE` queries.
*   **Impact:** While restricted to superusers, there are no guardrails. A compromised superuser account or a disgruntled administrator could wipe or manipulate the entire database. The "SELECT/UPDATE only" check is easily bypassed or abused.

### 🟡 Medium: AI Query Safety (Blacklist Bypass)
*   **Discovery:** `NLPService.php` uses a blacklist (`INSERT`, `DROP`, etc.) to filter AI-generated SQL.
*   **Impact:** Blacklists are historically unreliable. Obscure SQL syntax, comments (`/* ... */`), or missing keywords (e.g., `COPY`, `COMMENT ON`, `ANALYZE`) could be used to perform unauthorized actions or leak system information.
*   **Risk:** Indirect SQL injection via the AI interface.

### 🟡 Medium: Information Leakage in AI Error Responses
*   **Discovery:** The `NLPService` returns raw PDO/PostgreSQL exception messages to the frontend when a query fails.
*   **Impact:** These messages often contain schema details (table/column names) and logical hints that assist an attacker in mapping the database for further exploits.

### 🔵 Low: Weak Password Policy & Rate Limiting
*   **Discovery:** The system uses `PASSWORD_BCRYPT` (good), but lacks complexity requirements (length, symbols, etc.) and has no account lockout or rate-limiting on the login endpoint.
*   **Risk:** Vulnerability to brute-force or credential stuffing attacks.

---

## 3. Remediation Recommendations

1.  **Immediate: Downgrade Database User.**
    *   Create a dedicated application user in PostgreSQL that is NOT a superuser.
    *   Grant only necessary permissions (`SELECT`, `INSERT`, `UPDATE`) to this user.
    *   Ensure RLS is enabled and correctly enforced for this non-owner user.
2.  **Code-Level Ownership Checks:**
    *   Update all `Models` to include `officeid = :officeId` in the `WHERE` clause for every query.
    *   Validate that the requested resource belongs to the session user's office before rendering views or processing updates.
3.  **Refactor SQL Runner:**
    *   Replace arbitrary SQL execution with specific administrative actions or a strictly whitelisted query builder.
4.  **Improve AI Safety:**
    *   Transition from a blacklist to a whitelist approach for AI-generated SQL.
    *   Use a separate, read-only database connection for AI queries.
5.  **Sanitize Errors:**
    *   Log detailed errors to the server but return generic "An error occurred" messages to the UI.
6.  **Implement Rate Limiting:**
    *   Add a delay or lockout mechanism after multiple failed login attempts.
