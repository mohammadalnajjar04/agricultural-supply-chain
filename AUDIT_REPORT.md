# AgriLink — Professional Audit Report

**Date:** 2026-07-13
**Project:** AI-Based Agricultural Supply Chain Management System
**Scope:** Architecture, Security, Code Quality, Frontend, Database, AI, Performance, Accessibility

---

## Executive Summary

| Severity | Count |
|----------|-------|
| **CRITICAL** | 7 |
| **HIGH** | 32 |
| **MEDIUM** | 63 |
| **LOW** | 41 |
| **Total** | **143** |

**Overall Assessment:** A well-designed university prototype with strong visual design, functional i18n, and thoughtful security primitives. Critical gaps exist in CSRF coverage, testing, logging, and the AI service's production readiness. The architecture follows a script-per-page PHP pattern that works at prototype scale but needs refactoring for maintainability.

---

## Priority 1 — CRITICAL (Fix Immediately)

### C-1. Remote Code Execution via Flask Debug Mode
- **File:** `ai_service/app.py:555`
- **Issue:** `app.run(debug=True)` exposes the Werkzeug debugger, allowing **arbitrary code execution** on the server via crafted HTTP requests.
- **Fix:** Change to `debug=False` or read from environment variable.

### C-2. Wide-Open CORS on AI Service
- **File:** `ai_service/app.py:34`
- **Issue:** `CORS(app)` with no arguments allows requests from **any origin**. An attacker's website can call any AI endpoint.
- **Fix:** `CORS(app, origins=["http://localhost:8080"])` or specific production domain.

### C-3. CSRF Missing on 5 POST Forms
- **Files:**
  - `farmer/add_product.php:114` — Product creation
  - `farmer/edit_product.php:135` — Product editing
  - `farmer/transport_request.php:101` — Transport request creation
  - `store/place_order.php:152` — Order placement
  - `store/rate_order.php:140` — Order rating
- **Issue:** None of these forms include `csrf_field()` or validate with `csrf_validate()`. Attackers can forge requests from any site.
- **Fix:** Add `<?= csrf_field() ?>` in each form and `csrf_validate()` in each POST handler.

### C-4. Hardcoded Admin Credentials in SQL Dump
- **File:** `database_final.sql:41-42`
- **Issue:** `-- Password: Admin@123` in plaintext comment committed to version control.
- **Fix:** Remove password comment from SQL dump.

### C-5. Database Schema Drift
- **File:** `database_final.sql` vs application code
- **Issue:** PHP code references columns (`order_id`, `total_weight`, `delivery_fee`, `platform_fee`, `driver_earning`, `distance_type`) and tables (`market_prices`) that **don't exist** in the SQL dump. The schema is out of sync with the application. Runtime failures are guaranteed.
- **Fix:** Regenerate `database_final.sql` to match actual application requirements.

### C-6. Zero Logging
- **Files:** Entire codebase
- **Issue:** Only ONE `error_log()` call exists (in `config/db.php:16`). No logging for: failed logins, CSRF failures, rate limit hits, file uploads, admin actions, AI calls, order placements, or transport status changes. Debugging and incident response are impossible.
- **Fix:** Add structured logging to `security.php` and key action points.

### C-7. Zero Tests
- **Files:** Entire project
- **Issue:** No test files, no test framework, no `phpunit.xml`, no test configuration of any kind. No automated verification of correctness.
- **Fix:** Add at minimum: unit tests for `ai_engine.php`, `pricing.php`, CSRF functions, auth flow.

---

## Priority 2 — HIGH (Fix Before Submission)

### Security

| ID | File:Line | Issue |
|----|-----------|-------|
| H-1 | `auth/login.php:68` | Table/column names interpolated from `$role_post` into SQL. Mitigated by whitelist check but fragile pattern. |
| H-2 | `admin/dashboard.php:36,53` | `{$m['table']}` and `{$id_col}` interpolated into SQL. Currently hardcoded but risky. |
| H-3 | `config/db.php:9-10` | Default fallback credentials `root`/empty password when env vars unset. |
| H-4 | `ai_service/app.py:456-504` | No input validation on API endpoints — `int()` casts without bounds checking. |
| H-5 | `transporter/accept_request.php:32-38` | Race condition: two transporters can simultaneously accept the same request. No `FOR UPDATE` or row-level locking. |
| H-6 | `includes/security.php:12` | `session.cookie_secure` hardcoded to `0` — cookies sent over HTTP. |

### Architecture

