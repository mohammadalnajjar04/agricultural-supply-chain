# Phase 11 — Store Product Catalog and Checkout UI

## What changes
- Improves the existing catalog page with responsive cards, keyboard-accessible search and price sorting.
- Search and sorting operate **only on the products already loaded** by the page; there is no server pagination.
- Adds an estimated product subtotal to the checkout page. This number is **display-only** and excludes delivery.
- All pricing, stock checks, CSRF protection and order inserts continue to be enforced by `store/place_order.php` on the server. The browser estimate is never submitted as an authoritative price.

## Sources / compatibility
- `store/available_products.php` is based on the **Phase 5** patch (requires `products.is_active`).
- `store/place_order.php` is based on the **Phase 6** patch (requires the Phase 6 order-snapshot columns and `includes/security.php`).
- Phase 9 changes were not applied to these pages, so compare and merge with the current `development` files before overwriting. No SQL migrations are included in Phase 11.

## Install
1. Back up and compare the current store pages with this patch (`git diff --no-index` is one option).
2. Add `css/store-catalog-v2.css` and `js/store-catalog-v2.js`.
3. Merge the two PHP pages, preserving any newer fixes in your local development branch.
4. Test catalog in Arabic and English, desktop and mobile, search and sort, valid/invalid order submissions, tampered quantity, out-of-stock products, and sessions / CSRF.
5. Run PHP syntax checks on the two pages. Test live PHP+MySQL locally before committing.

## Limitations
- No product image field is established in the original schema; this patch does not invent image storage or use arbitrary pictures.
- Not independently tested against a live database or browser. No proof that all phases are applied to your current working tree.
