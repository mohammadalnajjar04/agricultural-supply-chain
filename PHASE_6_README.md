# Phase 6 — Order price/name snapshot + stock approval ledger

## Baseline and compatibility
- `store/place_order.php` derives from **Phase 5** (which derives from Phase 2).
- `farmer/store_orders.php` derives from **Phase 3**.
- If you edited either file locally after those phases, compare and merge rather than blindly overwrite.
- Requires Phase 5 `products.is_active`, and the Phase 1 `includes/security.php`.

## Preflight (mandatory)
1. Ensure Phase 3 and Phase 5 fixes have been copied locally. Take a full DB backup and copy your PHP files before replacement.
2. In a test DB check `SHOW CREATE TABLE orders; SHOW CREATE TABLE products;` and verify table/column names match.
3. Ensure `orders.product_name_at_order`, `orders.unit_price_at_order` and `inventory_movements` **do not already exist**. The migration is *one-time* and not idempotent.
4. Apply `database/migrations/006_order_snapshots_inventory_movements.sql` on a local disposable DB first, then on the target database if it succeeds. Apply DB migration before PHP deployment.
5. Replace only the two PHP files and run syntax checks.

## Tests
- New pending order stores product name/unit price as server-side snapshot; edit product afterwards and verify snapshots stay unchanged in SQL.
- Approve once: product stock goes down by requested quantity and precisely one `inventory_movements` row appears with negative `quantity_change`.
- Repeat approval: it must fail with no additional row or stock subtraction.
- Insufficient stock: request remains pending, no movement recorded.
- Rejection: no stock change or movement.
- Force an error in delivery creation on a disposable database: stock subtraction, movement, and status must all roll back.

## Important limits
- Old order names are backfilled from the CURRENT product name (not necessarily the original name). Old unit prices are estimated from order total/quantity, assuming total excludes delivery fee. This cannot restore historically changed data.
- New snapshots are stored but existing dashboards still show the linked live product name until their queries are changed in a follow-up.
- Ledger currently logs *farmer approvals only*, NOT manual stock edits, creation, cancellation, return, or reconciliation. Do not interpret it as a complete stock audit history yet.
- Pending orders are not stock reservations. Concurrent pending requests may exceed available stock, but approval locks and checks stock.
- These PHP files were syntax checked, not tested against a running MySQL service in this environment.
- Database schema updates are NOT performed by Git push. Do not commit environment credentials or real user/customer data.
