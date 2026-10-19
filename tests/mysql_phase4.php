<?php
declare(strict_types=1);

use Library\Core\Database;
use Library\Core\Router;
use Library\Repositories\BookRepository;
use Library\Repositories\FineRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\UserRepository;
use Library\Services\FineService;
use Library\Services\LoanReportCsvExporter;
use Library\Services\LoanService;

require_once __DIR__ . '/test_database_guard.php';
require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$db = Database::connection();
$admin = $db->query('SELECT id, name, email, role FROM users WHERE role IN ("admin", "librarian") AND is_active = 1 ORDER BY id LIMIT 1')->fetch();
if (!$admin) {
    fwrite(STDERR, "MySQL integration test requires one active admin or librarian account.\n");
    exit(1);
}

$adminId = (int) $admin['id'];
$tag = bin2hex(random_bytes(5));
$userId = 0;
$readerId = 0;
$bookId = 0;
$loanId = 0;
$itemId = 0;
$checks = 0;
$failure = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('MySQL phase 4 integration failed: ' . $message);
    }
};

try {
    $categoryId = (int) $db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    $userId = (new UserRepository($db))->create([
        'name' => 'QA Phase 4 ' . $tag,
        'email' => 'qa-phase4-' . $tag . '@example.test',
        'password_hash' => password_hash(bin2hex(random_bytes(20)), PASSWORD_DEFAULT),
        'role' => 'reader',
    ]);
    $readerId = (new ReaderRepository($db))->create([
        'user_id' => $userId,
        'card_number' => 'QA4-' . strtoupper($tag),
        'full_name' => 'QA Fine Reader ' . $tag,
        'phone' => null,
        'email' => 'qa-phase4-' . $tag . '@example.test',
        'address' => null,
        'expires_on' => date('Y-m-d', strtotime('+1 year')),
    ]);
    $bookData = [
        'book_code' => 'QA4-' . strtoupper($tag),
        'title' => 'QA Phase 4 temporary book ' . $tag,
        'author' => 'Integration test',
        'isbn' => null,
        'category_id' => $categoryId ?: null,
        'publisher' => null,
        'publication_year' => (int) date('Y'),
        'description' => 'Temporary record created and removed by tests/mysql_phase4.php.',
        'total_copies' => 1,
        'cover_url' => null,
    ];
    $bookRepository = new BookRepository($db);
    $bookId = $bookRepository->create($bookData);
    $bookData['author'] = 'Integration test; QA Coauthor';
    $bookData['publisher'] = 'QA Publisher';
    $bookRepository->update($bookId, $bookData);
    $authorCount = (int) $db->query('SELECT COUNT(*) FROM book_authors WHERE book_id = ' . $bookId)->fetchColumn();
    $assert($authorCount === 2, 'Book metadata writes normalized links for multiple authors.');
    $assert((int) $db->query('SELECT publisher_id FROM books WHERE id = ' . $bookId)->fetchColumn() > 0, 'Publisher text is linked to a normalized publisher record.');
    $copyId = (int) $db->query('SELECT id FROM book_copies WHERE book_id = ' . $bookId . ' ORDER BY id LIMIT 1')->fetchColumn();
    $loanService = new LoanService($db, 5000);
    $loanCode = $loanService->checkout(
        $readerId,
        [$copyId],
        $adminId,
        date('Y-m-d', strtotime('-3 days')),
        date('Y-m-d', strtotime('-2 days')),
        'QA phase 4'
    );
    $loanId = (int) $db->query('SELECT id FROM loans WHERE loan_code = ' . $db->quote($loanCode))->fetchColumn();
    $itemId = (int) $db->query('SELECT id FROM loan_items WHERE loan_id = ' . $loanId)->fetchColumn();
    $db->exec('UPDATE loan_items SET daily_fine = 5000, due_date = DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY) WHERE id = ' . $itemId);
    $db->exec('UPDATE loans SET due_at = DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY) WHERE id = ' . $loanId);

    $loanService->returnBook($itemId, $adminId, 'damaged');
    $fines = new FineService($db);
    $fines->synchronizeReader($readerId, $adminId);
    $charges = new FineRepository($db);
    $rows = $charges->chargesForReader($readerId);
    $assert(count($rows) === 2, 'Late return and damaged-copy charges are recorded as separate ledger entries.');
    $types = array_column($rows, 'charge_type');
    $assert(in_array('overdue', $types, true) && in_array('damaged', $types, true), 'Ledger identifies overdue and damaged-copy charge types.');
    $assert(count(array_unique(array_column($rows, 'deduplication_key'))) === 2, 'Each charge has an idempotent unique key.');
    $expectedOverdue = 10000.0;
    $overdue = array_values(array_filter($rows, static fn(array $row): bool => $row['charge_type'] === 'overdue'))[0];
    $assert((float) $overdue['amount'] === $expectedOverdue, 'Overdue amount is accrued from the item daily rate and late days.');
    $reportRows = (new LoanRepository($db))->reportRows(date('Y-m-d', strtotime('-5 days')), date('Y-m-d'), 'returned');
    $reportRow = array_values(array_filter($reportRows, static fn(array $row): bool => $row['loan_code'] === $loanCode));
    $assert(count($reportRow) === 1 && (float) $reportRow[0]['charged_amount'] > 0, 'Date- and status-filtered report rows include ledger charges.');
    $activeReportRows = (new LoanRepository($db))->reportRows(date('Y-m-d', strtotime('-5 days')), date('Y-m-d'), 'borrowed');
    $assert(!array_filter($activeReportRows, static fn(array $row): bool => $row['loan_code'] === $loanCode), 'Report status filters exclude returned items from active-loan results.');
    $exportRows = $reportRow;
    $exportRows[0]['full_name'] = '=2+2';
    $csvStream = fopen('php://temp', 'w+b');
    LoanReportCsvExporter::write($csvStream, $exportRows);
    rewind($csvStream);
    $csv = stream_get_contents($csvStream);
    fclose($csvStream);
    $assert(str_starts_with($csv, "\xEF\xBB\xBF") && str_contains($csv, 'Mã phiếu'), 'CSV output is UTF-8 with BOM and localized column headers.');
    $assert(str_contains($csv, "'=2+2"), 'CSV output neutralizes spreadsheet formula injection cells.');

    $initialBalance = $fines->balance($readerId);
    $assert($initialBalance > $expectedOverdue, 'Reader balance includes condition fees in addition to overdue charges.');
    try {
        $fines->recordPayment($readerId, number_format($initialBalance + 1, 2, '.', ''), 'cash', null, null, $adminId);
        $assert(false, 'Payment larger than the balance is rejected.');
    } catch (RuntimeException $exception) {
        $assert(str_contains($exception->getMessage(), 'vượt quá dư nợ'), 'Overpayment is rejected with an explicit validation message.');
    }

    $paidAmount = number_format(floor($initialBalance / 2), 2, '.', '');
    $fines->recordPayment($readerId, $paidAmount, 'cash', 'QA-' . $tag, 'Thanh toán kiểm thử', $adminId);
    $afterPayment = $fines->balance($readerId);
    $assert(abs($afterPayment - ($initialBalance - (float) $paidAmount)) < 0.01, 'Payment is allocated and reduces the outstanding balance exactly.');
    $allocationTotal = (float) $db->query(
        'SELECT COALESCE(SUM(a.amount), 0)
         FROM fine_payment_allocations a JOIN fine_payments p ON p.id = a.payment_id
         WHERE p.reader_id = ' . $readerId
    )->fetchColumn();
    $assert(abs($allocationTotal - (float) $paidAmount) < 0.01, 'Recorded payment is fully allocated to individual charges.');

    foreach ($charges->chargesForReader($readerId) as $charge) {
        $remaining = (float) $charge['balance'];
        if ($remaining > 0) {
            $fines->waive((int) $charge['id'], number_format($remaining, 2, '.', ''), 'QA policy adjustment', $adminId);
        }
    }
    $assert($fines->balance($readerId) === 0.0, 'Admin waiver records can settle the remaining charge balances.');
    $waiverCount = (int) $db->query(
        'SELECT COUNT(*) FROM fine_waivers w
         JOIN fine_charges fc ON fc.id = w.fine_charge_id WHERE fc.reader_id = ' . $readerId
    )->fetchColumn();
    $assert($waiverCount > 0, 'Waivers persist their amount, reason, and operator in MySQL.');

    $admin['credential_version'] = hash('sha256', (new UserRepository($db))->findById($adminId)['password_hash']);
    $_SESSION['user'] = $admin;
    $router = new Router();
    ob_start();
    $router->dispatch('GET', '/admin/fines?reader=' . $readerId);
    $finePage = (string) ob_get_clean();
    $assert(str_contains($finePage, 'Ghi nhận thanh toán') && str_contains($finePage, 'QA Fine Reader'), 'Admin fine-management route renders the account and payment form.');
    ob_start();
    $router->dispatch('GET', '/admin/settings');
    $settingsPage = (string) ob_get_clean();
    $assert(str_contains($settingsPage, 'Phí sách hỏng') && str_contains($settingsPage, 'Phí sách mất'), 'Admin policy settings expose configurable damage and loss fees.');
    ob_start();
    $router->dispatch('GET', '/admin');
    $dashboardPage = (string) ob_get_clean();
    $assert(str_contains($dashboardPage, 'Dư nợ phí'), 'Admin dashboard renders its ledger-backed outstanding-balance KPI.');
    ob_start();
    $router->dispatch('GET', '/admin/statistics');
    $statisticsPage = (string) ob_get_clean();
    $assert(str_contains($statisticsPage, 'Tải báo cáo CSV UTF-8') && str_contains($statisticsPage, 'Danh sách lượt mượn'), 'Statistics route renders date/status filters and CSV export.');
    $readerUser = $db->query('SELECT id, name, email, role FROM users WHERE id = ' . $userId)->fetch();
    $readerUser['credential_version'] = hash('sha256', (new UserRepository($db))->findById($userId)['password_hash']);
    $_SESSION['user'] = $readerUser;
    ob_start();
    $router->dispatch('GET', '/account');
    $readerPage = (string) ob_get_clean();
    $assert(str_contains($readerPage, 'Chi tiết khoản phí') && str_contains($readerPage, 'Lịch sử thanh toán phí'), 'Reader dashboard renders the charge ledger and personal payment history.');

    fwrite(STDOUT, "MySQL phase 4 integration: {$checks} assertions passed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $failure = 1;
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    try {
        if ($readerId > 0) {
            $paymentIds = $db->query('SELECT id FROM fine_payments WHERE reader_id = ' . $readerId)->fetchAll(PDO::FETCH_COLUMN);
            $chargeIds = $db->query('SELECT id FROM fine_charges WHERE reader_id = ' . $readerId)->fetchAll(PDO::FETCH_COLUMN);
            $subjectConditions = [];
            if ($loanId > 0) {
                $subjectConditions[] = '(subject_type = "loan" AND subject_id = ' . $loanId . ')';
            }
            if ($paymentIds) {
                $subjectConditions[] = '(subject_type = "fine_payment" AND subject_id IN (' . implode(',', array_map('intval', $paymentIds)) . '))';
            }
            if ($chargeIds) {
                $subjectConditions[] = '(subject_type = "fine_charge" AND subject_id IN (' . implode(',', array_map('intval', $chargeIds)) . '))';
            }
            if ($subjectConditions) {
                $db->exec('DELETE FROM activity_logs WHERE user_id = ' . $adminId . ' AND (' . implode(' OR ', $subjectConditions) . ')');
            }
            if ($paymentIds) {
                $paymentList = implode(',', array_map('intval', $paymentIds));
                $db->exec('DELETE FROM fine_payment_allocations WHERE payment_id IN (' . $paymentList . ')');
                $db->exec('DELETE FROM fine_payments WHERE id IN (' . $paymentList . ')');
            }
            if ($chargeIds) {
                $chargeList = implode(',', array_map('intval', $chargeIds));
                $db->exec('DELETE FROM fine_waivers WHERE fine_charge_id IN (' . $chargeList . ')');
                $db->exec('DELETE FROM fine_payment_allocations WHERE fine_charge_id IN (' . $chargeList . ')');
                $db->exec('DELETE FROM fine_charges WHERE id IN (' . $chargeList . ')');
            }
            $db->exec('DELETE FROM notifications WHERE user_id = ' . $userId);
            if ($loanId > 0) {
                $db->exec('DELETE FROM loan_items WHERE loan_id = ' . $loanId);
                $db->exec('DELETE FROM loans WHERE id = ' . $loanId);
            }
            $db->exec('DELETE FROM readers WHERE id = ' . $readerId);
            $db->exec('DELETE FROM users WHERE id = ' . $userId);
        }
        if ($bookId > 0) {
            $db->exec('DELETE FROM book_copies WHERE book_id = ' . $bookId);
            $db->exec('DELETE FROM books WHERE id = ' . $bookId);
        }
        $db->exec(
            'DELETE FROM authors WHERE name IN ("Integration test", "QA Coauthor")
             AND NOT EXISTS (SELECT 1 FROM book_authors WHERE book_authors.author_id = authors.id)'
        );
        $db->exec(
            'DELETE FROM publishers WHERE name = "QA Publisher"
             AND NOT EXISTS (SELECT 1 FROM books WHERE books.publisher_id = publishers.id)'
        );
    } catch (Throwable $cleanupException) {
        fwrite(STDERR, 'QA data cleanup failed: ' . $cleanupException->getMessage() . PHP_EOL);
        $failure = 1;
    }
}

exit($failure);