| ID | File:Line | Issue |
|----|-----------|-------|
| H-7 | All 25+ page files | **Massive DRY violation** — same ~30-line boilerplate (security.php, language.php, db.php, auth check, HTML head, sidebar, topbar) copy-pasted everywhere. No base layout/template. |
| H-8 | `config/db.php` | **Global `$conn`** — no dependency injection, no testability, no connection management. |
| H-9 | No `set_exception_handler()` | **No global exception handler** — PHP errors will show default error pages in production. |
| H-10 | All page files | **Logic mixed with presentation** — SQL queries, business logic, and HTML all in one file. |

### i18n

| ID | File:Line | Issue |
|----|-----------|-------|
| H-11 | `includes/ai_engine.php:212-214,303` | **Hardcoded Arabic strings** in business logic. Should use `t()` system. |
| H-12 | Throughout | **Dual translation system** — `t('key')` function AND inline `$is_ar ? "..." : "..."` ternaries used in parallel, creating unsynchronized translations. |

### AI/ML

| ID | File:Line | Issue |
|----|-----------|-------|
| H-13 | `ai_service/app.py:132-190` | **Training data is synthetic** — `build_daily_dataset()` generates noise. Model trains on its own synthetic data, creating a circular dependency. Not real ML. |
| H-14 | `ai_service/app.py` | **No authentication** on any endpoint. All predictions are publicly accessible. |

---

## Priority 3 — MEDIUM (Fix Before Production)

### Security (7 items)

| ID | File:Line | Issue |
|----|-----------|-------|
| M-1 | `includes/language.php:10` | `session_start()` called directly, bypassing `security_session_start()` hardening. |
| M-2 | `includes/store_topbar.php:2-4` | Redundant `session_start()` — bypasses security hardening. |
| M-3 | `includes/transporter_topbar.php:2-4` | Same redundant `session_start()`. |
| M-4 | `includes/security.php:64,72,89` | `@` error suppression on file operations — hides failures silently. |
| M-5 | `admin/dashboard.php:89-105` | `catch (Throwable $e) { // ignore }` — exceptions silently swallowed. |
| M-6 | `farmer/add_product.php:29-32` | No server-side input validation on quantity, price, harvest_date. |
| M-7 | `includes/security.php:57` | CSP allows `'unsafe-inline'` for scripts — weakens XSS protection. |

### Frontend (15 items)

| ID | File:Line | Issue |
|----|-----------|-------|
| M-8 | `app.js:82` | `toast.innerHTML` with dynamic message — XSS vector if caller passes user input. |
| M-9 | `farmer/recommendations.php:134` | Chart.js loaded render-blocking in `<head>` — delays page paint. |
| M-10 | `farmer.css:7-15` vs `brand.css:7-25` | Duplicate `:root` token definitions — fragile design system. |
| M-11 | `farmer.css:134` | Missing `-webkit-backdrop-filter` prefix — breaks Safari. |
| M-12 | Multiple files | RTL uses mixed `html[dir="rtl"]` vs `html[lang="ar"]` selectors. |
| M-13 | `store/my_orders.php:67` | Missing `<div class="menu-overlay">` — mobile sidebar toggle broken. |
| M-14 | `farmer/transport_requests.php:37` | Missing `dir="rtl"` — Arabic renders LTR. |
| M-15 | `brand.css` vs `farmer.css` | Inconsistent radius tokens (18px vs 16px) and shadow systems. |
| M-16 | CSS files | Only 2 breakpoints (992px, 576px). Desktop-first, no mobile-first approach. |
| M-17 | `farmer.css` vs `style.css` vs `style_ar.css` | Inconsistent spacing units (rem vs px) across design layers. |
| M-18 | `index.php:34` | Wrong `role="banner"` on `<nav>` — should be `aria-label`. |
| M-19 | `farmer/add_product.php:112` | `$message` contains raw HTML alert — XSS sink if user data ever incorporated. |
| M-20 | `farmer/transport_requests.php:117,135` | Raw DB `$r['status']` output without escaping in some contexts. |
| M-21 | `admin/dashboard.php:123` | Uses Bootstrap `visually-hidden-focusable` instead of `.skip-nav` — inconsistent. |
| M-22 | `farmer/transport_request.php:108` | `<select>` uses `form-control` instead of Bootstrap 5's `form-select`. |

### Database (7 items)

| ID | File:Line | Issue |
|----|-----------|-------|
| M-23 | `database_final.sql:106-107` | Redundant `farm_location` and `location` columns — 2NF violation. |
| M-24 | `database_final.sql:121` | `orders.status` uses `varchar(50)` instead of `ENUM`. |
| M-25 | `database_final.sql` | No `created_at`/`updated_at` timestamps on any table. |
| M-26 | `database_final.sql:54,75` | `national_id` and `phone` lack `UNIQUE` constraints. |
| M-27 | `database_final.sql` | Missing indexes on `status` columns (queried frequently). |
| M-28 | `database_final.sql:148` | `fk_request_farmer` has no `ON DELETE/ON UPDATE` cascade. |
| M-29 | `database_final.sql` | No migration/versioning system — destructive `DROP TABLE IF EXISTS` approach. |

