# Phase 16 — Staging integration test matrix

Record PASS/FAIL and evidence from your **actual development branch**, using disposable data.

| ID | Role / scenario | Expected behavior | Result |
| --- | --- | --- | --- |
| T01 | All four roles log in and log out | Session renews on login and fails after logout | NOT RUN |
| T02 | Store calls farmer/admin protected endpoint | Server denies access, not only hides buttons | NOT RUN |
| T03 | Farmer creates/edits/hides product | Hidden product not orderable but old orders remain | NOT RUN |
| T04 | Store submits quantity 0, negative, oversize | Invalid request rejected without rows | NOT RUN |
| T05 | Store orders, farmer changes price afterwards | Saved order unit price/name/total unchanged | NOT RUN |
| T06 | Farmer approves pending order once | Stock decremented once, one ledger event & transport request | NOT RUN |
| T07 | Two rapid approvals of same order | No double stock decrement or duplicate movement | NOT RUN |
| T08 | Approve with insufficient stock | No partial approval, ledger event, or transport request | NOT RUN |
| T09 | Two transporters accept same request | Exactly one succeeds; other gets conflict | NOT RUN |
| T10 | Transporter tries to skip delivery states | Illegal transition rejected | NOT RUN |
| T11 | Another transporter updates someone else's delivery | Access denied | NOT RUN |
| T12 | Admin approve/reject pending account by POST | Works once; GET cannot mutate | NOT RUN |
| T13 | Unauthenticated verification document URL | Denied, no private ID document exposed | NOT RUN |
| T14 | Mobile Arabic/English catalogs and dashboards | Controls accessible, no broken layouts | NOT RUN |
| T15 | Database foreign keys and UNIQUE index | Current schema matches required migration contract | NOT RUN |

**Priority:** T03–T11 and T13 are blockers for production. Reproduce each failure before fixing its PHP file. Keep DB snapshots/PII out of Git.
