<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Core\Database;
use Library\Core\Pagination;
use Library\Repositories\ActivityRepository;
use Library\Services\IsbnValidator;
use PDO;
use Throwable;

final class MetadataQualityController extends Controller
{
    private const FILTERS = [
        'all', 'missing_isbn', 'invalid_isbn', 'not_applicable', 'missing_cover',
        'missing_desc', 'zero_copies', 'duplicate_isbn', 'demo',
    ];

    public function index(): void
    {
        require_permission('books.view');
        $db = Database::connection();
        $filter = (string) ($_GET['filter'] ?? 'all');
        if (!in_array($filter, self::FILTERS, true)) $filter = 'all';
        $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 160);

        $notApplicableSources = $this->notApplicableSources();
        $catalogRows = $db->query('SELECT id, book_code, isbn FROM books')->fetchAll(PDO::FETCH_ASSOC);
        $validIsbnIds = [];
        $invalidIsbnIds = [];
        $missingIsbnIds = [];
        $notApplicableIds = [];
        $normalizedGroups = [];

        foreach ($catalogRows as $row) {
            $id = (int) $row['id'];
            $isbn = IsbnValidator::normalize($row['isbn']);
            if ($isbn !== '') {
                $normalizedGroups[$isbn][] = $id;
                if (IsbnValidator::isValid($isbn)) $validIsbnIds[] = $id;
                else $invalidIsbnIds[] = $id;
            } elseif (isset($notApplicableSources[(string) $row['book_code']])) {
                $notApplicableIds[] = $id;
            } else {
                $missingIsbnIds[] = $id;
            }
        }

        $duplicateIsbns = [];
        $duplicateBookIds = [];
        foreach ($normalizedGroups as $isbn => $ids) {
            if (count($ids) < 2) continue;
            $duplicateIsbns[] = ['isbn' => $isbn, 'cnt' => count($ids)];
            array_push($duplicateBookIds, ...$ids);
        }

        $totalBooks = count($catalogRows);
        $publicBooks = (int) $db->query('SELECT COUNT(*) FROM books WHERE is_public = 1')->fetchColumn();
        $missingCover = (int) $db->query("SELECT COUNT(*) FROM books WHERE cover_url IS NULL OR TRIM(cover_url) = ''")->fetchColumn();
        $missingDesc = (int) $db->query("SELECT COUNT(*) FROM books WHERE description IS NULL OR CHAR_LENGTH(TRIM(description)) < 40")->fetchColumn();
        $missingPublisher = (int) $db->query("SELECT COUNT(*) FROM books WHERE publisher IS NULL OR TRIM(publisher) = ''")->fetchColumn();
        $zeroCopies = (int) $db->query('SELECT COUNT(*) FROM books b WHERE NOT EXISTS (SELECT 1 FROM book_copies bc WHERE bc.book_id = b.id)')->fetchColumn();
        $eligibleCopyTotal = max(0, $totalBooks - count($notApplicableIds));
        $eligibleWithCopies = $eligibleCopyTotal - count(array_intersect(
            array_map('intval', $db->query('SELECT b.id FROM books b WHERE NOT EXISTS (SELECT 1 FROM book_copies bc WHERE bc.book_id = b.id)')->fetchAll(PDO::FETCH_COLUMN)),
            array_values(array_diff(array_map(static fn(array $row): int => (int) $row['id'], $catalogRows), $notApplicableIds))
        ));