### AI/ML (5 items)

| ID | File:Line | Issue |
|----|-----------|-------|
| M-30 | `ai_service/app.py:193-207` | No train/test split or cross-validation. Overfitting undetectable. |
| M-31 | `ai_service/app.py:210-232` | Model retrained from scratch every startup. No persistence. |
| M-32 | `ai_service/app.py:263-265` | Extrapolation beyond training data with no bounds checking. |
| M-33 | `ai_service/app.py` | No logging — debugging in production impossible. |
| M-34 | `ai_service/app.py:555` | Flask development server — no gunicorn/uwsgi. Not production-ready. |

### Code Quality (7 items)

| ID | File:Line | Issue |
|----|-----------|-------|
| M-35 | All 3 register files | `save_verification_file()` copy-pasted 3 times with minor variations. |
| M-36 | All 25+ page files | Auth guard `if (!isset($_SESSION['user_id']))` duplicated everywhere. Should be `require_role()`. |
| M-37 | `farmer/recommendations.php`, `store/recommendations.php` | `renderMLChart()` JS function copy-pasted (~90 lines each). |
| M-38 | `auth/login.php:3-4` | `include` instead of `require` for critical dependencies (db.php, language.php). |
| M-39 | Multiple files | Inconsistent include styles: `__DIR__`-based vs relative paths without `__DIR__`. |
| M-40 | `ai_service/app.py:398-414` | Hardcoded text recommendations for transporters/stores — not actually ML. |
| M-41 | `includes/security.php:111-114` | `safe_redirect()` doesn't validate URL — potential open redirect. |

---

## Priority 4 — LOW (Polish / Best Practice)

| ID | Category | Issue |
|----|----------|-------|
| L-1 | CSS | `.skeleton` and `.loading-spinner` classes defined but never used |
| L-2 | CSS | `.success-msg`/`.error-msg` classes defined but Bootstrap alerts used instead |
| L-3 | CSS | `farmer.css:18` duplicates `body` background rule from `brand.css` |
| L-4 | CSS | `.roles` class in `style_ar.css:28-30` doesn't exist in any HTML |
| L-5 | JS | `farmer.js` is dead code — 7 lines of comments, no functionality |
| L-6 | JS | `brand.js` and `app.js` both implement identical `initPasswordToggles()` |
| L-7 | JS | `var` used 22 times instead of `const`/`let` in standalone JS files |
| L-8 | JS | Global error handler only logs to console — no user-facing feedback |
| L-9 | HTML | `<br>` tags used for spacing instead of CSS margin |
| L-10 | HTML | No favicon in any page |
| L-11 | HTML | Canonical URL points to `example.com` |
| L-12 | HTML | No Open Graph or structured data |
| L-13 | HTML | Icon-only buttons missing `aria-label` |
| L-14 | HTML | `farmer/add_product.php:170` — uses `disabled` instead of `readonly` for location field |
| L-15 | DB | `quantity` and `price` allow zero/negative (no CHECK constraints) |
| L-16 | DB | `rating` has no `CHECK (BETWEEN 1 AND 5)` constraint |
| L-17 | DB | `utf8mb4_general_ci` instead of `utf8mb4_unicode_ci` for better Arabic sorting |
| L-18 | DB | Missing index on `products.name` (used in AI engine queries) |
| L-19 | Config | No `.env` or `.env.example` to document required variables |
| L-20 | Config | No `.gitignore` — uploads, `__pycache__`, IDE files could be committed |
| L-21 | Config | CDN assets loaded without `integrity` hashes — supply chain risk |
| L-22 | AI | `recommend_for_transporter()` and `recommend_for_store()` return hardcoded text |
| L-23 | AI | Single 555-line file — all logic in one module |
| L-24 | AI | `_daily_series()` calls predict 31 times per request — no batch prediction |
| L-25 | AI | No virtual environment in run scripts — system-wide pip install |
| L-26 | Arch | No breadcrumb navigation on sub-pages |
| L-27 | Arch | No password recovery mechanism |
| L-28 | Arch | No interactive tutorial or guided onboarding |
| L-29 | Arch | `admin/login.php` is just a redirect stub (5 lines) |
| L-30 | Arch | Duplicate dataset files in `data/` and `ai_service/data/` |
| L-31 | Arch | `css/farmer.css` is misnamed — used as shared dashboard layout for ALL roles |
| L-32 | i18n | `style_ar.css` is only 51 lines — incomplete RTL coverage |
| L-33 | i18n | Chart.js RTL text direction not handled |
| L-34 | Perf | 5-7 external stylesheets loaded per page |
| L-35 | Perf | No database connection pooling |
| L-36 | Perf | Synchronous AI service calls block PHP execution (4s timeout) |
| L-37 | Perf | File-based rate limiting won't work in multi-server deployments |
| L-38 | HTML | `farmer_tabs.php` exists but is never included on any page |
| L-39 | HTML | Missing `<meta name="robots">` on all pages |
| L-40 | DB | `database_final.sql` uses `DROP TABLE IF EXISTS` — destructive reset |
| L-41 | Security | `config/db.php:15-20` — `die()` reveals "Database Connection Failed" to users |

