# Phase 9 — Shared authentication session helpers

## Install
Copy the paths into the repository root after comparing existing edits. This patch changes `auth/login.php`, `auth/logout.php`, `api/v1/_bootstrap.php` and adds `includes/auth_session.php`. The login file is based on the **Phase 1 security version**. Compare with your current `development` branch before replacement.

## What changed
- Login regenerates session ID, resets any previous identity, and rotates CSRF token.
- Session cookie defaults to HttpOnly, SameSite=Lax, session-only lifetime; Secure is enabled on HTTPS (localhost HTTP still works).
- Logout invalidates server session and expires browser session cookie.
- Phase 8 API uses shared login/role validation.

## Test before committing
1. Farmer, store, transporter, and admin login; approved and unapproved accounts.
2. API unauthenticated 401, wrong-role 403, store 200.
3. Logout then refresh any protected page and API: must deny access.
4. Arabic language selection persists after login.
5. CSRF-protected forms still work after new login; stale tokens must fail.
6. Test with HTTPS in staging before production.

## Important limitations
- Existing role-protected HTML pages are NOT yet migrated to the shared helper: audit those separately.
- No rate limiting, MFA, account lockout, authorization policy audit, or database-session revocation yet.
- An already active PHP session started elsewhere retains its previously configured cookie settings; include `auth_session.php` first on newly refactored pages.
- This patch has only undergone PHP syntax checks, NOT live browser / DB tests.
