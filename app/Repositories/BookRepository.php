<?php
declare(strict_types=1);

namespace Library\Repositories;

use InvalidArgumentException;
use PDO;
use Throwable;

final class BookRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function search(string $query = '', int $categoryId = 0, string $availability = '', ?int $limit = null, int $offset = 0, string $sort = 'newest'): array
    {
        $sql = $this->selectSql() . ' WHERE b.is_active = 1';
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (b.book_code LIKE :search_code OR b.title LIKE :search_title OR b.author LIKE :search_author OR b.isbn LIKE :search_isbn OR c.name LIKE :search_category)';
            $search = '%' . $query . '%';
            $params += [
                'search_code' => $search,
                'search_title' => $search,
                'search_author' => $search,
                'search_isbn' => $search,
                'search_category' => $search,
            ];
        }
        if ($categoryId > 0) {
            $sql .= ' AND b.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($availability === 'available') {
            $sql .= ' AND (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") >
                (SELECT COUNT(*) FROM reservations r WHERE r.book_id = b.id AND r.status = "ready"
                 AND r.book_copy_id IS NULL AND (r.expires_at IS NULL OR r.expires_at >= NOW()))';
        } elseif ($availability === 'unavailable') {
            $sql .= ' AND (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") <=
                (SELECT COUNT(*) FROM reservations r WHERE r.book_id = b.id AND r.status = "ready"
                 AND r.book_copy_id IS NULL AND (r.expires_at IS NULL OR r.expires_at >= NOW()))';
        }
        $orders = ['newest' => 'b.updated_at DESC, b.id DESC', 'title' => 'b.title ASC, b.id ASC', 'author' => 'b.author ASC, b.id ASC'];
        $sql .= ' ORDER BY ' . ($orders[$sort] ?? $orders['newest']);
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset);
        }

        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countSearch(string $query = '', int $categoryId = 0, string $availability = ''): int
    {
        $sql = 'SELECT COUNT(*) FROM books b LEFT JOIN categories c ON c.id = b.category_id WHERE b.is_active = 1';
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (b.book_code LIKE ? OR b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR c.name LIKE ?)';
            $params = array_fill(0, 5, '%' . $query . '%');
        }
        if ($categoryId > 0) { $sql .= ' AND b.category_id = ?'; $params[] = $categoryId; }
        if (in_array($availability, ['available', 'unavailable'], true)) {
            $sql .= ' AND (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id=b.id AND cp.status="available") ' .
                ($availability === 'available' ? '>' : '<=') .
                ' (SELECT COUNT(*) FROM reservations r WHERE r.book_id=b.id AND r.status="ready" AND r.book_copy_id IS NULL AND (r.expires_at IS NULL OR r.expires_at >= NOW()))';
        }
        $stmt = $this->db->prepare($sql); $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare($this->selectSql() . ' WHERE b.id = :id AND b.is_active = 1');
        $statement->execute(['id' => $id]);
        $book = $statement->fetch();
        return $book ?: null;
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, title, total_copies, is_active FROM books WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $book = $statement->fetch();
        return $book ?: null;
    }

    public function allCategories(): array
    {
        return $this->db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }

    public function create(array $data): int
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $statement = $this->db->prepare(
                'INSERT INTO books (book_code, title, author, isbn, category_id, publisher, publication_year, description, total_copies, cover_url)
                 VALUES (:book_code, :title, :author, :isbn, :category_id, :publisher, :publication_year, :description, :total_copies, :cover_url)'
            );
            $statement->execute($data);
            $id = (int) $this->db->lastInsertId();
            $this->reconcileCopies($id, $data['book_code'], (int) $data['total_copies']);
            $this->syncMetadata($id, (string) $data['author'], (string) ($data['publisher'] ?? ''));
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return $id;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function update(int $id, array $data): void
    {
        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $this->reconcileCopies($id, (string) $data['book_code'], (int) $data['total_copies']);
            $data['id'] = $id;
            $statement = $this->db->prepare(
                'UPDATE books SET book_code = :book_code, title = :title, author = :author, isbn = :isbn,
                 category_id = :category_id, publisher = :publisher, publication_year = :publication_year,
                 description = :description, total_copies = :total_copies, cover_url = :cover_url WHERE id = :id'
            );
            $statement->execute($data);
            $this->syncMetadata($id, (string) $data['author'], (string) ($data['publisher'] ?? ''));
            if ($ownsTransaction) {
                $this->db->commit();
            }
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('UPDATE books SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function deleteConflicts(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM loan_items li JOIN book_copies cp ON cp.id=li.book_copy_id WHERE cp.book_id = :loan_book AND li.returned_at IS NULL)
             OR EXISTS(SELECT 1 FROM reservations WHERE book_id = :reservation_book AND status IN ("pending", "ready"))'
        );
        $statement->execute(['loan_book' => $id, 'reservation_book' => $id]);
        return (bool) $statement->fetchColumn();
    }

    public function countActiveLoans(int $id): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM book_copies WHERE book_id = :id AND status = "on_loan"');
        $statement->execute(['id' => $id]);
        return (int) $statement->fetchColumn();
    }

    public function countHeldCopies(int $id): int
    {
        $statement = $this->db->prepare(
            'SELECT
              (SELECT COUNT(*) FROM book_copies WHERE book_id = :loan_id AND status = "on_loan") +
              (SELECT COUNT(*) FROM reservations WHERE book_id = :reservation_id AND status = "ready"
               AND (expires_at IS NULL OR expires_at >= NOW()))'
        );
        $statement->execute(['loan_id' => $id, 'reservation_id' => $id]);
        return (int) $statement->fetchColumn();
    }

    public function popular(int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        $statement = $this->db->query(
            $this->selectSql() . ' WHERE b.is_active = 1
             ORDER BY (SELECT COUNT(*) FROM loan_items li
                       JOIN book_copies cp ON cp.id = li.book_copy_id
                       WHERE cp.book_id = b.id) DESC, b.title LIMIT ' . $limit
        );
        return $statement->fetchAll();
    }

    public function metrics(): array
    {
        $statement = $this->db->query(
            'SELECT COUNT(*) AS title_count,
             COALESCE(SUM(copy_count), 0) AS copy_count,
             COALESCE(SUM(available_count), 0) AS available_count,
             COALESCE(SUM(loaned_count), 0) AS loaned_count
             FROM (' . $this->inventorySql() . ') inventory'
        );
        return $statement->fetch() ?: ['title_count' => 0, 'copy_count' => 0, 'available_count' => 0, 'loaned_count' => 0];
    }

    private function selectSql(): string
    {
        return 'SELECT b.*, c.name AS category_name,
            (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "on_loan") AS active_loans,
            (SELECT COUNT(*) FROM reservations r WHERE r.book_id = b.id AND r.status = "ready" AND (r.expires_at IS NULL OR r.expires_at >= NOW())) AS active_reservations,
            (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status <> "retired") AS total_copies,
            GREATEST(
              (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") -
              (SELECT COUNT(*) FROM reservations r WHERE r.book_id = b.id AND r.status = "ready"
               AND r.book_copy_id IS NULL AND (r.expires_at IS NULL OR r.expires_at >= NOW())), 0) AS available_copies
            FROM books b LEFT JOIN categories c ON c.id = b.category_id';
    }

    private function syncMetadata(int $bookId, string $authors, string $publisher): void
    {
        $authorNames = array_values(array_unique(array_filter(
            array_map('trim', explode(';', $authors)),
            static fn(string $name): bool => $name !== ''
        )));
        if (!$authorNames) {
            throw new InvalidArgumentException('Vui lòng nhập ít nhất một tác giả.');
        }
        $canonicalAuthors = implode('; ', $authorNames);
        if (mb_strlen($canonicalAuthors) > 190) {
            throw new InvalidArgumentException('Tổng tên tác giả không được vượt quá 190 ký tự.');
        }

        $this->db->prepare('DELETE FROM book_authors WHERE book_id = :book_id')->execute(['book_id' => $bookId]);
        $upsertAuthor = $this->db->prepare(
            'INSERT INTO authors (name) VALUES (:name)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $linkAuthor = $this->db->prepare('INSERT IGNORE INTO book_authors (book_id, author_id) VALUES (:book_id, :author_id)');
        foreach ($authorNames as $name) {
            $upsertAuthor->execute(['name' => $name]);
            $authorId = (int) $this->db->lastInsertId();
            if ($authorId < 1) {
                $lookup = $this->db->prepare('SELECT id FROM authors WHERE name = :name');
                $lookup->execute(['name' => $name]);
                $authorId = (int) $lookup->fetchColumn();
            }
            if ($authorId < 1) {
                throw new \RuntimeException('Không thể lưu tác giả sách.');
            }
            $linkAuthor->execute(['book_id' => $bookId, 'author_id' => $authorId]);
        }

        $publisher = trim($publisher);
        $publisherId = null;
        if ($publisher !== '') {
            $upsertPublisher = $this->db->prepare(
                'INSERT INTO publishers (name) VALUES (:name)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $upsertPublisher->execute(['name' => $publisher]);
            $publisherId = (int) $this->db->lastInsertId();
            if ($publisherId < 1) {
                $lookup = $this->db->prepare('SELECT id FROM publishers WHERE name = :name');
                $lookup->execute(['name' => $publisher]);
                $publisherId = (int) $lookup->fetchColumn();
            }
        }

        $statement = $this->db->prepare(
            'UPDATE books SET author = :author, publisher = :publisher, publisher_id = :publisher_id WHERE id = :book_id'
        );
        $statement->execute([
            'author' => $canonicalAuthors,
            'publisher' => $publisher !== '' ? $publisher : null,
            'publisher_id' => $publisherId,
            'book_id' => $bookId,
        ]);
    }

    private function inventorySql(): string
    {
        return 'SELECT b.id,
            (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status <> "retired") AS copy_count,
            GREATEST(
              (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "available") -
              (SELECT COUNT(*) FROM reservations r WHERE r.book_id = b.id AND r.status = "ready"
               AND r.book_copy_id IS NULL AND (r.expires_at IS NULL OR r.expires_at >= NOW())), 0) AS available_count,
            (SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id = b.id AND cp.status = "on_loan") AS loaned_count
            FROM books b WHERE b.is_active = 1';
    }

    private function reconcileCopies(int $bookId, string $bookCode, int $targetCount): void
    {
        $statement = $this->db->prepare(
            'SELECT id, copy_code, status FROM book_copies WHERE book_id = :book_id ORDER BY id FOR UPDATE'
        );
        $statement->execute(['book_id' => $bookId]);
        $copies = $statement->fetchAll();
        $activeCopies = array_values(array_filter($copies, static fn(array $copy): bool => $copy['status'] !== 'retired'));
        $difference = $targetCount - count($activeCopies);

        if ($difference < 0) {
            $available = array_values(array_filter($activeCopies, static fn(array $copy): bool => $copy['status'] === 'available'));
            if (count($available) < -$difference) {
                throw new \InvalidArgumentException('Chỉ có thể giảm tồn bằng các bản sao đang sẵn sàng trên kệ.');
            }
            $retire = $this->db->prepare('UPDATE book_copies SET status = "retired" WHERE id = :id AND status = "available"');
            foreach (array_slice($available, 0, -$difference) as $copy) {
                $retire->execute(['id' => (int) $copy['id']]);
            }
            return;
        }

        if ($difference === 0) {
            return;
        }

        $nextNumber = 0;
        foreach ($copies as $copy) {
            if (preg_match('/-(\d+)$/', (string) $copy['copy_code'], $matches)) {
                $nextNumber = max($nextNumber, (int) $matches[1]);
            }
        }
        $insert = $this->db->prepare(
            'INSERT INTO book_copies (book_id, copy_code, status) VALUES (:book_id, :copy_code, "available")'
        );
        for ($index = 1; $index <= $difference; $index++) {
            $insert->execute([
                'book_id' => $bookId,
                'copy_code' => sprintf('%s-%04d', $bookCode, $nextNumber + $index),
            ]);
        }
    }
}
