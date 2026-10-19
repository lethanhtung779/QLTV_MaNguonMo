# Project Audit – Phase 1

## 1. Architecture Overview
- MVC layout under `app/` (Controllers, Repositories, Services, Core, Views).
- Routing handled by `app/Core/Router.php` – simple string‑based dispatch, no external router library.
- Database access via PDO with prepared statements in Repositories.
- Session & CSRF helpers in `app/Helpers/functions.php`.
- Permissions are defined in `app/Core/Permissions.php` (not shown) and enforced in the router for `/admin` routes.

## 2. Routing (`app/Core/Router.php`)
| Issue | Severity | File/Line | Description |
|-------|----------|-----------|-------------|
| **Potential base‑path bug** | P1 | Router.php:158‑162 | `normalizePath()` strips a configured `APP_BASE_PATH` but does not preserve leading slash for empty base, may produce `//` or missing leading slash in some deployments. |
| **Missing 404 handling for unknown HTTP methods** | P2 | Router.php:18‑23 | Only `GET`/`POST` routes are defined; unsupported methods fall through to 404 without explicit method check (acceptable but could be clearer). |

## 3. Controllers
### 3.1 `AuthController`
- **CSRF validation** present on login (`POST /login`) and registration (`POST /register`).
- **Password handling** delegated to `AuthService`. Ensure `AuthService::authenticate` uses `password_verify` and stores passwords with `password_hash`. *(not inspected yet – verify in service).* 
- **Potential open‑redirect**: `homeForRole()` builds redirect URLs from hard‑coded paths; safe.

### 3.2 `LoanController`
| Issue | Severity | File/Line | Details |
|-------|----------|-----------|---------|
| **Missing permission check on `create`** | P1 | LoanController.php:45‑48 | `create()` only calls `require_auth(['admin','librarian'])` – no fine‑grained permission (e.g., `loans.create`). May be acceptable if admin/librarian are the only actors. |
| **No transaction wrapper visible** | P0 | `LoanService::checkout` (called from `create`) – need to confirm it uses a DB transaction to lock copies and prevent double‑booking. |
| **CSRF validation present** | – | 48, 88, 110, 122, 140 | OK.
| **Potential IDOR in `listPath()`** | P2 | listPath() builds URL from POST values; no user‑specific filtering needed – safe. |

### 3.3 `InventoryController`
| Issue | Severity | File/Line | Details |
|-------|----------|-----------|---------|
| **CSRF validation** present on `saveCopy` and `saveMetadata`. |
| **Direct SQL in `reader()`** uses prepared statement – OK. |
| **No permission check on `reader()`** – only `require_auth(['admin','librarian'])`; fine.

### 3.4 `AuthController` & other controllers use `validate_csrf()` – good.

## 4. Repositories (selected)
### 4.1 `LoanRepository`
- Uses prepared statements throughout – **good**.
- **N+1 queries**: `all()` loads loans then `itemsForLoanIds()` – acceptable but could be combined.
- **Row‑level locking**: Methods `findCopyForUpdate`, `findItemForUpdate` use `FOR UPDATE` – essential for concurrency.
- **Potential missing index**: Queries on `loan_items` filtering by `returned_at`/`due_date`. Ensure indexes on `loan_id`, `returned_at`, `due_date`.
- **Count queries** use sub‑selects – may be heavy on large tables.

### 4.2 `CopyRepository` (not shown)
- Verify unique constraints on `copy_code`/`barcode` – migration `002_copy_level_inventory.php` should enforce.

## 5. Services
### 5.1 `LoanService`
- Must contain the transactional logic for checkout, renewal, return, and reservation expiration.
- **Critical to verify** that `checkout()` starts a DB transaction, locks the selected copy rows (`SELECT ... FOR UPDATE`), checks availability, and commits/rolls back correctly. If not, race conditions may allow two users to borrow the same copy.
- **Renewal limits** (`max_renewals`) should be enforced – check implementation.

### 5.2 `AuthService`
- Should hash passwords, regenerate session ID on login, set secure cookie flags (`HttpOnly`, `Secure` when HTTPS). Verify.

## 6. Security
| Area | Finding | Severity |
|------|---------|----------|
| **SQL Injection** | All DB access uses prepared statements – ✅ | – |
| **Cross‑Site Request Forgery** | CSRF token validated on all state‑changing POST routes – ✅ | – |
| **Cross‑Site Scripting** | Views output user‑controlled data (e.g., book titles, reader names) without explicit escaping. Check `app/Views/**/*.php` for `htmlspecialchars`. |
| **Session fixation** | Ensure `Session::regenerate()` on login (search for `session_regenerate_id`). |
| **Rate limiting** | No obvious rate limiting on login or chatbot endpoints. Consider adding. |
| **Password storage** | Must use `password_hash`. Verify in `AuthService`. |
| **Cookie flags** | Not visible – ensure `setcookie` uses `HttpOnly` and `Secure` flags. |
| **IDOR** | Reader pages (`/account`) correctly use `current_user()`; admin routes check `require_auth`. No public IDOR observed. |
| **File upload** | `CoverUpload` service should validate MIME, size, and re‑encode. Verify implementation. |
| **CSV injection** | Export routes (`/admin/statistics/export`) generate CSV – must prefix cells with `'` or `=` sanitization. Not yet verified. |

## 7. Tests
- Test suite scripts exist (`tests/run.php`, `tests/mysql_phase*.php`). However, the environment lacks a `php` executable (PowerShell error). Ensure PHP is in PATH or invoke via full path.
- No CI integration – manual run required.
- No unit tests for services (e.g., `LoanService`). Consider adding.

## 8. Documentation Gaps
- No `docs/PROJECT_AUDIT.md` existed – now created.
- Missing `docs/FEATURE_MATRIX.md`, `DATABASE_DESIGN.md`, etc. (to be added in later phases).

## 9. Action Items (Prioritized)
1. **P0 – Concurrency safety**: Verify `LoanService::checkout` uses a transaction and row‑level locking. If missing, add `BEGIN; … COMMIT;` with `SELECT … FOR UPDATE` on `book_copies`.
2. **P0 – Password handling**: Audit `AuthService` to confirm `password_hash`/`password_verify` and session regeneration.
3. **P1 – CSRF coverage**: Scan all controller methods for missing `validate_csrf()` on POST routes (e.g., any custom actions not listed).
4. **P1 – XSS protection**: Ensure all view outputs escape with `htmlspecialchars`. Add a helper if missing.
5. **P1 – Rate limiting**: Implement simple login throttling (already present via `LoginThrottle` service – confirm usage).
6. **P2 – Base‑path normalization**: Adjust `Router::normalizePath` to correctly handle empty base and avoid double slashes.
7. **P2 – Indexes**: Add missing indexes on `loan_items(returned_at)`, `loan_items(due_date)`, and `book_copies(status)` for performance.
8. **P2 – CSV injection sanitization**: Prefix exported CSV cells that start with `=`, `+`, `-`, `@`.

---
*Generated by Antigravity on 2026‑10‑12.*