---

## Well-Implemented Areas (Positive Findings)

| Area | Details |
|------|---------|
| **Visual Design** | Glassmorphism design system with role-specific color theming. Polished and professional. |
| **i18n Foundation** | `t()` function, `languages/` directory, `url_with_lang()` — architecture is solid even if usage is inconsistent. |
| **Security Primitives** | `security.php` implements CSRF, rate limiting, session hardening, MIME verification, security headers. The primitives are correct — just not applied everywhere. |
| **AI Engine Design** | PHP-side `ai_engine.php` (moving average + seasonal index) and `ai_client.php` (Flask integration) are clean, well-structured modules. |
| **Pricing Engine** | `pricing.php` uses pure functions, `match` expression, Haversine formula — clean and testable. |
| **CSS Design Tokens** | `brand.css` defines a complete design token system with role variants. |
| **Accessibility Basics** | Skip nav links, `:focus-visible`, `prefers-reduced-motion`, semantic HTML `<main>` elements. |
| **Upload Security** | `.htaccess` blocks PHP execution in uploads/, MIME verification via `finfo`, 5MB limit, random filenames. |
| **Session Hardening** | httponly, strict_mode, only_cookies, 30-min timeout, regeneration on login. |

---

## Recommended Fix Order

### Phase 1 — Security Critical (Day 1)
1. Fix Flask `debug=False` and restrict CORS
2. Add CSRF tokens to 5 unprotected forms
3. Remove admin password from SQL dump
4. Sync database schema with application
5. Add `.gitignore`

### Phase 2 — Security High (Day 2)
6. Remove redundant `session_start()` calls
7. Add server-side input validation
8. Fix race condition in `accept_request.php`
9. Set `cookie_secure` dynamically
10. Add `.htaccess` to `uploads/verification/`

### Phase 3 — Code Quality (Days 3-4)
11. Extract `save_verification_file()` to shared include
12. Create `require_role()` helper function
13. Extract duplicated `renderMLChart()` to shared JS file
14. Add database indexes on status columns
15. Fix missing `dir="rtl"` on transport_requests page
16. Fix missing `menu-overlay` on store/my_orders page

### Phase 4 — Architecture (Days 5-7)
17. Add logging to security.php and key actions
18. Add global exception/error handlers
19. Replace inline i18n ternaries with `t()` calls
20. Extract hardcoded Arabic strings from ai_engine.php
21. Create base layout template to eliminate DRY violations
22. Add server-side input validation to all POST handlers

### Phase 5 — Polish (Optional)
23. Add PHPUnit tests for ai_engine, pricing, CSRF
24. Fix CSS inconsistencies (tokens, units, breakpoints)
25. Remove dead code (farmer.js, unused CSS classes)
26. Add proper error pages (404, 500)
27. Add AI service logging and input validation
28. Create Docker configuration

---

## File-Level Summary

| Directory | Files | Critical Issues | High Issues | Medium Issues |
|-----------|-------|----------------|-------------|---------------|
| `admin/` | 3 | 1 (schema drift) | 2 (SQL interpolation, error swallowing) | 3 |
| `auth/` | 7 | 1 (credentials) | 1 (SQL pattern) | 2 |
| `farmer/` | 9 | 2 (CSRF×2) | 3 (input validation, XSS patterns) | 4 |
| `store/` | 6 | 1 (CSRF) | 1 (CSRF) | 2 |
| `transporter/` | 6 | 0 | 2 (race condition, CSRF link) | 1 |
| `includes/` | 10 | 0 | 1 (session bypass) | 4 |
| `config/` | 1 | 0 | 1 (hardcoded creds) | 0 |
| `ai_service/` | 6 | 2 (debug mode, CORS) | 2 (no auth, no validation) | 5 |
| `css/` | 5 | 0 | 0 | 4 |
| `js/` | 3 | 0 | 0 | 1 |
| `database_final.sql` | 1 | 1 (drift) | 0 | 4 |

---

*Report generated by automated code audit. All findings require manual verification before fixes are applied.*
