<?php
declare(strict_types=1);

return static function (PDO $connection): void {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS book_copies (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            book_id BIGINT UNSIGNED NOT NULL,
            copy_code VARCHAR(60) NOT NULL,
            barcode VARCHAR(80) NULL,
            item_condition ENUM("good", "worn", "damaged", "lost") NOT NULL DEFAULT "good",
            status ENUM("available", "on_loan", "reserved", "damaged", "lost", "retired") NOT NULL DEFAULT "available",
            shelf_location VARCHAR(120) NULL,
            acquired_at DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_book_copies_code (copy_code),
            UNIQUE KEY uq_book_copies_barcode (barcode),
            KEY idx_book_copies_book_status (book_id, status),
            CONSTRAINT fk_book_copies_book FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS loan_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            loan_id BIGINT UNSIGNED NOT NULL,
            book_copy_id BIGINT UNSIGNED NOT NULL,
            borrowed_at DATE NOT NULL,
            due_date DATE NOT NULL,
            returned_at DATE NULL,
            daily_fine DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
            renewal_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            active_copy_id BIGINT UNSIGNED GENERATED ALWAYS AS (
                CASE WHEN returned_at IS NULL THEN book_copy_id ELSE NULL END
            ) STORED,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_loan_items_active_copy (active_copy_id),
            KEY idx_loan_items_loan_returned (loan_id, returned_at),
            KEY idx_loan_items_copy_history (book_copy_id, borrowed_at, returned_at),
            KEY idx_loan_items_due_returned (due_date, returned_at),
            CONSTRAINT chk_loan_items_dates CHECK (due_date >= borrowed_at),
            CONSTRAINT chk_loan_items_fine CHECK (daily_fine >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    foreach ([
        'fk_loan_items_loan' => 'FOREIGN KEY (loan_id) REFERENCES loans (id) ON UPDATE CASCADE ON DELETE RESTRICT',
        'fk_loan_items_copy' => 'FOREIGN KEY (book_copy_id) REFERENCES book_copies (id) ON UPDATE RESTRICT ON DELETE RESTRICT',
    ] as $name => $definition) {
        $check = $connection->prepare(
            'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = :name'
        );
        $check->execute(['name' => $name]);
        if (!$check->fetchColumn()) {
            try {
                $connection->exec("ALTER TABLE loan_items ADD CONSTRAINT {$name} {$definition}");
            } catch (Throwable $exception) {
                throw new RuntimeException("Không thể thêm ràng buộc {$name}: {$exception->getMessage()}", 0, $exception);
            }
        }
    }

    $connection->beginTransaction();
    try {
        $copies = $connection->prepare(
            'INSERT IGNORE INTO book_copies (book_id, copy_code, status)
             VALUES (:book_id, :copy_code, "available")'
        );
        $books = $connection->query('SELECT id, book_code, total_copies FROM books ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($books as $book) {
            for ($copyNumber = 1; $copyNumber <= (int) $book['total_copies']; $copyNumber++) {
                $copies->execute([
                    'book_id' => (int) $book['id'],
                    'copy_code' => sprintf('%s-%04d', $book['book_code'], $copyNumber),
                ]);
            }
        }

        $loans = $connection->query(
            'SELECT id, book_id, borrowed_at, due_at, returned_at, daily_fine
             FROM loans ORDER BY book_id, borrowed_at, id'
        )->fetchAll(PDO::FETCH_ASSOC);
        $byBook = [];
        foreach ($loans as $loan) {
            $byBook[(int) $loan['book_id']][] = $loan;
        }

        $copyIds = $connection->prepare(
            'SELECT id FROM book_copies WHERE book_id = :book_id ORDER BY id'
        );
        $copyUpdate = $connection->prepare(
            'UPDATE book_copies SET status = :status WHERE id = :id'
        );
        $itemInsert = $connection->prepare(
            'INSERT INTO loan_items (loan_id, book_copy_id, borrowed_at, due_date, returned_at, daily_fine)
             VALUES (:loan_id, :book_copy_id, :borrowed_at, :due_date, :returned_at, :daily_fine)'
        );

        foreach ($byBook as $bookId => $bookLoans) {
            $copyIds->execute(['book_id' => $bookId]);
            $ids = array_map('intval', $copyIds->fetchAll(PDO::FETCH_COLUMN));
            $freeOn = array_fill_keys($ids, '0001-01-01');
            foreach ($bookLoans as $loan) {
                $borrowedAt = (string) $loan['borrowed_at'];
                $copyId = null;
                foreach ($ids as $candidate) {
                    if ($freeOn[$candidate] < $borrowedAt) {
                        $copyId = $candidate;
                        break;
                    }
                }
                if ($copyId === null) {
                    throw new RuntimeException(
                        "Không thể ánh xạ lịch sử mượn #{$loan['id']} sang bản sao: số lượt mượn chồng lấn vượt tồn kho sách #{$bookId}. Dữ liệu cũ được giữ nguyên."
                    );
                }

                $returnedAt = $loan['returned_at'] === null ? null : (string) $loan['returned_at'];
                $freeOn[$copyId] = $returnedAt ?? '9999-12-31';
                $itemInsert->execute([
                    'loan_id' => (int) $loan['id'],
                    'book_copy_id' => $copyId,
                    'borrowed_at' => $borrowedAt,
                    'due_date' => (string) $loan['due_at'],
                    'returned_at' => $returnedAt,
                    'daily_fine' => (string) $loan['daily_fine'],
                ]);
                if ($returnedAt === null) {
                    $copyUpdate->execute(['status' => 'on_loan', 'id' => $copyId]);
                }
            }
        }
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
};
