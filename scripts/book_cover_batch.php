<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;

const VERIFIED_STATUS = 'VERIFIED_READY';
const MISSING_STATUS = 'MISSING_NO_VERIFIED_SOURCE';

$root = dirname(__DIR__);
$inputReport = $root . '/storage/reports/book_cover_mapping_preview_20261019.csv';
$mappingReport = $root . '/storage/reports/book_cover_batch_preview_20261019.csv';
$stageDirectory = $root . '/storage/staged-book-covers';
$publicDirectory = $root . '/public/assets/book-covers';
$mode = $argv[1] ?? '--preview';
$refresh = in_array('--refresh', $argv, true);

if (!in_array($mode, ['--preview', '--apply', '--verify'], true)) {
    fwrite(STDERR, "Usage: php scripts/book_cover_batch.php --preview [--refresh] | --apply | --verify\n");
    exit(2);
}

$db = Database::connection();

/** @return array<int,array<string,string>> */
function readCsv(string $path): array
{
    if (!is_file($path)) throw new RuntimeException('Missing CSV: ' . $path);
    $handle = fopen($path, 'rb');
    if (!$handle) throw new RuntimeException('Cannot read CSV: ' . $path);
    $header = fgetcsv($handle);
    if (!$header) throw new RuntimeException('CSV header is empty: ' . $path);
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
    $rows = [];
    while (($values = fgetcsv($handle)) !== false) {
        if (count($values) !== count($header)) throw new RuntimeException('Malformed CSV row in ' . $path);
        $rows[] = array_combine($header, $values);
    }
    fclose($handle);
    return $rows;
}

/** @param array<int,array<string,string>> $rows */
function writeCsv(string $path, array $rows): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Cannot create report directory');
    $handle = fopen($path, 'wb');
    if (!$handle) throw new RuntimeException('Cannot write mapping report');
    fwrite($handle, "\xEF\xBB\xBF");
    $header = [
        'book_id', 'book_code', 'title', 'author', 'isbn', 'publisher', 'publication_year',
        'old_cover', 'source_page', 'source_cover_url', 'staged_file', 'target_cover',
        'sha256', 'mime', 'width', 'height', 'status', 'verification_basis', 'reason',
    ];
    fputcsv($handle, $header);
    foreach ($rows as $row) fputcsv($handle, array_map(static fn(string $key): string => (string) ($row[$key] ?? ''), $header));
    fclose($handle);
}

