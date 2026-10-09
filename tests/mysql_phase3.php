<?php
declare(strict_types=1);

use Library\Core\Database;
use Library\Core\Router;
use Library\Repositories\BookRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\NotificationRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Repositories\UserRepository;
use Library\Services\LoanService;

require_once __DIR__ . '/test_database_guard.php';
require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$db = Database::connection();
$adminId = (int) $db->query('SELECT id FROM users WHERE role IN ("admin", "librarian") AND is_active = 1 ORDER BY id LIMIT 1')->fetchColumn();
if ($adminId < 1) {
    fwrite(STDERR, "MySQL integration test requires one active admin or librarian account.\n");
    exit(1);
}

$tag = bin2hex(random_bytes(5));
$userIds = [];
$readerIds = [];
$bookId = 0;
$copyId = 0;
$failure = 0;
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        throw new RuntimeException('MySQL integration test failed: ' . $message);
    }
};
$expectFailure = static function (callable $operation, string $expectedMessage, string $description) use ($assert): void {
    try {
        $operation();
    } catch (RuntimeException $exception) {
        if ($exception instanceof PDOException || !str_contains($exception->getMessage(), $expectedMessage)) {
            throw $exception;
        }
        $assert(true, $description);
        return;
    }
    $assert(false, $description);
};

