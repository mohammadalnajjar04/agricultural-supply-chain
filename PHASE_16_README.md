# Phase 16 — Integration stabilization preflight (no replacement PHP files)

This patch is intentionally non-destructive. Previous phase ZIPs overwrite some of the same PHP paths (notably `store/place_order.php`). We do **not** have your current Windows `development` checkout or its live DB. Avoid applying new application changes before verifying the branch you actually use.

## Contents
- `tools/verify_integration.py`: Python 3 stdlib read-only file/heuristic/PHP lint/Git sensitive-file checks. It doesn't connect to MySQL, modify PHP, or run migrations. Exit code 1 = at least one FAIL; REVIEW is a prompt for manual inspection, not proof of a bug.
- `database/checks/016_readonly_schema_preflight.sql`: MySQL metadata-only queries to check whether migrations and constraints *appear* present. Does **not** change database.
- `PHASE_16_TEST_MATRIX.md`: end-to-end and race-condition checklist.

## Usage (PowerShell from your repository)
```powershell
cd C:\Projects\agricultural-supply-chain
git switch development
python tools/verify_integration.py --root . --json phase16-local-report.json
# If PHP is not in PATH: python tools/verify_integration.py --root . --php 'C:\xampp\php\php.exe'
```
Review the findings; do not upload `phase16-local-report.json` with machine paths. In phpMyAdmin select a **staging copy** of the project DB, run `database/checks/016_readonly_schema_preflight.sql`, and compare the result to migrations 003–006. If any column/table missing, stop and reconcile migrations **on staging first**. Do not run the original schema initializer on an existing DB (it may drop tables).

## Git after checks
```powershell
git add tools/verify_integration.py database/checks/016_readonly_schema_preflight.sql PHASE_16_README.md PHASE_16_TEST_MATRIX.md
git diff --cached --check
git diff --cached --stat
git commit -m "test: add phase 16 integration and schema preflight"
git push origin development
```
These commands commit the diagnostic tools, **not** a claim that the full system is tested or fixed. Do not stage `.env`, verification documents, SQL backups, or local reports.
