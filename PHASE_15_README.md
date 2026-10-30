# Phase 15 — Integration audit (non-destructive)

## Why this phase is different
We do NOT ship another replacement PHP page until the real `development` branch passes integration checks. The phase ZIPs were generated separately; we do **not** have your current Git working tree. Do not apply the diagnostic overlay as a deployable codebase.

## Evidence from available packages
- A reconstructed diagnostic snapshot from security-pass + phases 2–14 contains **54 PHP files**. PHP CLI syntax failures: **0**. This is syntax-only, NOT live behavior confirmation.
- File collisions (last package wins in a naive unzip):
  - `store/place_order.php`: phases 2, 5, 6, 11
  - `farmer/store_orders.php`: phases 3, 6
  - `farmer/add_product.php`: phases 5, 12
  - `farmer/edit_product.php`: phases 5, 12
  - `farmer/my_products.php`: phases 5, 12
  - `store/available_products.php`: phases 5, 11
  - `api/v1/_bootstrap.php`: phases 8, 9
- The phase 4 foreign-key migration may break old hard-delete pages; confirm their behavior.
- Phase 5 `products.is_active` and phase 6 order snapshot/ledger schema are required by later PHP files.
- Phase 6 stock ledger records approvals, NOT all manual quantity edits.
- Phase 14 account verification-document URLs may still expose personal documents; access control must be checked before deployment.

## Local checklist — do not skip
1. Back up your MySQL database and copy your `development` checkout; **never use live customer data in test DB**.
2. Run `git switch development` and `git pull --ff-only` from the repository root (only when local work is committed/clean).
3. Run `python tools/audit_local.py --root .` (Python 3 + PHP CLI must be installed). It is read-only; review its findings. `php` may not be available on Windows PATH; use your PHP executable if needed.
4. Inspect `git ls-files .env uploads/verification`; both should show **no tracked secrets/private documents**. If tracked, stop; removing them from the latest commit alone does not erase Git history.
5. Inspect actual schema with `SHOW CREATE TABLE products; SHOW CREATE TABLE orders; SHOW CREATE TABLE transport_requests;` and check applied migrations 003–006 on a disposable test database. Migration files being committed to Git does **not** mean the DB was migrated. Don't rerun a migration blindly.
6. Test matrix (two sessions / separate browsers where noted):
   - Farmer, store, transporter, admin login/logout and unauthorized cross-role access (403 or redirect).
   - Admin approves/rejects only pending accounts; GET does not mutate state.
   - Farmer creates/edits/hides products; hidden product cannot be ordered.
   - Store creates order with valid quantity and rejects bad quantity, hidden product, forged price.
   - Farmer approves order exactly once; stock decreases once and one ledger event is recorded.
   - Approval fails safely with insufficient stock; no partial delivery request or ledger event.
   - Two transporters race to accept one pending shipment; exactly one should win.
   - Transporter follows accepted → in_progress → delivered; cross-user transition rejected.
   - Store cannot browse private verification docs without authorization.
   - Search/sorting and Arabic/English on desktop + mobile.
7. Run tests on a copy of the real `development` files, not on the diagnostic overlay; record evidence and failures. Fix one failure at a time with a focused commit.

## Contents
- `tools/audit_local.py`: read-only PHP lint, required-file checks, and potential secret flags.
- `PHASE_15_STATIC_AUDIT.json`: diagnostics from reconstructed ZIP snapshot, **not** the current GitHub branch.

## Git (after the script succeeds and you review changes)
```powershell
cd C:\Projects\agricultural-supply-chain
git switch development
python tools/audit_local.py --root .
git add tools/audit_local.py PHASE_15_README.md PHASE_15_STATIC_AUDIT.json
git diff --cached --check
git diff --cached --stat
git commit -m "test: add phase 15 static integration audit checklist"
git push origin development
```
Do not commit `audit_snapshot/`, databases, `.env`, or verification uploads.
