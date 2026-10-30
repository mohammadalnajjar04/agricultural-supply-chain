# Phase 13 — Transporter requests UI

## Scope
Improves `transporter/available_requests.php` and `transporter/my_requests.php` request cards, adds client-side search, responsive styles, and crucially replaces legacy GET state-changing links with POST forms carrying CSRF tokens.

## Prerequisite
**Phase 7** `transporter/accept_request.php` and `transporter/update_request_status.php` must already be installed, because they require POST + CSRF. `includes/security.php` must expose `csrf_field()` and `require_valid_csrf()`.

## Install
Merge/replace the two transporter PHP files only after comparing to your current development branch; add the CSS and JS. No database migration for this phase.

## Tests
1. Logged-in transporter sees pending unassigned requests only. Search filters cards.
2. Accept using POST, then view the request under My Requests.
3. Start delivery and mark delivered. Check DB order + transport request statuses.
4. A second transporter cannot accept an already claimed request.
5. Unauthenticated sessions cannot accept; invalid CSRF yields HTTP 403.
6. Test Arabic/English, narrow screens, empty lists, and refresh after POST.

**Important:** Frontend form disabling is only a UX improvement and not a concurrency safeguard. Phase 7 backend transactional guards remain the required protection. This patch has not been integration-tested with your actual MySQL database or browser.
