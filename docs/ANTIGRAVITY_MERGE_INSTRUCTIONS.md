# Antigravity — Merge real statistics data, without losing recent code

I have a targeted ChatGPT-generated patch at `Paper_Haven_Real_Statistics_Patch.zip`. Extract it to a separate folder, e.g. `C:\Users\MINH\OneDrive\Desktop\Paper_Haven_Real_Statistics_Patch_Review`. Project: `D:\1.Download\php code\DoAn_MaNguonMoPHP\QLTV_MaNguonMo-main\QLTV_MaNguonMo-github-stage`, Git branch `minh`.

**Perform an actual selective merge; no blanket overwrite.** Project is more recent than the snapshot used by ChatGPT. Compare each patched file with its present counterpart, accept only verified reporting changes, preserve other new improvements. Add the new `app/Repositories/RealStatisticsRepository.php` if no equivalent exists. Preserve existing route/RBAC/CSRF. Do not add demo data, do not edit MySQL, no migrations/seed/reset, no git reset/clean, no commit/push, no daemon restarts, no secrets in logs.

Outcome: `/admin/statistics` and all its CSV exports use only operational records; exclude known seed fixtures tagged `books.book_code LIKE 'DEMO-%'`, `readers.card_number LIKE 'DEMO-%'`, `loans.note LIKE 'DEMO_FIXTURE:%'`. Book counts and real loan counts, fine totals, charts, popular titles, pagination and exports must agree; do not fabricate zero-record datasets. Keep real data even if a demo staff member issued a legitimate loan.

QA: PHP lint changed files; run `php tests/real_statistics_static.php` after adapting to current code; browser-check date ranges, CSV and empty states, exercise SQL read-only counts against QA DB; report PASS/FAIL/BLOCKED honestly. If dashboard/other pages also show demos, report separately; do not extend scope without checking how they obtain metrics. Include final list of modified files and outputs.