try {
    $categoryId = (int) $db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    $users = new UserRepository($db);
    $readers = new ReaderRepository($db);
    for ($index = 1; $index <= 4; $index++) {
        $userId = $users->create([
            'name' => "QA Phase 3 {$index}",
            'email' => "qa-phase3-{$tag}-{$index}@example.test",
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'role' => 'reader',
        ]);
        $userIds[] = $userId;
        $readerIds[] = $readers->create([
            'user_id' => $userId,
            'card_number' => 'QA3-' . strtoupper($tag) . '-' . $index,
            'full_name' => "QA Reader {$index}",
            'phone' => null,
            'email' => "qa-phase3-{$tag}-{$index}@example.test",
            'address' => null,
            'expires_on' => date('Y-m-d', strtotime('+1 year')),
        ]);
    }

    $bookId = (new BookRepository($db))->create([
        'book_code' => 'QA3-' . strtoupper($tag),
        'title' => 'QA Phase 3 temporary book ' . $tag,
        'author' => 'Integration test',
        'isbn' => null,
        'category_id' => $categoryId ?: null,
        'publisher' => null,
        'publication_year' => (int) date('Y'),
        'description' => 'Temporary record created and removed by tests/mysql_phase3.php.',
        'total_copies' => 1,
        'cover_url' => null,
    ]);
    $copyId = (int) $db->query('SELECT id FROM book_copies WHERE book_id = ' . $bookId . ' ORDER BY id LIMIT 1')->fetchColumn();
    $service = new LoanService($db, 5000);
    $loans = new LoanRepository($db);
    $borrowedAt = date('Y-m-d');
    $dueAt = date('Y-m-d', strtotime('+1 day'));

    $code = $service->checkout($readerIds[0], [$copyId], $adminId, $borrowedAt, $dueAt, 'QA phase 3');
    $loanId = (int) $db->query("SELECT id FROM loans WHERE loan_code = " . $db->quote($code))->fetchColumn();
    $itemId = (int) $db->query('SELECT id FROM loan_items WHERE loan_id = ' . $loanId . ' ORDER BY id LIMIT 1')->fetchColumn();
    $maxRenewals = (int) $db->query('SELECT setting_value FROM system_settings WHERE setting_key = "max_renewals"')->fetchColumn();
    for ($renewal = 0; $renewal < $maxRenewals; $renewal++) {
        $previousDue = (string) $db->query('SELECT due_date FROM loan_items WHERE id = ' . $itemId)->fetchColumn();
        $nextDue = $service->renew($itemId, $userIds[0], $readerIds[0]);
        $assert($nextDue > $previousDue, 'Renewal advances the due date and writes history.');
    }
    $assert($loans->renewalCount($itemId) === $maxRenewals, 'Renewal history matches the configured limit.');
    $renewalSnapshot = $db->query('SELECT policy_version, renewal_days FROM loan_renewals WHERE loan_item_id = ' . $itemId . ' ORDER BY renewal_number DESC LIMIT 1')->fetch();
    $assert((int) $renewalSnapshot['policy_version'] > 0 && (int) $renewalSnapshot['renewal_days'] > 0, 'Each renewal stores its policy version and applied duration.');
    $expectFailure(
        static fn() => $service->renew($itemId, $userIds[0], $readerIds[0]),
        'đạt giới hạn',
        'Renewals are blocked after the configured maximum.'
    );

    $service->reserve($readerIds[1], $bookId, $userIds[1]);
    $reservation = $db->query('SELECT id FROM reservations WHERE reader_id = ' . $readerIds[1] . ' AND book_id = ' . $bookId)->fetchColumn();
    $expectFailure(
        static fn() => $service->reserve($readerIds[1], $bookId, $userIds[1]),
        'đã mượn hoặc đặt trước',
        'Duplicate reservations by the same reader are rejected.'
    );
    $service->returnBook($itemId, $adminId, 'good');
    $ready = $db->query('SELECT status, book_copy_id FROM reservations WHERE id = ' . (int) $reservation)->fetch();
    $assert($ready['status'] === 'ready' && (int) $ready['book_copy_id'] === $copyId, 'Returned copy is assigned to the oldest reservation.');
    $assert((string) $db->query('SELECT status FROM book_copies WHERE id = ' . $copyId)->fetchColumn() === 'reserved', 'Ready reservation holds the physical copy.');
    $assert((int) $db->query('SELECT COUNT(*) FROM notifications WHERE deduplication_key = ' . $db->quote('reservation-ready:' . $reservation))->fetchColumn() === 1, 'Ready notification is written once.');
    $notifications = new NotificationRepository($db);
    $unreadBefore = $notifications->unreadCount($userIds[1]);
    $notifications->create($userIds[1], 'Duplicate test', 'Must be ignored.', 'reservation', 'reservation', (int) $reservation, 'reservation-ready:' . $reservation);
    $assert((int) $db->query('SELECT COUNT(*) FROM notifications WHERE deduplication_key = ' . $db->quote('reservation-ready:' . $reservation))->fetchColumn() === 1, 'Deduplication key prevents duplicate notifications.');
    $noticeId = (int) $db->query('SELECT id FROM notifications WHERE deduplication_key = ' . $db->quote('reservation-ready:' . $reservation))->fetchColumn();
    $assert($notifications->markRead($noticeId, $userIds[1]) && $notifications->unreadCount($userIds[1]) === $unreadBefore - 1, 'Readers can mark their own notification as read.');

    $service->checkout($readerIds[1], [$copyId], $adminId, $borrowedAt, $dueAt, 'QA reservation fulfillment');
    $secondLoan = (int) $db->query('SELECT id FROM loans WHERE reader_id = ' . $readerIds[1] . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
    $secondItem = (int) $db->query('SELECT id FROM loan_items WHERE loan_id = ' . $secondLoan . ' ORDER BY id LIMIT 1')->fetchColumn();
    $assert((string) $db->query('SELECT status FROM reservations WHERE id = ' . (int) $reservation)->fetchColumn() === 'fulfilled', 'Checkout by the reserving reader fulfills the queue entry.');

    $service->reserve($readerIds[2], $bookId, $userIds[2]);
    $nextReservation = (int) $db->query('SELECT id FROM reservations WHERE reader_id = ' . $readerIds[2] . ' AND book_id = ' . $bookId)->fetchColumn();
    $service->returnBook($secondItem, $adminId, 'worn');
    $service->reserve($readerIds[3], $bookId, $userIds[3]);
    $waitingReservation = (int) $db->query('SELECT id FROM reservations WHERE reader_id = ' . $readerIds[3] . ' AND book_id = ' . $bookId)->fetchColumn();
    $db->exec('UPDATE reservations SET expires_at = DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id = ' . $nextReservation);
    $service->expireReservations();
    $expiredStatus = (string) $db->query('SELECT status FROM reservations WHERE id = ' . $nextReservation)->fetchColumn();
    $waiting = $db->query('SELECT status, book_copy_id FROM reservations WHERE id = ' . $waitingReservation)->fetch();
    $assert($expiredStatus === 'cancelled', 'Expired ready reservations are closed.');
    $assert($waiting['status'] === 'ready' && (int) $waiting['book_copy_id'] === $copyId, 'Expired hold is released and assigned to the next reader.');

    $service->checkout($readerIds[3], [$copyId], $adminId, $borrowedAt, $dueAt, 'QA next reservation');
    $thirdLoan = (int) $db->query('SELECT id FROM loans WHERE reader_id = ' . $readerIds[3] . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
    $thirdItem = (int) $db->query('SELECT id FROM loan_items WHERE loan_id = ' . $thirdLoan . ' ORDER BY id LIMIT 1')->fetchColumn();
    $service->returnBook($thirdItem, $adminId, 'good');

    $admin = $db->query('SELECT id, name, email, role FROM users WHERE id = ' . $adminId)->fetch();
    $_SESSION['user'] = $admin;
    $router = new Router();
    ob_start();
    $router->dispatch('GET', '/admin/loans');
    $loanPage = (string) ob_get_clean();
    $assert(str_contains($loanPage, 'SỔ MƯỢN TRẢ') && str_contains($loanPage, 'Yêu cầu chờ xử lý'), 'Authenticated loan management route renders the copy and reservation screens.');
    ob_start();
    $router->dispatch('GET', '/admin/settings');
    $settingsPage = (string) ob_get_clean();
    $assert(str_contains($settingsPage, 'CHÍNH SÁCH THƯ VIỆN') && str_contains($settingsPage, 'Số lần gia hạn tối đa'), 'Admin policy settings route renders editable controls.');
    $policySettings = new SystemSettingRepository($db);
    $policyValues = [];
    foreach ($db->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll() as $setting) {
        $policyValues[$setting['setting_key']] = (string) $setting['setting_value'];
    }
    $versionBefore = $policySettings->currentPolicyVersion();
    $policyValues['library_name'] = 'QA Version Rollback';
    $db->beginTransaction();
    $policySettings->update($policyValues, $adminId);
    $versionAfter = $policySettings->currentPolicyVersion();
    $assert($versionAfter > $versionBefore, 'Saving policy creates a new immutable policy snapshot.');
    $db->rollBack();
    $_SESSION['user'] = [
        'id' => $userIds[3],
        'name' => 'QA Phase 3 4',
        'email' => 'qa-phase3-test@example.test',
        'role' => 'reader',
    ];
    ob_start();
    $router->dispatch('GET', '/notifications');
    $notificationsPage = (string) ob_get_clean();
    $assert(str_contains($notificationsPage, 'TIN NHẮN TỪ THƯ VIỆN') && str_contains($notificationsPage, 'Thông báo'), 'Authenticated notification route renders reader messages.');

    fwrite(STDOUT, "MySQL phase 3 integration: {$checks} assertions passed.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    $failure = 1;
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $db->beginTransaction();
    try {
        if ($readerIds) {
            $readerList = implode(',', array_map('intval', $readerIds));
            $reservationRows = $db->query('SELECT id FROM reservations WHERE reader_id IN (' . $readerList . ')')->fetchAll(PDO::FETCH_COLUMN);
            if ($reservationRows) {
                $db->exec('DELETE FROM notifications WHERE entity_type = "reservation" AND entity_id IN (' . implode(',', array_map('intval', $reservationRows)) . ')');
            }
            $db->exec('DELETE FROM reservations WHERE reader_id IN (' . $readerList . ')');
            $loanRows = $db->query('SELECT id FROM loans WHERE reader_id IN (' . $readerList . ')')->fetchAll(PDO::FETCH_COLUMN);
            if ($loanRows) {
                $loanList = implode(',', array_map('intval', $loanRows));
                $db->exec('DELETE FROM notifications WHERE entity_type = "loan" AND entity_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM activity_logs WHERE subject_type = "loan" AND subject_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM loan_renewals WHERE loan_item_id IN (SELECT id FROM loan_items WHERE loan_id IN (' . $loanList . '))');
                $db->exec('DELETE FROM loan_items WHERE loan_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM loans WHERE id IN (' . $loanList . ')');
            }
            $db->exec('DELETE FROM activity_logs WHERE user_id IN (' . implode(',', array_map('intval', $userIds)) . ')');
            $db->exec('DELETE FROM notifications WHERE user_id IN (' . implode(',', array_map('intval', $userIds)) . ')');
            $db->exec('DELETE FROM readers WHERE id IN (' . $readerList . ')');
        }
        if ($userIds) {
            $db->exec('DELETE FROM users WHERE id IN (' . implode(',', array_map('intval', $userIds)) . ')');
        }
        if ($bookId > 0) {
            $db->exec('DELETE FROM activity_logs WHERE subject_type = "book" AND subject_id = ' . $bookId);
            $db->exec('DELETE FROM book_copies WHERE book_id = ' . $bookId);
            $db->exec('DELETE FROM books WHERE id = ' . $bookId);
        }
        $db->exec(
            'DELETE FROM authors WHERE name = "Integration test"
             AND NOT EXISTS (SELECT 1 FROM book_authors WHERE book_authors.author_id = authors.id)'
        );
        $db->commit();
    } catch (Throwable $cleanupException) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        fwrite(STDERR, 'QA data cleanup failed: ' . $cleanupException->getMessage() . PHP_EOL);
        $failure = 1;
    }
}

exit($failure ?? 0);
