# Paper Haven — Operational-only statistics patch

**Purpose.** Fix `/admin/statistics` showing `Tủ sách minh họa Paper & Page`, `DEMO-B...` and `DEMO-READ...` as if operational loans. The original `database/seed_demo.php` marks its generated fixture data using book code `DEMO-B...`, reader card `DEMO-READ...`, and loan note `DEMO_FIXTURE:...`.

## Scope

- `/admin/statistics`: monthly trend, popular loans, active loans, titles, copies, readers, categories, fees collected and outstanding, tabular loan / inventory / fee reports.
- `/admin/statistics/export`: loans, inventory, fines, books, readers and monthly CSV exports.
- Data is queried from MySQL, never fabricated. If real transactions do not exist, the report shows an honest empty state.
- The patch **does not delete, reset, migrate, edit or re-seed** the database.
- Other operational screens (e.g. `/admin/loans` and the Admin homepage) are **not** modified by this narrow patch; consider a separate review if they also display QA fixtures.

## Data classification rule

- Exclude `books.book_code LIKE 'DEMO-%'`.
- Exclude `readers.card_number LIKE 'DEMO-%'`.
- Exclude `loans.note LIKE 'DEMO_FIXTURE:%'`.
- The `demo-librarian@paperpage.local` staff account is **not itself a reason** to hide a legitimate transaction, because the decisive provenance is the loan/reader/book, not who processed the loan.
- These rules separate **known seed fixtures** only. They cannot certify that other database records represent actual physical circulation. Physical copies labeled `pending_audit` must still be excluded from borrowability under existing rules.

## Files to merge (not overwrite wholesale)

1. `app/Repositories/RealStatisticsRepository.php`: new read-only statistics queries.
2. `app/Repositories/LoanRepository.php`: optional `$realOnly` flag on `reportRows()`; default `false` so other existing uses are unchanged.
3. `app/Repositories/ReportRepository.php`: optional `$realOnly` flag on `rows()`; default `false`.
4. `app/Controllers/DashboardController.php`: real-only reporting and exports.
5. `app/Views/admin/statistics.php`: explicit report scope and genuine empty states.
6. `tests/real_statistics_static.php`: static guard (not a MySQL test).

**Important:** Your live project was modified by Antigravity *after* the source ZIP was shared. This patch is based on the snapshot uploaded to ChatGPT. Run a three-way/manual merge in VS Code or Antigravity, using the current project as authoritative. Never blindly overwrite the live project's corresponding files, because you may lose new functions.

## Verification checklist in your own QA database

1. Copy latest schema and sanitized data into an isolated QA database. Never run fixture-producing tests against `paper_page_library`.
2. Back up primary database before **any** independently approved future mutation; no mutation is needed for this patch.
3. Measure fixture and nonfixture counts with read-only queries:

```sql
SELECT COUNT(*) AS fixture_books FROM books WHERE book_code LIKE 'DEMO-%';
SELECT COUNT(*) AS fixture_readers FROM readers WHERE card_number LIKE 'DEMO-%';
SELECT COUNT(*) AS real_loan_items
FROM loan_items li JOIN loans l ON l.id=li.loan_id
JOIN readers r ON r.id=l.reader_id
JOIN book_copies cp ON cp.id=li.book_copy_id
JOIN books b ON b.id=cp.book_id
WHERE b.book_code NOT LIKE 'DEMO-%'
  AND r.card_number NOT LIKE 'DEMO-%'
  AND COALESCE(l.note,'') NOT LIKE 'DEMO_FIXTURE:%';
```

4. Compare `/admin/statistics` to the SELECT results over the same date window, including each summary and CSV export; ensure paging totals match table totals.
5. Verify a real loan appears and a demo loan does not; if there are no real loans, show 0 and empty state (do not fabricate loans).
6. Verify operational catalogue books with 0 copies remain visible as titles, but are not presented as borrowable stock.
7. Verify `/admin/loans`, returns, admin dashboard and QA-specific screens still behave correctly (this patch changes the report scope, not those modules).
8. PHP lint all changed files and execute `php tests/real_statistics_static.php`. Check responsive dashboard after merging.

**Known limitation:** No connection to your local MySQL server was available in this environment; SQL integration, live screenshots, CSV content and end-to-end browser testing are NOT verified by ChatGPT. Perform these checks on your QA database before deployment. 
