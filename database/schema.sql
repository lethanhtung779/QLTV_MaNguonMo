CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'librarian', 'reader') NOT NULL DEFAULT 'reader',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS books (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    book_code VARCHAR(40) NOT NULL,
    title VARCHAR(240) NOT NULL,
    author VARCHAR(190) NOT NULL,
    isbn VARCHAR(20) NULL,
    category_id BIGINT UNSIGNED NULL,
    publisher VARCHAR(190) NULL,
    publication_year SMALLINT UNSIGNED NULL,
    description TEXT NULL,
    total_copies INT UNSIGNED NOT NULL DEFAULT 1,
    cover_url VARCHAR(500) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_books_code (book_code),
    UNIQUE KEY uq_books_isbn (isbn),
    KEY idx_books_title_author (title, author),
    KEY idx_books_public_active (is_public, is_active),
    KEY idx_books_category_active (category_id, is_active),
    CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_books_copies CHECK (total_copies >= 0),
    CONSTRAINT chk_books_year CHECK (publication_year IS NULL OR publication_year BETWEEN 1 AND 2100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS readers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    card_number VARCHAR(40) NOT NULL,
    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(32) NULL,
    email VARCHAR(190) NULL,
    address VARCHAR(400) NULL,
    joined_on DATE NOT NULL DEFAULT (CURRENT_DATE),
    expires_on DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_readers_card (card_number),
    UNIQUE KEY uq_readers_user (user_id),
    KEY idx_readers_name (full_name),
    KEY idx_readers_email (email),
    CONSTRAINT fk_readers_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS loans (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    loan_code VARCHAR(40) NOT NULL,
    reader_id BIGINT UNSIGNED NOT NULL,
    book_id BIGINT UNSIGNED NOT NULL,
    issued_by BIGINT UNSIGNED NULL,
    borrowed_at DATE NOT NULL,
    due_at DATE NOT NULL,
    returned_at DATE NULL,
    daily_fine DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    note VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_loans_code (loan_code),
    KEY idx_loans_reader_active (reader_id, returned_at, due_at),
    KEY idx_loans_book_active (book_id, returned_at),
    KEY idx_loans_due_returned (due_at, returned_at),
    KEY idx_loans_borrowed (borrowed_at),
    CONSTRAINT fk_loans_reader FOREIGN KEY (reader_id) REFERENCES readers (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_loans_issuer FOREIGN KEY (issued_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_loans_dates CHECK (due_at >= borrowed_at),
    CONSTRAINT chk_loans_fine CHECK (daily_fine >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reader_id BIGINT UNSIGNED NOT NULL,
    book_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'ready', 'fulfilled', 'cancelled') NOT NULL DEFAULT 'pending',
    reserved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    handled_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_reservations_book_status (book_id, status),
    KEY idx_reservations_reader_status (reader_id, status),
    CONSTRAINT fk_reservations_reader FOREIGN KEY (reader_id) REFERENCES readers (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_book FOREIGN KEY (book_id) REFERENCES books (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_handler FOREIGN KEY (handled_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    subject_type VARCHAR(80) NULL,
    subject_id BIGINT UNSIGNED NULL,
    details VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_activity_created (created_at),
    KEY idx_activity_user (user_id),
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
