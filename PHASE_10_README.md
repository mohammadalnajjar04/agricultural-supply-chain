# Phase 10 — Dashboard visual refresh (safe incremental patch)

This is a **UI-only first pass** on the original repository snapshot available for review. It adds a new, scoped stylesheet and updates the three `dashboard.php` pages only. No database schema, credentials, queries, sessions, checkout, or status logic are intentionally changed.

## Install carefully
1. Ensure phases 1–9 have been committed. Back up your working tree.
2. Compare each supplied dashboard file against your current one before replacing: earlier commits may have changes not present in the original snapshot. Prefer applying just these 3 edits on each dashboard:
   - Add `<meta name="viewport" content="width=device-width, initial-scale=1">` below the charset meta.
   - Add `<link rel="stylesheet" href="../css/dashboard-v2.css?v=1">` immediately before `</head>`.
   - Change `<body data-role="ROLE">` to `<body data-role="ROLE" class="dashboard-v2">`.
3. Copy `css/dashboard-v2.css` to the same path in your project.
4. Test dashboard pages for all 3 roles in English and Arabic at desktop width and mobile width, including sidebar and links. Verify any old `class` attributes on `body` are preserved when merging.

## Important limitations
- The admin dashboard is not modified in this phase.
- Other role pages are not modified; this patch only covers dashboards.
- This is a visual stylesheet and not a full frontend redesign; behavior and existing UI quirks remain.
- PHP syntax was checked, but browser and database integration tests have not been performed.

## Git
`git add css/dashboard-v2.css farmer/dashboard.php store/dashboard.php transporter/dashboard.php PHASE_10_README.md`
`git diff --cached --check`
`git diff --cached`
`git commit -m "style: refresh role dashboard layout and responsive cards"`
`git push origin development`
