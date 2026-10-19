<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Core\Database;
use Library\Core\Session;
use Library\Repositories\ActivityRepository;
use PDO;
use Throwable;

/** Bulk import only registers book titles; physical copies must be received separately. */
final class BookImportController extends Controller
{
    private const PREVIEW_SESSION_KEY = 'paper_haven_book_import_preview';
    private const MAX_ROWS = 150;
    private const MAX_BYTES = 512000;

    public function index(): void
    {
        require_permission('books.create');
        $this->renderForm(null, []);
    }

    public function preview(): void
    {
        require_permission('books.create');
        validate_csrf();
        unset($_SESSION[self::PREVIEW_SESSION_KEY]);
        $errors = [];
        $db = Database::connection();
        $categories = $db->query('SELECT id, name FROM categories ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $catMap = [];
        foreach ($categories as $category) {
            $catMap[(string) $category['id']] = (int) $category['id'];
            $catMap[mb_strtolower(trim((string) $category['name']))] = (int) $category['id'];
        }
        $categoryInput = $_POST['default_category_id'] ?? '';
        $defaultId = (is_string($categoryInput) || is_int($categoryInput)) && ctype_digit((string) $categoryInput) ? (int) $categoryInput : 0;
        if (!isset($catMap[(string) $defaultId])) {
            $errors[] = 'Thể loại mặc định không hợp lệ.';
        }

        $rows = $this->readRows($errors);
        if ($rows === []) {
            $errors[] = 'Không tìm thấy dòng dữ liệu hợp lệ. Hãy kiểm tra file hoặc nội dung dán.';
        }
        if ($errors !== []) {
            $this->renderForm(null, $errors);
            return;
        }

        $existingIsbns = [];
        foreach ($db->query("SELECT isbn FROM books WHERE isbn IS NOT NULL AND isbn <> ''")->fetchAll(PDO::FETCH_COLUMN) as $isbn) {
            $existingIsbns[self::normalizeIsbn((string) $isbn)] = true;
        }
        $existingPairs = [];
        foreach ($db->query('SELECT title, author FROM books')->fetchAll(PDO::FETCH_ASSOC) as $book) {
            $existingPairs[self::titleKey((string) $book['title'], (string) $book['author'])] = true;
        }
        $batchIsbns = [];
        $batchPairs = [];
        $previewItems = [];
        foreach ($rows as $idx => $row) {
            $title = mb_substr(trim((string) ($row[0] ?? '')), 0, 240);
            $author = mb_substr(trim((string) ($row[1] ?? '')), 0, 190);
            $rawIsbn = trim((string) ($row[2] ?? ''));
            $publisher = trim((string) ($row[3] ?? ''));
            $yearText = trim((string) ($row[4] ?? ''));
            $categoryValue = mb_strtolower(trim((string) ($row[5] ?? '')));
            $categoryId = $catMap[$categoryValue] ?? $defaultId;
            $status = 'VALID';
            $note = 'Sẵn sàng nhập đầu sách (0 bản sao)';
            $isbn = null;
            $year = null;

            if ($title === '' || $author === '') {
                $status = 'INVALID'; $note = 'Thiếu tên sách hoặc tác giả';
            }
            if ($rawIsbn !== '') {
                $normalized = self::normalizeIsbn($rawIsbn);
                if (!self::validIsbn($normalized)) {
                    $status = 'INVALID'; $note = 'ISBN không hợp lệ. Mã EAN 893/SKU không phải ISBN';
                } else {
                    $isbn = $normalized;
                }
            }
            if ($yearText !== '') {
                if (!preg_match('/^[0-9]{4}$/', $yearText) || (int) $yearText < 1450 || (int) $yearText > (int) date('Y') + 1) {
                    $status = 'INVALID'; $note = 'Năm xuất bản không hợp lệ';
                } else {
                    $year = (int) $yearText;
                }
            }
            if (!isset($catMap[(string) $categoryId])) {
                $status = 'INVALID'; $note = 'Thể loại không hợp lệ';
            }
            if (mb_strlen($publisher) > 190) {
                $status = 'INVALID'; $note = 'Tên nhà xuất bản quá dài';
            }
            if ($isbn !== null && (isset($existingIsbns[$isbn]) || isset($batchIsbns[$isbn]))) {
                $status = 'DUPLICATE_ISBN'; $note = 'ISBN trùng với danh mục hoặc với dòng khác';
            }
            $pair = self::titleKey($title, $author);
            if (isset($existingPairs[$pair]) || isset($batchPairs[$pair])) {
                $status = 'DUPLICATE_TITLE'; $note = 'Trùng tên sách và tác giả';
            }
            if ($isbn !== null) $batchIsbns[$isbn] = true;
            $batchPairs[$pair] = true;

            $previewItems[] = [
                'index' => $idx + 1, 'book_code' => 'Tự cấp khi lưu',
                'title' => $title, 'author' => $author, 'isbn' => $isbn,
                'publisher' => $publisher === '' ? null : $publisher,
                'publication_year' => $year, 'category_id' => $categoryId,
                'copies_count' => 0, 'shelf_location' => null,
                'status' => $status, 'note' => $note,
            ];
        }
        $previewToken = bin2hex(random_bytes(24));
        $_SESSION[self::PREVIEW_SESSION_KEY] = [
            'token' => $previewToken, 'created_at' => time(), 'items' => $previewItems,
        ];
        $this->renderForm($previewItems, [], $previewToken);
    }

    public function commit(): void
    {
        require_permission('books.create');
        validate_csrf();
        $preview = $_SESSION[self::PREVIEW_SESSION_KEY] ?? null;
        $token = $_POST['preview_token'] ?? null;
        if (!is_array($preview) || !is_string($token) ||
            !hash_equals((string) ($preview['token'] ?? ''), $token) ||
            time() - (int) ($preview['created_at'] ?? 0) > 900) {
            Session::flash('error', 'Phiên xem trước đã hết hạn hoặc không hợp lệ. Vui lòng kiểm tra lại dữ liệu.');
            redirect('/admin/books/import');
            return;
        }
        // Never trust a hidden JSON field: use exclusively the validated, server-side preview.
        unset($_SESSION[self::PREVIEW_SESSION_KEY]);
        $items = array_values(array_filter($preview['items'] ?? [], static fn($row) =>
            is_array($row) && ($row['status'] ?? '') === 'VALID'
        ));
        if ($items === []) {
            Session::flash('error', 'Không có đầu sách hợp lệ để nhập.');
            redirect('/admin/books/import');
            return;
        }

        $db = Database::connection();
        $imported = 0;
        $skipped = 0;
        try {
            $db->beginTransaction();
            $findPair = $db->prepare('SELECT id FROM books WHERE LOWER(TRIM(title)) = LOWER(TRIM(?)) AND LOWER(TRIM(author)) = LOWER(TRIM(?)) LIMIT 1');
            $findIsbn = $db->prepare('SELECT id FROM books WHERE REPLACE(REPLACE(isbn, "-", ""), " ", "") = ? LIMIT 1');
            $insert = $db->prepare('INSERT INTO books (book_code, title, author, isbn, category_id, publisher, publication_year, description, total_copies, cover_url, is_active, is_public, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 0, NULL, 1, 1, NOW(), NOW())');
            foreach ($items as $item) {
                $findPair->execute([$item['title'], $item['author']]);
                if ($findPair->fetchColumn()) { $skipped++; continue; }
                $isbn = $item['isbn'] ?? null;
                if ($isbn !== null) {
                    if (!self::validIsbn((string) $isbn)) { $skipped++; continue; }
                    $findIsbn->execute([$isbn]);
                    if ($findIsbn->fetchColumn()) { $skipped++; continue; }
                }
                // Internal catalog code is not an ISBN or a physical copy identifier.
                $code = 'IMP-' . strtoupper(bin2hex(random_bytes(8)));
                $insert->execute([$code, $item['title'], $item['author'], $isbn,
                    (int) $item['category_id'], $item['publisher'], $item['publication_year']]);
                $imported++;
            }
            if ($imported === 0) {
                $db->rollBack();
                Session::flash('error', 'Tất cả đầu sách đã tồn tại, không có bản ghi mới được thêm.');
                redirect('/admin/books/import');
                return;
            }
            $user = current_user();
            (new ActivityRepository($db))->log((int) ($user['id'] ?? 0), 'import_books', 'books', null,
                "Nhập {$imported} đầu sách, bỏ qua {$skipped} trùng; không tự tạo bản sao vật lý.");
            $db->commit();
            Session::flash('success', "Đã thêm {$imported} đầu sách vào danh mục (0 bản sao); bỏ qua {$skipped} sách trùng. Thủ thư nhập bản sao riêng khi tiếp nhận sách.");
            redirect('/admin/books');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('Catalog import failed: ' . $e::class . ': ' . $e->getMessage());
            Session::flash('error', 'Nhập sách thất bại. Dữ liệu chưa được lưu; vui lòng kiểm tra lại danh mục và thử lại.');
            redirect('/admin/books/import');
        }
    }

    private function readRows(array &$errors): array
    {
        $text = $_POST['raw_data'] ?? '';
        $rows = [];
        $upload = $_FILES['csv_file'] ?? null;
        if (is_array($upload) && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (($upload['error'] ?? -1) !== UPLOAD_ERR_OK ||
                !is_string($upload['tmp_name'] ?? null) ||
                !is_uploaded_file($upload['tmp_name']) ||
                (int) ($upload['size'] ?? 0) > self::MAX_BYTES ||
                strtolower(pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
                $errors[] = 'File phải là CSV hợp lệ, dung lượng tối đa 500 KB.';
                return [];
            }
            $handle = fopen($upload['tmp_name'], 'rb');
            if ($handle === false) { $errors[] = 'Không đọc được file CSV.'; return []; }
            while (($fields = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($fields) === 1 && trim((string) $fields[0]) === '') continue;
                $rows[] = $fields;
                if (count($rows) > self::MAX_ROWS) break;
            }
            fclose($handle);
            if ($rows) {
                $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $rows[0][0]);
                if (preg_match('/^(tựa sách|tên sách|title)$/iu', trim((string) $rows[0][0]))) array_shift($rows);
            }
        } elseif (is_string($text) && $text !== '') {
            if (strlen($text) > self::MAX_BYTES) { $errors[] = 'Nội dung dán quá dài.'; return []; }
            foreach (preg_split('/\r\n|\n|\r/', $text) as $line) {
                if (trim($line) === '' || str_starts_with(ltrim($line), '#')) continue;
                $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, '|') ? '|' : ',');
                $rows[] = str_getcsv($line, $delimiter, '"', '');
                if (count($rows) > self::MAX_ROWS) break;
            }
        } elseif ($text !== '' && !is_string($text)) {
            $errors[] = 'Định dạng nội dung không hợp lệ.';
        }
        if (count($rows) > self::MAX_ROWS) {
            $errors[] = 'Mỗi lần chỉ nhập tối đa ' . self::MAX_ROWS . ' đầu sách.';
            return [];
        }
        return $rows;
    }

    private function renderForm(?array $items, array $errors, string $token = ''): void
    {
        $this->render('admin/book_import', [
            'title' => $items === null ? 'Nhập sách hàng loạt an toàn' : 'Xem trước dữ liệu nhập hàng loạt',
            'categories' => Database::connection()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC),
            'previewItems' => $items, 'errors' => $errors, 'previewToken' => $token,
        ]);
    }

    public static function normalizeIsbn(string $isbn): string
    {
        return strtoupper(preg_replace('/[\s-]/', '', $isbn));
    }

    public static function validIsbn(string $isbn): bool
    {
        $isbn = self::normalizeIsbn($isbn);
        if (preg_match('/^97[89][0-9]{10}$/', $isbn)) {
            $sum = 0;
            for ($i = 0; $i < 12; $i++) $sum += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
            return (10 - $sum % 10) % 10 === (int) $isbn[12];
        }
        if (!preg_match('/^[0-9]{9}[0-9X]$/', $isbn)) return false;
        $sum = 0;
        for ($i = 0; $i < 10; $i++) $sum += ($isbn[$i] === 'X' ? 10 : (int) $isbn[$i]) * (10 - $i);
        return $sum % 11 === 0;
    }

    private static function titleKey(string $title, string $author): string
    {
        return mb_strtolower(trim($title)) . '|||' . mb_strtolower(trim($author));
    }
}
