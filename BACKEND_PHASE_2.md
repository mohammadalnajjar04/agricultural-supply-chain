# Backend phase 2 — safer store order submission

## Changes
- `store/place_order.php`: require CSRF token for order POST.
- Enforce strictly positive integer quantity on server.
- Re-read product stock and price within a database transaction using `SELECT ... FOR UPDATE`.
- Check execute errors and roll back before replying; log server-side errors only.
- Remove duplicate stylesheet links.
- Add `.gitignore` for runtime secrets, uploads and generated files.

## Important limits
- A **pending** order does NOT reserve stock. The farmer approval workflow remains the point at which stock is deducted, so multiple pending orders may exist for the same available stock. Later orders may be rejected on approval.
- `SELECT ... FOR UPDATE` requires an InnoDB transaction. This change is not a full inventory reservation system.
- No schema was migrated and no live MySQL integration test was performed here.

## Manual tests (use a non-production MySQL database)
1. Sign in as a store; place a valid order; it should appear as pending.
2. Post a quantity of 0, negative or non-integer; it should be rejected.
3. Post without a valid CSRF token; it should return HTTP 403.
4. Request more than current stock; should show an error and create no order.
5. Change the product's price in DB after loading the order page, then submit; persisted order should use latest price.
6. Approve an order as the owning farmer, verify stock decreases once and exactly one transport request is created.
7. Reapprove the same order; verify no second deduction and no second transport request.
8. Test two concurrent approvals against the same low-stock product, verifying that stock never becomes negative.

## Important Git hygiene
Check `git status` and `git diff --cached --name-only` before committing. `.gitignore` does not remove already-tracked secrets: use `git rm --cached <file>` (only for the relevant sensitive file) and rotate exposed credentials. Do not upload any real verification documents.
