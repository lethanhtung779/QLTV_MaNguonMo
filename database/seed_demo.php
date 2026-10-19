<?php
declare(strict_types=1);

use Library\Core\Config;
use Library\Core\Database;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\UserRepository;
use Library\Services\FineService;
use Library\Services\LoanService;

require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (Config::get('APP_ENV') !== 'local' || !Config::bool('SEED_DEMO_DATA', false)) {
    fwrite(STDERR, "Seed demo chỉ chạy khi APP_ENV=local và SEED_DEMO_DATA=true trong .env.\n");
    exit(1);
}
$databaseHost = strtolower(trim((string) Config::get('DB_HOST', '127.0.0.1')));
$databaseName = (string) Config::get('DB_NAME', '');
if (!in_array($databaseHost, ['localhost', '127.0.0.1', '::1'], true) ||
    preg_match('/(^|_)(prod|production)(_|$)/i', $databaseName)) {
    fwrite(STDERR, "Seed demo chỉ được phép trên database local, không phải môi trường production.\n");
    exit(1);
}

function demoSecret(string $key, string $envFile): string
{
    $secret = (string) Config::get($key, '');
    if (strlen($secret) > 72 || str_contains($secret, "\0")) {
        throw new RuntimeException('Mật khẩu demo tối đa 72 byte và không chứa byte NUL.');
    }
    if (strlen($secret) >= 16 && !str_contains($secret, 'ChangeThis')) {
        return $secret;
    }

    if (!is_file($envFile) || !is_writable($envFile)) {
        throw new RuntimeException('Không thể lưu mật khẩu demo an toàn vào .env.');
    }
    $secret = bin2hex(random_bytes(24));
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Không thể đọc .env để lưu mật khẩu demo.');
    }
    $found = false;
    foreach ($lines as &$line) {
        if (preg_match('/^\s*' . preg_quote($key, '/') . '\s*=/', $line)) {
            $line = $key . '=' . $secret;
            $found = true;
        }
    }
    unset($line);
    if (!$found) {
        $lines[] = $key . '=' . $secret;
    }
    if (file_put_contents($envFile, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('Không thể ghi mật khẩu demo vào .env.');
    }
    putenv($key . '=' . $secret);
    $_ENV[$key] = $secret;
    return $secret;
}

