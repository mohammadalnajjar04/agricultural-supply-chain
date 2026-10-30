# Phase 12 — Farmer Product Management UI

## Contents
- `farmer/my_products.php`: refreshed product listing, client-side search/status filter, status badges, safe output escaping, existing hide action and CSRF preserved.
- `farmer/add_product.php` and `farmer/edit_product.php`: layout improvements, no intentional business-logic changes.
- `css/farmer-products-v2.css`: responsive page and form styling.
- `js/farmer-products-v2.js`: local filtering (no API access).

## Prerequisites
- Phase 5 product `is_active` database migration applied.
- `includes/security.php` with `csrf_field()` and existing navigation includes.
- Files here are derived from the **Phase 5 PHP files**, NOT from your current GitHub development branch. Review `git diff` and merge carefully: later edits may be overwritten if you replace blindly.

## Deploy
1. Save your current files/commit; use development branch.
2. Compare `farmer/*.php` with current versions and merge the page presentation while preserving any newer business logic.
3. Add CSS/JS files.
4. Test with farmer account: add, edit, search, filter, hide, and old order history.
5. Test AR/EN, desktop/mobile, permissions and CSRF.

## Known limitations
- No DB migration in Phase 12.
- Hidden items are not reactivated by this UI (existing behavior maintained).
- This is a UI update, not a complete stock-management solution: direct quantity edits should eventually create auditable inventory movements.
- PHP syntax lint passed; runtime DB, browser and integration tests were not performed.

## Git
```
git switch development
git status
git add farmer/my_products.php farmer/add_product.php farmer/edit_product.php css/farmer-products-v2.css js/farmer-products-v2.js PHASE_12_README.md
git diff --cached --check
git diff --cached --stat
git commit -m "feat: improve farmer product management UI"
git push origin development
```