/** @return array<int,array<string,string>> */
function buildPreview(PDO $db, string $inputReport, string $root): array
{
    $sourceRows = array_values(array_filter(readCsv($inputReport), static fn(array $row): bool => ($row['status'] ?? '') === 'MISSING_NO_SAFE_MATCH'));
    $statement = $db->prepare('SELECT id, book_code, title, author, isbn, publisher, publication_year, cover_url, is_public FROM books WHERE id = ?');
    $rejectedGenerated = [
        'B006' => 'Migration 014 xác nhận file local cũ là bìa generated/artificial.',
        'B007' => 'Migration 014 xác nhận file local cũ là bìa generated/artificial.',
        'B009' => 'Migration 014 xác nhận file local cũ là bìa generated/artificial.',
    ];
    $researchedCandidates = [
        'B110' => [
            'source_page' => 'https://www.emiclib.com/Item/ItemDetail/121732',
            'status' => 'REVIEW_EDITION_YEAR_AND_LICENSE',
            'basis' => 'ISBN 9786041000698 khớp nhan đề, tác giả và NXB Trẻ; nguồn thư mục ghi năm 2016.',
            'reason' => 'MySQL ghi năm 2015 và chưa có quyền tái sử dụng ảnh bìa được công bố; cần xác minh đúng lần in.',
        ],
        'B504' => [
            'source_page' => 'https://omegaplus.vn/sung-vi-trung-va-thep-an-ban-bo-tui',
            'status' => 'REVIEW_EDITION_YEAR_AND_LICENSE',
            'basis' => 'Trang đơn vị phát hành khớp ISBN 9786047789313, nhan đề, tác giả và NXB Thế Giới.',
            'reason' => 'Trang hiện mô tả ấn bản bỏ túi năm 2026 trong khi MySQL ghi 2019; chưa có quyền tái sử dụng ảnh được công bố.',
        ],
        'B509' => [
            'source_page' => 'https://thuvien.hanoiacademy.edu.vn/courses/21-bai-hoc-cho-the-ky-21-yuval-noah-harari/',
            'status' => 'REVIEW_EDITION_YEAR_AND_LICENSE',
            'basis' => 'Bản ghi thư viện khớp ISBN 9786043922820, nhan đề, tác giả và NXB Thế Giới; ghi năm 2023.',
            'reason' => 'MySQL ghi năm 2019 và nguồn không công bố quyền tái sử dụng ảnh; cần xác minh đúng lần in.',
        ],
        'B603' => [
            'source_page' => 'https://muasachcu.com/sach/di-tim-le-song-tb-2022-sach-cu/',
            'status' => 'REVIEW_EDITION_YEAR_AND_LICENSE',
            'basis' => 'Trang ấn phẩm khớp ISBN 9786043356441, nhan đề và tác giả; ghi tái bản 2022.',
            'reason' => 'MySQL ghi năm 2018 và nguồn không công bố quyền tái sử dụng ảnh; cần xác minh đúng lần in.',
        ],
        'B803' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Tra cứu nguyên văn nhan đề, tác giả, NXB và năm xuất bản không tìm thấy bản ghi ấn phẩm tương ứng.',
            'reason' => 'Metadata có dấu hiệu là tiêu đề tổng hợp. Đề xuất Admin tạm ẩn khỏi danh mục công khai cho đến khi có ISBN hoặc bản chụp trang bản quyền.',
        ],
        'B804' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Chỉ tìm thấy tài liệu/chuyên đề gần tên của PGS.TS. Vũ Thị Phụng, không tìm thấy sách đúng nhan đề, NXB và năm 2020.',
            'reason' => 'Chưa xác minh được sự tồn tại của ấn phẩm. Đề xuất Admin tạm ẩn khỏi danh mục công khai cho đến khi có ISBN hoặc bản chụp trang bản quyền.',
        ],
        'B805' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Tra cứu nguyên văn nhan đề, tác giả, NXB và năm xuất bản không tìm thấy bản ghi ấn phẩm tương ứng.',
            'reason' => 'Metadata có dấu hiệu là tiêu đề tổng hợp. Đề xuất Admin tạm ẩn khỏi danh mục công khai cho đến khi có ISBN hoặc bản chụp trang bản quyền.',
        ],
        'B806' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Brian Tracy có sách Thuật quản lý thời gian; không tìm thấy ấn phẩm đúng nhan đề, NXB Lao Động Xã Hội và năm 2019 như MySQL.',
            'reason' => 'Không được dùng bìa của sách Brian Tracy gần giống. Đề xuất Admin tạm ẩn cho đến khi có ISBN hoặc bản chụp trang bản quyền.',
        ],
        'B807' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Tra cứu nguyên văn nhan đề, tác giả, NXB và năm xuất bản không tìm thấy bản ghi ấn phẩm tương ứng.',
            'reason' => 'Chưa xác minh được sự tồn tại của ấn phẩm; đề xuất Admin tạm ẩn khỏi danh mục công khai.',
        ],
        'B808' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Carmine Gallo có các sách thuyết trình khác; không tìm thấy sách đúng nhan đề và ấn bản đang lưu trong MySQL.',
            'reason' => 'Không được dùng bìa của sách gần giống; đề xuất Admin tạm ẩn cho đến khi xác minh metadata.',
        ],
        'B809' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Tra cứu chỉ thấy tài liệu pháp lý và sách khác của Nguyễn Ngọc Bích, không có ấn phẩm đúng nhan đề đang lưu.',
            'reason' => 'Chưa xác minh được sự tồn tại của ấn phẩm; đề xuất Admin tạm ẩn khỏi danh mục công khai.',
        ],
        'B810' => [
            'source_page' => '',
            'status' => 'REVIEW_EXISTENCE_UNVERIFIED',
            'basis' => 'Dave Ulrich có các công trình quản trị nhân sự khác; không tìm thấy sách đúng nhan đề, NXB và năm đang lưu.',
            'reason' => 'Không được dùng bìa của sách gần giống; đề xuất Admin tạm ẩn cho đến khi xác minh metadata.',
        ],
    ];
    $rows = [];
    foreach ($sourceRows as $sourceRow) {
        $statement->execute([(int) $sourceRow['book_id']]);
        $book = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$book || (int) $book['is_public'] !== 1 || trim((string) ($book['cover_url'] ?? '')) !== '') continue;
        $code = (string) $book['book_code'];
        $status = MISSING_STATUS;
        $staged = $target = $sha = $mime = $width = $height = '';
        $basis = 'Đã đối chiếu report 20261019, database, manifest cũ và file local theo mã sách.';
        $reason = trim((string) ($book['isbn'] ?? '')) === ''
            ? 'Thiếu ISBN hoặc mã ấn bản; chưa đủ căn cứ xác minh bìa chính xác theo tên, tác giả và NXB.'
            : 'Đã tra theo ISBN; chưa tìm được nguồn ảnh đúng ấn bản có quyền sử dụng phù hợp.';

        if (isset($rejectedGenerated[$code])) {
            $reason = $rejectedGenerated[$code] . ' Không được phép liên kết lại.';
        }

        $candidate = $researchedCandidates[$code] ?? null;
        if ($candidate) {
            $status = $candidate['status'];
            $basis = $candidate['basis'];
            $reason = $candidate['reason'];
        }

        $rows[] = [
            'book_id' => (string) $book['id'], 'book_code' => $code, 'title' => (string) $book['title'],
            'author' => (string) $book['author'], 'isbn' => (string) ($book['isbn'] ?? ''),
            'publisher' => (string) ($book['publisher'] ?? ''), 'publication_year' => (string) ($book['publication_year'] ?? ''),
            'old_cover' => (string) ($book['cover_url'] ?? ''),
            'source_page' => (string) ($candidate['source_page'] ?? ''), 'source_cover_url' => '',
            'staged_file' => $staged, 'target_cover' => $target, 'sha256' => $sha, 'mime' => $mime,
            'width' => $width, 'height' => $height, 'status' => $status,
            'verification_basis' => $basis, 'reason' => $reason,
        ];
    }
    return $rows;
}

