# Phase 8 — First shared API layer (additive)

This is an **incremental refactor**, not a complete backend rewrite. It adds three PHP files and deliberately does not replace existing UI pages or order logic.

## Prerequisites
- Phase 5 migration `products.is_active` applied to the **local test database**.
- Existing login/session uses `$_SESSION['user_id']` and `$_SESSION['role']`.
- PHP version 8.0+ with mysqli/mysqlnd (matches project setup).
- Existing `config/db.php` working with server environment variables. Never commit DB credentials.

## Endpoints
`GET /api/v1/products/index.php?page=1&limit=20`
- Login as **store** using existing website in the same browser/session.
- 200: `{ "success": true, "data": [...], "pagination": ... }`
- 401: no authenticated session; 403: logged-in role other than store.
- 405: method other than GET; 422: invalid pagination; 500: unexpected DB query failure.
- Read-only and paginated; no request-body inputs or data mutations.
- Existing front-end remains untouched during this phase.

## Test before commit
1. Backup application and database. Copy `api/v1/_bootstrap.php`, `api/v1/products/index.php`, `app/Services/ProductCatalog.php` into project.
2. Confirm `products.is_active` exists; run migrations from previous phases as appropriate in a disposable DB. Do not reapply migrations blindly.
3. Visit API endpoint signed out => 401. Signed in as farmer => 403. As store => 200 with only active/in-stock products.
4. Try `?page=0`, `?limit=101`, `?page=abc` => 422, and POST => 405.
5. Check PHP/web server error logs if API returns 500. If config/db.php exits on connection failure, it can return 503 outside the JSON response structure.
6. Check `git diff --cached` for secrets before committing.

## Known limitations / next refactor
- Does not yet paginate total counts or support filters.
- All existing UI queries remain where they are; adapt pages to consume the API only after testing.
- Existing DB exceptions and status handling need consolidation across more endpoints.
- Session cookies should be configured secure/httponly/samesite at server/bootstrap level for production.
- Authorization here relies on the session role; account suspension after login is not rechecked on each request.
