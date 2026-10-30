# Phase 3 — order approval & inventory integrity

## Changes
- Farmer approval/rejection uses conditional pending-state updates and checks affected_rows.
- Approval, stock deduction and transport request creation remain in the same MySQL transaction; failures trigger rollback.
- Fix action button sizing in order cards.
- Optional migration includes a duplicate-request preflight and a commented unique-key DDL.

## Install
1. Back up the existing project and MySQL database.
2. Replace `farmer/store_orders.php` on the development branch.
3. Add `database/migrations/003_unique_transport_order.sql` (do **not** import it automatically).
4. The project requires the prior security patch (`includes/security.php`) and MySQL InnoDB tables.
5. Review duplicate transport requests before manually enabling the UNIQUE index.

## Manual acceptance checks (on local development database)
- Create order and approve it: exactly one transport request, product stock reduced once.
- Reject a pending order: stock unchanged; no transport request.
- Approve same order twice: second attempt denied; no extra stock decrement.
- Create two pending orders that collectively exceed stock: approve one, second must fail if not enough stock.
- Force transport insert failure on a disposable test DB: stock and order status must roll back.
- Confirm a farmer cannot approve another farmer's order.

## Limitations
This patch is source-level only; no live MySQL integration test was run. It does not implement stock reservation for pending orders or fully redesign the schema.
