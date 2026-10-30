# Phase 14 — Admin review UI + safer approvals

This patch modifies **only the admin account-verification dashboard**. It is based on the originally uploaded project, with the shared `includes/auth_session.php` helper from phase 9 required. Compare with your latest `development` version before replacing anything.

## Files
- `admin/dashboard.php` replace after diff review
- `css/admin-v2.css` new
- `js/admin-v2.js` new

## Changes
- Approve/reject is POST-only with CSRF verification; no state change on GET.
- UPDATE requires `status = 'pending'` to avoid re-processing stale forms.
- Server-side role/id/action allow-list; SQL update uses prepared parameters.
- Clearer tables, client-side search, responsive styles.

## Not included / security caveats
- This does **not** deliver full admin management of users/products/orders or a new analytics backend.
- Existing verification-document URLs are still directly linked. If those files contain IDs or private documents, they must be moved outside public webroot and served through an authenticated, authorized download endpoint. Do not push those documents to GitHub.
- The dashboard still displays pending account email and phone numbers; limit admin access.
- This change does not audit historical approvals; an audit log is recommended.
- A live MySQL/browser test has NOT been run. PHP syntax checks only.

## Before pushing
1. Check `includes/auth_session.php` exists and all phase 9 changes are in place.
2. Confirm `git diff` for `admin/dashboard.php`, keeping any newer customization.
3. Log in as admin and confirm pending accounts appear.
4. Approve one pending account: only that account becomes approved; it disappears from pending list.
5. Confirm refresh does not repeat approval and GET `?action=reject&role=farmer&id=1` changes nothing.
6. Try rejecting another pending account and simulate expired CSRF token: update must fail.
7. Check Arabic/English and mobile layout.
8. Review verification docs/privacy before deployment.

No database migration required for this patch.
