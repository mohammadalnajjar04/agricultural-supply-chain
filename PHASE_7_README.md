# Phase 7 — Transport & delivery status integrity

## Scope
Replace only `transporter/accept_request.php` and `transporter/update_request_status.php`.
Baseline: Phase 1 security (`includes/security.php`) plus the existing transport status enum and linked order status values in `database/agri_supply_chain_FINAL.sql`.

## Improvements
- POST + CSRF + transporter role checks.
- Row lock (`FOR UPDATE`) and a transaction before accepting or advancing delivery.
- Reject a second transporter attempting acceptance, or duplicate start/deliver submission.
- For an order-linked delivery, verify that the linked order is in a compatible state; update both statuses in one transaction.
- Require `accepted -> in_progress -> delivered` (no skipping pickup/start).
- Roll back on DB errors and log server-side; avoid reporting success after exceptions.
- Farmer-created transport requests without an `order_id` continue to work without changing an order.

## Installation
1. Back up your project and database. Compare both PHP files with your current branch (do not overwrite later local edits).
2. No database migration is included in this patch. Verify transport_requests.status includes `pending,accepted,in_progress,delivered` and that order statuses include `approved,in_progress,delivered`.
3. Confirm Phase 1 CSRF forms are live in `transporter/available_requests.php` and `transporter/my_requests.php`.
4. Copy the two files and check `php -l`.
5. Test on a nonproduction database before uploading.

## Integration tests (must run locally)
1. Farmer approves store order; associated transport request is `pending` and order `approved`.
2. Transporter A accepts: transport `accepted`, order `in_progress`.
3. Transporter B tries to accept same request: rejected; A remains assigned.
4. A starts: transport `in_progress`; repeating start should fail with no change.
5. A delivers: transport `delivered`, order `delivered`, delivery_date populated; repeating delivery fails.
6. Other transporter attempts status update: rejected.
7. Start before accept and deliver before start: both rejected.
8. Nonlinked manual transport request (`order_id IS NULL`) follows transport statuses without linked order update.
9. If a linked order was cancelled or otherwise moved to an incompatible state, acceptance/status update rejects.
10. Simulate forced DB failure in staging and verify rollback.

## Known limitations and next steps
- No signature, OTP or store-side proof of delivery. Driver marking delivered is not proof the store received goods.
- No GPS tracking, notifications, transport payment settlement or dispute process.
- `my_requests.php` only offers start when accepted and deliver when in_progress.
- State machine currently uses existing VARCHAR/ENUM statuses; a normalized transport event log would make a better full audit trail.
- Full concurrent DB integration test has NOT been run; only PHP syntax checks passed.
- If local order status semantics differ from the schema, adapt status mapping instead of deploying blindly.
