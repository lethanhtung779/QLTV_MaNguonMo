<?php
declare(strict_types=1);

return static function (PDO $connection): void {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS authors (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_authors_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS publishers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_publishers_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS book_authors (
            book_id BIGINT UNSIGNED NOT NULL,
            author_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (book_id, author_id),
            KEY idx_book_authors_author (author_id, book_id),
            CONSTRAINT fk_book_authors_book FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT fk_book_authors_author FOREIGN KEY (author_id) REFERENCES authors (id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columnCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "books" AND COLUMN_NAME = "publisher_id"'
    );
    $columnCheck->execute();
    if (!$columnCheck->fetchColumn()) {
        $connection->exec('ALTER TABLE books ADD COLUMN publisher_id BIGINT UNSIGNED NULL AFTER publisher');
    }
    $indexCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "books" AND INDEX_NAME = "idx_books_publisher"'
    );
    $indexCheck->execute();
    if (!$indexCheck->fetchColumn()) {
        $connection->exec('ALTER TABLE books ADD KEY idx_books_publisher (publisher_id)');
    }
    $foreignKeyCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = "fk_books_publisher"'
    );
    $foreignKeyCheck->execute();
    if (!$foreignKeyCheck->fetchColumn()) {
        $connection->exec(
            'ALTER TABLE books ADD CONSTRAINT fk_books_publisher
             FOREIGN KEY (publisher_id) REFERENCES publishers (id) ON UPDATE CASCADE ON DELETE SET NULL'
        );
    }

    $books = $connection->query('SELECT id, author, publisher FROM books ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $authorUpsert = $connection->prepare(
        'INSERT INTO authors (name) VALUES (:name)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $authorLookup = $connection->prepare('SELECT id FROM authors WHERE name = :name');
    $authorLink = $connection->prepare('INSERT IGNORE INTO book_authors (book_id, author_id) VALUES (:book_id, :author_id)');
    $publisherUpsert = $connection->prepare(
        'INSERT INTO publishers (name) VALUES (:name)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    );
    $publisherLookup = $connection->prepare('SELECT id FROM publishers WHERE name = :name');
    $publisherLink = $connection->prepare('UPDATE books SET publisher_id = :publisher_id WHERE id = :book_id');

    foreach ($books as $book) {
        $authorNames = array_values(array_unique(array_filter(
            array_map('trim', explode(';', (string) $book['author'])),
            static fn(string $name): bool => $name !== ''
        )));
        foreach ($authorNames as $name) {
            $authorUpsert->execute(['name' => $name]);
            $authorId = (int) $connection->lastInsertId();
            if ($authorId < 1) {
                $authorLookup->execute(['name' => $name]);
                $authorId = (int) $authorLookup->fetchColumn();
            }
            if ($authorId > 0) {
                $authorLink->execute(['book_id' => (int) $book['id'], 'author_id' => $authorId]);
            }
        }

        $publisherName = trim((string) ($book['publisher'] ?? ''));
        if ($publisherName !== '') {
            $publisherUpsert->execute(['name' => $publisherName]);
            $publisherId = (int) $connection->lastInsertId();
            if ($publisherId < 1) {
                $publisherLookup->execute(['name' => $publisherName]);
                $publisherId = (int) $publisherLookup->fetchColumn();
            }
            $publisherLink->execute(['publisher_id' => $publisherId ?: null, 'book_id' => (int) $book['id']]);
        }
    }
};
