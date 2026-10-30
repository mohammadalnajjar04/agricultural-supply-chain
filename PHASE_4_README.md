# Phase 4 — Data retention and foreign-key hardening

**This is a proposed, opt-in SQL migration, not a tested production deployment.** It was built from `database/agri_supply_chain_FINAL.sql` in the original supplied repository, not from your live MySQL database.

## Purpose

- Protect historical `orders` and `transport_requests` from being removed through `ON DELETE CASCADE` when deleting a farmer, store, transporter, or product.
- Prevent deleting an `orders` row while linked to a `transport_requests` row (previous behavior was `SET NULL`).
- Add useful dashboard query indexes.

## Install safely (in this exact order)

1. Ensure phases 1–3 are copied locally; keep this patch separate and on `development`.
2. **Back up** the MySQL database first. Example (PowerShell): `mysqldump -u root -p --single-transaction agri_supply_chain > backup_before_phase4.sql` (run on a machine that has the MySQL CLI). Protect the backup because it may contain personal data; do not commit it.
3. In phpMyAdmin or MySQL, select the **development** database. Run `004_preflight_readonly.sql`, inspect results, and compare constraint/index names with the real schema using `SHOW CREATE TABLE orders;`, `SHOW CREATE TABLE transport_requests;`, `SHOW CREATE TABLE products;` and `SHOW INDEX FROM orders;` / `SHOW INDEX FROM transport_requests;`.
4. If live constraint names differ, or index names already exist, adjust the migration before running. If prior data is inconsistent, stop. The query for duplicate transport requests must be empty before separately enabling phase 3's UNIQUE constraint.
5. Run `004_preserve_order_history.sql` only after reviewing impacts. **MySQL DDL auto-commits**; if a statement fails midway, earlier statements will remain applied. Restore from backup / use forward corrections rather than assuming ROLLBACK will restore everything.
6. Verify: add and accept a test order. Try deleting a product with linked orders: deletion should be **blocked** with a FK error (the application should eventually show a helpful error or perform soft delete). Confirm that order and delivery remain intact.

## Important limitations

- This migration deliberately **does not** change PHP controllers. Older hard-delete features for linked data may now show SQL errors; do not deploy it before adapting them.
- The schema still keeps `orders.product_id` and current product price; order history does not yet store immutable product-name / per-unit-price snapshots. Next phase should add snapshot fields and update order creation safely.
- This does **not** fix concurrent inventory reservation or comprehensive authorization, nor test against a live MySQL instance.
- Do **not** re-import `agri_supply_chain_FINAL.sql` on a live populated database: it drops tables and recreates sample data.

## Commit only the migration files

```powershell
cd C:\Projects\agricultural-supply-chain
git switch development
git status
git add database/migrations/004_preflight_readonly.sql database/migrations/004_preserve_order_history.sql PHASE_4_README.md
git diff --cached --check
git diff --cached --stat
git commit -m "db: add opt-in foreign key history protection migration"
git push origin development
```

Adding migration files to Git is **not the same** as executing them against MySQL.