/** @param array<int,array<string,string>> $rows */
function validateMappingRows(array $rows, string $root, bool $requireVerifiedFiles): array
{
    $errors = [];
    $seen = [];
    foreach ($rows as $index => $row) {
        $line = $index + 2;
        $id = filter_var($row['book_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || isset($seen[$id])) $errors[] = "Dòng {$line}: book_id thiếu hoặc trùng.";
        $seen[$id] = true;
        if (($row['status'] ?? '') !== VERIFIED_STATUS) continue;
        foreach (['source_page', 'source_cover_url', 'staged_file', 'target_cover', 'sha256', 'mime', 'width', 'height', 'verification_basis'] as $field) {
            if (trim((string) ($row[$field] ?? '')) === '') $errors[] = "Dòng {$line}: VERIFIED_READY thiếu {$field}.";
        }
        if (!filter_var($row['source_page'] ?? '', FILTER_VALIDATE_URL) || !str_starts_with((string) $row['source_page'], 'https://')) {
            $errors[] = "Dòng {$line}: source_page phải là HTTPS.";
        }
        if (!preg_match('#^/assets/book-covers/[A-Za-z0-9_-]+\.(?:jpg|png|webp)$#', (string) ($row['target_cover'] ?? ''))) {
            $errors[] = "Dòng {$line}: target_cover không an toàn.";
        }
        $stageRelative = str_replace('\\', '/', (string) ($row['staged_file'] ?? ''));
        if (!str_starts_with($stageRelative, 'storage/staged-book-covers/')) $errors[] = "Dòng {$line}: staged_file nằm ngoài vùng staging.";
        $stagePath = $root . '/' . $stageRelative;
        if ($requireVerifiedFiles && !is_file($stagePath)) {
            $errors[] = "Dòng {$line}: thiếu file staging.";
            continue;
        }
        if ($requireVerifiedFiles) {
            $info = @getimagesize($stagePath);
            $actualMime = (string) ($info['mime'] ?? '');
            if (!in_array($actualMime, ['image/jpeg', 'image/png', 'image/webp'], true)) $errors[] = "Dòng {$line}: MIME ảnh không hợp lệ.";
            if (($info[0] ?? 0) < 120 || ($info[1] ?? 0) < 160 || filesize($stagePath) > 5 * 1024 * 1024) $errors[] = "Dòng {$line}: kích thước ảnh không đạt.";
            if (!hash_equals((string) $row['sha256'], hash_file('sha256', $stagePath) ?: '')) $errors[] = "Dòng {$line}: SHA-256 không khớp.";
        }
    }
    return $errors;
}

/** @param array<int,array<string,string>> $rows */
function summary(array $rows): array
{
    $statuses = array_count_values(array_map(static fn(array $row): string => (string) ($row['status'] ?? ''), $rows));
    return [
        'total' => count($rows),
        'new_images_downloaded' => count(array_filter($rows, static fn(array $row): bool => ($row['status'] ?? '') === VERIFIED_STATUS && ($row['staged_file'] ?? '') !== '')),
        'verified_mapping' => $statuses[VERIFIED_STATUS] ?? 0,
        'review' => count(array_filter($rows, static fn(array $row): bool => str_starts_with((string) ($row['status'] ?? ''), 'REVIEW_'))),
        'missing' => $statuses[MISSING_STATUS] ?? 0,
    ];
}

if ($mode === '--preview') {
    if ($refresh || !is_file($mappingReport)) writeCsv($mappingReport, buildPreview($db, $inputReport, $root));
    $rows = readCsv($mappingReport);
    $errors = validateMappingRows($rows, $root, false);
    echo json_encode(['mode' => 'preview', 'report' => $mappingReport, 'summary' => summary($rows), 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($errors ? 1 : 0);
}

$rows = readCsv($mappingReport);
$errors = validateMappingRows($rows, $root, true);
if ($errors) {
    fwrite(STDERR, json_encode(['status' => 'FAIL', 'errors' => $errors], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
    exit(1);
}
$verifiedRows = array_values(array_filter($rows, static fn(array $row): bool => ($row['status'] ?? '') === VERIFIED_STATUS));

if ($mode === '--verify') {
    $results = [];
    $statement = $db->prepare('SELECT book_code, title, author, isbn, publisher, publication_year, cover_url FROM books WHERE id = ?');
    foreach ($verifiedRows as $row) {
        $statement->execute([(int) $row['book_id']]);
        $book = $statement->fetch(PDO::FETCH_ASSOC);
        $publicFile = $root . '/public' . $row['target_cover'];
        $results[] = [
            'book_id' => (int) $row['book_id'],
            'database_match' => $book && (string) $book['cover_url'] === (string) $row['target_cover'],
            'file_exists' => is_file($publicFile),
            'sha256_match' => is_file($publicFile) && hash_equals((string) $row['sha256'], hash_file('sha256', $publicFile) ?: ''),
        ];
    }
    echo json_encode(['mode' => 'verify', 'verified_rows' => count($verifiedRows), 'results' => $results, 'summary' => summary($rows)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

if (!$verifiedRows) {
    echo json_encode(['mode' => 'apply', 'status' => 'NO_VERIFIED_ROWS', 'message' => 'Không có mapping VERIFIED_READY; database không thay đổi.'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
}

require $root . '/database/backup.php';
$db = Database::connection();
$copied = [];
$db->beginTransaction();
try {
    $select = $db->prepare('SELECT book_code, title, author, isbn, publisher, publication_year, cover_url FROM books WHERE id = ? FOR UPDATE');
    $update = $db->prepare("UPDATE books SET cover_url = ? WHERE id = ? AND (cover_url IS NULL OR TRIM(cover_url) = '')");
    foreach ($verifiedRows as $row) {
        $select->execute([(int) $row['book_id']]);
        $book = $select->fetch(PDO::FETCH_ASSOC);
        if (!$book) throw new RuntimeException('Book disappeared: ' . $row['book_id']);
        foreach (['book_code', 'title', 'author', 'isbn', 'publisher', 'publication_year'] as $field) {
            if ((string) ($book[$field] ?? '') !== (string) ($row[$field] ?? '')) throw new RuntimeException('Metadata changed for book_id ' . $row['book_id']);
        }
        if (trim((string) ($book['cover_url'] ?? '')) !== '') throw new RuntimeException('Cover already assigned for book_id ' . $row['book_id']);
        $stagePath = $root . '/' . str_replace('\\', '/', $row['staged_file']);
        $targetPath = $root . '/public' . $row['target_cover'];
        if (is_file($targetPath)) throw new RuntimeException('Target already exists: ' . $row['target_cover']);
        $temporary = $targetPath . '.tmp-' . bin2hex(random_bytes(4));
        if (!copy($stagePath, $temporary) || !rename($temporary, $targetPath)) throw new RuntimeException('Cannot publish cover: ' . $row['target_cover']);
        $copied[] = $targetPath;
        $update->execute([$row['target_cover'], (int) $row['book_id']]);
        if ($update->rowCount() !== 1) throw new RuntimeException('Concurrent cover update for book_id ' . $row['book_id']);
        $log = $db->prepare("INSERT INTO activity_logs (user_id, action, subject_type, subject_id, details) VALUES (NULL, 'book_cover_batch_updated', 'book', ?, ?)");
        $log->execute([(int) $row['book_id'], 'Nguồn: ' . $row['source_page'] . '; SHA-256: ' . $row['sha256']]);
    }
    $db->commit();
    echo json_encode(['mode' => 'apply', 'status' => 'PASS', 'updated' => count($verifiedRows)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $exception) {
    if ($db->inTransaction()) $db->rollBack();
    foreach ($copied as $file) if (is_file($file)) @unlink($file);
    throw $exception;
}