function demoUser(PDO $db, string $name, string $email, string $password, string $role): int
{
    $users = new UserRepository($db);
    $existing = $users->findByEmail($email);
    if ($existing) {
        if ($existing['role'] !== $role || !(bool) $existing['is_active']) {
            throw new RuntimeException("Tài khoản demo {$email} đã tồn tại nhưng không đúng role/trạng thái.");
        }
        return (int) $existing['id'];
    }
    return $users->create([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);
}

function verifyDemoCredential(PDO $db, int $userId, string $password, string $role): void
{
    $statement = $db->prepare('SELECT password_hash, role, is_active FROM users WHERE id = :id');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();
    if (!$user || $user['role'] !== $role || !(bool) $user['is_active'] ||
        !password_verify($password, (string) $user['password_hash'])) {
        throw new RuntimeException('Thông tin đăng nhập demo không khớp cấu hình trong .env.');
    }
}

function demoReader(PDO $db, string $cardNumber, string $name, ?int $userId, ?string $email): int
{
    $lookup = $db->prepare('SELECT id, user_id, is_active FROM readers WHERE card_number = :card');
    $lookup->execute(['card' => $cardNumber]);
    $existing = $lookup->fetch();
    if ($existing) {
        if ((int) ($existing['user_id'] ?? 0) !== (int) ($userId ?? 0) || !(bool) $existing['is_active']) {
            throw new RuntimeException("Hồ sơ demo {$cardNumber} đã tồn tại nhưng không khớp tài khoản/trạng thái.");
        }
        return (int) $existing['id'];
    }
    return (new ReaderRepository($db))->create([
        'user_id' => $userId,
        'card_number' => $cardNumber,
        'full_name' => $name,
        'phone' => null,
        'email' => $email,
        'address' => 'Địa chỉ minh họa — dữ liệu demo local',
        'expires_on' => date('Y-m-d', strtotime('+2 years')),
    ]);
}

function demoLoan(
    PDO $db,
    LoanService $service,
    int $readerId,
    array $copyIds,
    int $librarianId,
    string $borrowedAt,
    string $dueAt,
    string $marker
): int {
    $lookup = $db->prepare('SELECT id FROM loans WHERE note = :marker LIMIT 1');
    $lookup->execute(['marker' => 'DEMO_FIXTURE:' . $marker]);
    $existingLoanId = (int) $lookup->fetchColumn();
    if ($existingLoanId > 0) {
        return $existingLoanId;
    }
    $code = $service->checkout(
        $readerId,
        $copyIds,
        $librarianId,
        $borrowedAt,
        $dueAt,
        'DEMO_FIXTURE:' . $marker
    );
    $lookup->execute(['marker' => 'DEMO_FIXTURE:' . $marker]);
    $loanId = (int) $lookup->fetchColumn();
    if ($loanId < 1) {
        throw new RuntimeException("Không tìm thấy phiếu demo vừa tạo {$code}.");
    }
    return $loanId;
}

try {
    $root = dirname(__DIR__);
    $envFile = $root . '/.env';
    $librarianEmail = strtolower(trim((string) Config::get('SEED_DEMO_LIBRARIAN_EMAIL', 'demo-librarian@paperpage.local')));
    $readerEmail = strtolower(trim((string) Config::get('SEED_DEMO_READER_EMAIL', 'demo-reader@paperpage.local')));
    if (!filter_var($librarianEmail, FILTER_VALIDATE_EMAIL) || !filter_var($readerEmail, FILTER_VALIDATE_EMAIL) ||
        $librarianEmail === $readerEmail) {
        throw new RuntimeException('Email tài khoản demo trong .env không hợp lệ hoặc bị trùng.');
    }
    $librarianPassword = demoSecret('SEED_DEMO_LIBRARIAN_PASSWORD', $envFile);
    $readerPassword = demoSecret('SEED_DEMO_READER_PASSWORD', $envFile);

    $db = Database::connection();
    $categoryRows = $db->query('SELECT name, id FROM categories ORDER BY id')->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!$categoryRows) {
        throw new RuntimeException('Cần chạy database/setup.php trước khi nạp demo.');
    }
    $categoryIds = array_values($categoryRows);
    $books = new BookRepository($db);
    $demoBooks = [];
    for ($number = 1; $number <= 21; $number++) {
        $code = sprintf('DEMO-B%03d', $number);
        $title = sprintf('Tủ sách minh họa Paper & Page %02d', $number);
        $find = $db->prepare('SELECT id, title FROM books WHERE book_code = :code');
        $find->execute(['code' => $code]);
        $existing = $find->fetch();
        if ($existing) {
            if ($existing['title'] !== $title) {
                throw new RuntimeException("Mã sách demo {$code} đã được dùng cho sách khác.");
            }
            $bookId = (int) $existing['id'];
        } else {
            $bookId = $books->create([
                'book_code' => $code,
                'title' => $title,
                'author' => sprintf('Tác giả minh họa %02d', $number),
                'isbn' => null,
                'category_id' => (int) $categoryIds[($number - 1) % count($categoryIds)],
                'publisher' => 'Nhà xuất bản minh họa',
                'publication_year' => 2000 + (($number * 7) % 25),
                'description' => 'Dữ liệu demo tổng hợp, không đại diện cho đầu sách hay giao dịch thật.',
                'total_copies' => 2,
                'cover_url' => null,
            ]);
        }
        $demoBooks[] = $bookId;
    }

    $librarianId = demoUser($db, 'Thủ thư minh họa', $librarianEmail, $librarianPassword, 'librarian');
    $readerUserId = demoUser($db, 'Độc giả minh họa', $readerEmail, $readerPassword, 'reader');
    verifyDemoCredential($db, $librarianId, $librarianPassword, 'librarian');
    verifyDemoCredential($db, $readerUserId, $readerPassword, 'reader');
    $readerIds = [
        demoReader($db, 'DEMO-READ-001', 'Độc giả minh họa 01', $readerUserId, $readerEmail),
        demoReader($db, 'DEMO-READ-002', 'Độc giả minh họa 02', null, 'demo-reader-02@paperpage.local'),
        demoReader($db, 'DEMO-READ-003', 'Độc giả minh họa 03', null, 'demo-reader-03@paperpage.local'),
    ];

    $copyLookup = $db->prepare('SELECT id FROM book_copies WHERE book_id = :book_id ORDER BY copy_code');
    $copiesFor = static function (int $bookId) use ($copyLookup): array {
        $copyLookup->execute(['book_id' => $bookId]);
        $ids = array_map('intval', $copyLookup->fetchAll(PDO::FETCH_COLUMN));
        if (count($ids) < 2) {
            throw new RuntimeException('Mỗi đầu sách demo cần có hai bản sao.');
        }
        return $ids;
    };
    $loanService = new LoanService($db, (float) Config::get('DAILY_FINE', 5000));
    $now = new DateTimeImmutable('today');
    $date = static fn(int $days): string => $now->modify(($days >= 0 ? '+' : '') . $days . ' days')->format('Y-m-d');

    demoLoan($db, $loanService, $readerIds[0], [$copiesFor($demoBooks[0])[0]], $librarianId, $date(-2), $date(12), 'ACTIVE');
    demoLoan(
        $db,
        $loanService,
        $readerIds[1],
        [$copiesFor($demoBooks[1])[0]],
        $librarianId,
        $date(-16),
        $date(-2),
        'OVERDUE'
    );

    $reservationBookCopies = $copiesFor($demoBooks[1]);
    demoLoan(
        $db,
        $loanService,
        $readerIds[0],
        [$reservationBookCopies[1]],
        $librarianId,
        $date(-1),
        $date(13),
        'RESERVATION-HOLD'
    );

    $reservationLookup = $db->prepare(
        'SELECT id, status FROM reservations WHERE reader_id = :reader_id AND book_id = :book_id
         AND status IN ("pending", "ready") ORDER BY id LIMIT 1'
    );
    $reservationLookup->execute(['reader_id' => $readerIds[2], 'book_id' => $demoBooks[1]]);
    $reservation = $reservationLookup->fetch();
    if (!$reservation) {
        $loanService->reserve($readerIds[2], $demoBooks[1], $librarianId);
    }

    $returnLookup = $db->prepare(
        'SELECT li.id FROM loan_items li JOIN loans l ON l.id = li.loan_id
         WHERE l.note = :marker AND li.returned_at IS NULL LIMIT 1'
    );
    $returnLookup->execute(['marker' => 'DEMO_FIXTURE:RESERVATION-HOLD']);
    $holdItemId = (int) $returnLookup->fetchColumn();
    if ($holdItemId > 0) {
        $loanService->returnBook($holdItemId, $librarianId, 'good');
    }

    demoLoan(
        $db,
        $loanService,
        $readerIds[2],
        [$copiesFor($demoBooks[2])[0], $copiesFor($demoBooks[3])[0]],
        $librarianId,
        $date(-1),
        $date(13),
        'PARTIAL'
    );
    $partialState = $db->prepare(
        'SELECT COUNT(*) AS item_count, SUM(li.returned_at IS NOT NULL) AS returned_count
         FROM loan_items li JOIN loans l ON l.id = li.loan_id WHERE l.note = :marker'
    );
    $partialState->execute(['marker' => 'DEMO_FIXTURE:PARTIAL']);
    $partial = $partialState->fetch();
    if ($partial && (int) $partial['item_count'] === 2 && (int) $partial['returned_count'] === 0) {
        $returnLookup->execute(['marker' => 'DEMO_FIXTURE:PARTIAL']);
        $partialItemId = (int) $returnLookup->fetchColumn();
        if ($partialItemId > 0) {
            $loanService->returnBook($partialItemId, $librarianId, 'good');
        }
    }

    demoLoan(
        $db,
        $loanService,
        $readerIds[0],
        [$copiesFor($demoBooks[4])[0]],
        $librarianId,
        $date(-3),
        $date(-1),
        'RETURNED'
    );
    $returnLookup->execute(['marker' => 'DEMO_FIXTURE:RETURNED']);
    $returnedItemId = (int) $returnLookup->fetchColumn();
    if ($returnedItemId > 0) {
        $loanService->returnBook($returnedItemId, $librarianId, 'worn');
    }

    (new FineService($db))->synchronizeReader($readerIds[1], $librarianId);
    $reservationLookup->execute(['reader_id' => $readerIds[2], 'book_id' => $demoBooks[1]]);
    $reservation = $reservationLookup->fetch();
    if (!$reservation || $reservation['status'] !== 'ready') {
        throw new RuntimeException('Không thể hoàn tất reservation demo ở trạng thái ready.');
    }

    fwrite(STDOUT, "Đã nạp fixture local an toàn: 21 sách minh họa, 3 độc giả, tài khoản thủ thư/độc giả, phiếu đang mượn/quá hạn/trả một phần/đã trả, reservation ready và ledger phí.\n");
    fwrite(STDOUT, "Mật khẩu tài khoản demo được lưu trong .env bị ignore; không ghi mật khẩu ra terminal.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Không thể nạp fixture demo: {$exception->getMessage()}\n");
    exit(1);
}
