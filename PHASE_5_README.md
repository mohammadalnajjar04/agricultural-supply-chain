# Phase 5 — Product visibility without deleting order history

**Source baseline:** the Phase 2 version of the code, plus compatible Phase 1 security helpers. Phase 3 and 4 are distinct files/migrations. Verify local changes with `git diff` before applying.

## Deployment order
1. Back up database and PHP files; test on a disposable local copy.
2. Apply `database/migrations/005_products_soft_delete.sql` once in MySQL. If you have not applied phase 4, review the migration order and foreign keys first.
3. Replace six PHP files in `farmer/` and `store/`.
4. Smoke test product create/edit/hide, visibility in store, pending orders on hidden products, and historic orders.

## Behavior
- A farmer's existing Delete button now marks a product inactive. The product remains in DB and in historical order joins.
- Store catalog and new order placement exclude hidden products.
- A transaction also rechecks active status to avoid a stale product page creating orders.
- Farmer product list displays inactive products with a badge. Re-activation is not implemented.
- CSRF protection added to add/edit product forms.

## Caveats
- DB schema must be migrated **before** deploying updated PHP.
- No live MySQL integration test was run here.
- Other dashboards, admin APIs, AI queries and cached listings may still display hidden products; audit those routes before production.
- Pre-existing pending orders are NOT automatically canceled on hide; define cancellation/reservation policy first.
- Snapshotting product names/unit prices and full inventory ledgers are future work, not in this patch.
- Historic secrets already committed to Git need rotation and removal from history if sensitive.
