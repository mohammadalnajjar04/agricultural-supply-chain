# Agricultural Supply Chain — First Security Pass & Rebuild Roadmap

## Changes made in this working copy
- Removed a hard-coded database credential. Configure `AGRI_DB_PASSWORD` on the server; optional `AGRI_DB_HOST`, `AGRI_DB_USER`, `AGRI_DB_NAME`.
- Avoid exposing raw database connection errors to users.
- Rotate session IDs on successful login.
- Changed product deletion, farmer order approval/rejection, accepting transport, and updating delivery statuses to POST forms with CSRF tokens.
- Reject stale/raced transporter acceptance so it cannot update an unrelated order after another transporter accepted.
- Removed uploaded identity-verification JPGs from the distributable archive (back up securely outside the public web root).

## Critical actions before deployment
1. Rotate the previously committed MySQL password immediately, including any reused credentials.
2. Configure PHP environment variables via your local web server or deployment platform. Do not commit secrets.
3. Remove demo accounts and passwords from production, and do not import test users into production.
4. Run end-to-end checks with a disposable test database: signup/approval, product lifecycle, order approval, stock concurrency, transport acceptance, delivery completion, reviews.
5. Move private verification documents outside the web root; require authenticated authorization for downloads.
6. Add CSRF protection to EVERY other POST and handle all file uploads strictly. This pass protects selected critical mutation routes, not the whole application.
7. Review cascading foreign-key deletes: deleting a product or account can erase order/transport history.
8. Add integration tests, backup/restore exercises, logging, role/permission matrix, transactions and idempotency.

## Recommended architecture
- Keep PHP/MySQL initially to reduce rewrite risk; introduce a clear controllers/services/repositories boundary, versioned JSON API, validated request DTOs and centralized authorization.
- Redesign database using explicit order items, stock reservations or inventory movements, shipments, status history, payments/settlements (if needed), audit events, and soft deletes for products/users.
- Adopt a shared bilingual responsive design system and role-based dashboards after locking down critical workflows.
- Validate training datasets, provenance, time splits, baselines and AI confidence; avoid presenting synthetic forecasts as facts.

## Limitations
Static review and PHP lint only (if PHP CLI available). No live DB, browser, load or integration tests were performed. These patches are not a certification of production safety.