        $copyStatusCounts = [];
        foreach ($db->query('SELECT status, COUNT(*) AS count FROM book_copies GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $copyStatusCounts[(string) $row['status']] = (int) $row['count'];
        }
        $totalCopies = array_sum($copyStatusCounts);
        $availableCopies = $copyStatusCounts['available'] ?? 0;
        $pendingAuditCopies = $copyStatusCounts['pending_audit'] ?? 0;
        $otherCopies = $totalCopies - $availableCopies - $pendingAuditCopies;

        $applicableIsbnTotal = max(0, $totalBooks - count($notApplicableIds));
        $components = [
            'isbn' => ['label' => 'ISBN hợp lệ', 'weight' => 25, 'rate' => $this->rate(count($validIsbnIds), $applicableIsbnTotal)],
            'cover' => ['label' => 'Ảnh bìa', 'weight' => 20, 'rate' => $this->rate($totalBooks - $missingCover, $totalBooks)],
            'description' => ['label' => 'Mô tả', 'weight' => 25, 'rate' => $this->rate($totalBooks - $missingDesc, $totalBooks)],
            'publisher' => ['label' => 'Nhà xuất bản', 'weight' => 15, 'rate' => $this->rate($totalBooks - $missingPublisher, $totalBooks)],
            'copies' => ['label' => 'Bản sao vật lý', 'weight' => 15, 'rate' => $this->rate($eligibleWithCopies, $eligibleCopyTotal)],
        ];
        $completenessScore = (int) round(array_sum(array_map(
            static fn(array $component): float => $component['rate'] * $component['weight'],
            $components
        )));
        $qualityLevel = $completenessScore >= 90 ? 'Đạt chuẩn' : ($completenessScore >= 75 ? 'Cần hoàn thiện' : 'Cần ưu tiên');
        $qualityClass = $completenessScore >= 90 ? 'is-good' : ($completenessScore >= 75 ? 'is-warning' : 'is-critical');

        $where = ['1=1'];
        $params = [];
        if ($search !== '') {
            $where[] = '(b.title LIKE ? OR b.author LIKE ? OR b.book_code LIKE ? OR b.isbn LIKE ?)';
            array_push($params, ...array_fill(0, 4, '%' . $search . '%'));
        }
        $filterIds = match ($filter) {
            'missing_isbn' => $missingIsbnIds,
            'invalid_isbn' => $invalidIsbnIds,
            'not_applicable' => $notApplicableIds,
            'duplicate_isbn' => $duplicateBookIds,
            default => null,
        };
        if (is_array($filterIds)) {
            $where[] = $filterIds ? 'b.id IN (' . implode(',', array_fill(0, count($filterIds), '?')) . ')' : '1=0';
            array_push($params, ...$filterIds);
        } elseif ($filter === 'missing_cover') {
            $where[] = "(b.cover_url IS NULL OR TRIM(b.cover_url) = '')";
        } elseif ($filter === 'missing_desc') {
            $where[] = "(b.description IS NULL OR CHAR_LENGTH(TRIM(b.description)) < 40)";
        } elseif ($filter === 'zero_copies') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM book_copies bc WHERE bc.book_id = b.id)';
        } elseif ($filter === 'demo') {
            $where[] = "b.book_code LIKE 'DEMO-%'";
        }

        $whereSql = implode(' AND ', $where);
        $countStatement = $db->prepare('SELECT COUNT(*) FROM books b WHERE ' . $whereSql);
        $countStatement->execute($params);
        $pagination = new Pagination((int) $countStatement->fetchColumn(), 25);

        $isbnProblemIds = array_values(array_unique(array_merge($missingIsbnIds, $invalidIsbnIds)));
        $isbnIssueSql = $isbnProblemIds ? 'b.id IN (' . implode(',', array_map('intval', $isbnProblemIds)) . ')' : '0';
        $issueExpression = '(CASE WHEN ' . $isbnIssueSql . ' THEN 1 ELSE 0 END'
            . " + CASE WHEN b.cover_url IS NULL OR TRIM(b.cover_url) = '' THEN 1 ELSE 0 END"
            . " + CASE WHEN b.description IS NULL OR CHAR_LENGTH(TRIM(b.description)) < 40 THEN 1 ELSE 0 END"
            . " + CASE WHEN b.publisher IS NULL OR TRIM(b.publisher) = '' THEN 1 ELSE 0 END"
            . ' + CASE WHEN NOT EXISTS (SELECT 1 FROM book_copies issue_cp WHERE issue_cp.book_id = b.id) THEN 1 ELSE 0 END)';
        $sql = "SELECT b.id, b.book_code, b.title, b.author, b.isbn, b.publisher, b.publication_year,
                       b.description, b.cover_url, b.is_public, c.name AS category_name,
                       (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) AS copies_count,
                       (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'available') AS available_count,
                       {$issueExpression} AS issue_count
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE {$whereSql}
                ORDER BY issue_count DESC, b.updated_at ASC, b.id ASC
                LIMIT {$pagination->perPage} OFFSET {$pagination->offset}";
        $statement = $db->prepare($sql);
        $statement->execute($params);
        $books = $statement->fetchAll(PDO::FETCH_ASSOC);
        $validLookup = array_fill_keys($validIsbnIds, true);
        $invalidLookup = array_fill_keys($invalidIsbnIds, true);
        $notApplicableLookup = array_fill_keys($notApplicableIds, true);
        $duplicateLookup = array_fill_keys($duplicateBookIds, true);
        foreach ($books as &$book) {
            $id = (int) $book['id'];
            if (isset($duplicateLookup[$id])) {
                $book['isbn_status'] = 'duplicate';
                $book['isbn_status_label'] = 'Trùng ISBN cần xem xét';
            } elseif (isset($validLookup[$id])) {
                $book['isbn_status'] = 'valid';
                $book['isbn_status_label'] = 'ISBN hợp lệ';
            } elseif (isset($invalidLookup[$id])) {
                $book['isbn_status'] = 'invalid';
                $book['isbn_status_label'] = 'ISBN không hợp lệ';
            } elseif (isset($notApplicableLookup[$id])) {
                $book['isbn_status'] = 'not_applicable';
                $book['isbn_status_label'] = 'Không áp dụng ISBN';
                $book['alternative_identifier'] = $notApplicableSources[(string) $book['book_code']] ?? '';
            } else {
                $book['isbn_status'] = 'missing';
                $book['isbn_status_label'] = 'Thiếu ISBN cần xác minh';
            }
        }
        unset($book);

        $this->render('admin/metadata_quality', [
            'title' => 'Kiểm toán chất lượng Metadata',
            'totalBooks' => $totalBooks,
            'publicBooks' => $publicBooks,
            'validIsbn' => count($validIsbnIds),
            'invalidIsbn' => count($invalidIsbnIds),
            'missingIsbn' => count($missingIsbnIds),
            'notApplicableIsbn' => count($notApplicableIds),
            'missingCover' => $missingCover,
            'missingDesc' => $missingDesc,
            'missingPublisher' => $missingPublisher,
            'zeroCopies' => $zeroCopies,
            'totalCopies' => $totalCopies,
            'availableCopies' => $availableCopies,
            'pendingAuditCopies' => $pendingAuditCopies,
            'otherCopies' => $otherCopies,
            'copyStatusCounts' => $copyStatusCounts,
            'completenessScore' => $completenessScore,
            'scoreComponents' => $components,
            'qualityLevel' => $qualityLevel,
            'qualityClass' => $qualityClass,
            'duplicateIsbns' => $duplicateIsbns,
            'books' => $books,
            'filter' => $filter,
            'search' => $search,
            'pagination' => $pagination,
            'paginationAnchor' => 'audit-results',
            'canEditIsbn' => (current_user()['role'] ?? '') === 'admin' && can('books.update'),
        ]);
    }

    public function updateIsbn(): never
    {
        $user = require_auth(['admin']);
        validate_csrf();
        $id = request_int('id', 1);
        $source = request_string('verification_source', 500, true);
        $isbn = IsbnValidator::normalize(request_string('isbn', 24, true));
        if (!IsbnValidator::isValid($isbn)) {
            $this->fail('ISBN không hợp lệ: vui lòng kiểm tra ISBN-10/ISBN-13 và số kiểm tra.', '/admin/metadata-quality');
        }
        if (mb_strlen($source) < 10) {
            $this->fail('Cần ghi rõ nguồn xác minh đúng ấn bản trước khi cập nhật ISBN.', '/admin/metadata-quality');
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT id, title, isbn FROM books WHERE id = ? FOR UPDATE');
            $statement->execute([$id]);
            $book = $statement->fetch(PDO::FETCH_ASSOC);
            if (!$book) throw new InvalidArgumentException('Không tìm thấy đầu sách cần cập nhật.');
            $oldIsbn = IsbnValidator::normalize($book['isbn']);
            if ($oldIsbn !== '' && IsbnValidator::isValid($oldIsbn) && $oldIsbn !== $isbn) {
                throw new InvalidArgumentException('ISBN hiện tại đang hợp lệ; không thể thay đổi tại màn hình kiểm toán khi chưa có quy trình đối soát chuyên biệt.');
            }
            $duplicates = $this->db->prepare("SELECT id, isbn FROM books WHERE id <> ? AND isbn IS NOT NULL AND TRIM(isbn) <> '' FOR UPDATE");
            $duplicates->execute([$id]);
            foreach ($duplicates->fetchAll(PDO::FETCH_ASSOC) as $candidate) {
                if (IsbnValidator::normalize($candidate['isbn']) === $isbn) {
                    throw new InvalidArgumentException('ISBN này đã được sử dụng bởi một đầu sách khác.');
                }
            }
            $update = $this->db->prepare('UPDATE books SET isbn = ? WHERE id = ?');
            $update->execute([$isbn, $id]);
            (new ActivityRepository($this->db))->log(
                (int) $user['id'],
                'book_isbn_updated',
                'book',
                $id,
                'ISBN: ' . ($oldIsbn ?: 'trống') . ' → ' . $isbn . '; nguồn xác minh: ' . $source
            );
            $this->db->commit();
            $this->success('Đã cập nhật ISBN hợp lệ và ghi nhật ký nguồn xác minh.', '/admin/metadata-quality');
        } catch (InvalidArgumentException $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($exception->getMessage(), '/admin/metadata-quality');
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            error_log('Metadata ISBN update error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật ISBN lúc này.', '/admin/metadata-quality');
        }
    }

    private function rate(int $complete, int $applicable): float
    {
        return $applicable > 0 ? max(0, min(1, $complete / $applicable)) : 1.0;
    }

    /** @return array<string,string> */
    private function notApplicableSources(): array
    {
        $sources = [];
        foreach (['real_catalog_manifest.json', 'category_catalog_manifest.json'] as $filename) {
            $path = dirname(__DIR__, 2) . '/database/' . $filename;
            $rows = is_file($path) ? json_decode((string) file_get_contents($path), true) : [];
            foreach (is_array($rows) ? $rows : [] as $row) {
                $code = (string) ($row['book_code'] ?? '');
                $source = (string) ($row['source_url'] ?? '');
                $isbn = IsbnValidator::normalize($row['isbn'] ?? null);
                $hasOpenIdentifier = $source !== '' && filter_var($source, FILTER_VALIDATE_URL)
                    && (!empty($row['license']) || str_contains(strtolower((string) ($row['publisher'] ?? '')), 'standard ebooks'));
                if ($code !== '' && $isbn === '' && $hasOpenIdentifier) $sources[$code] = $source;
            }
        }
        return $sources;
    }
}
