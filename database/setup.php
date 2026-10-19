<?php
declare(strict_types=1);

use Library\Core\Config;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once dirname(__DIR__) . '/bootstrap/autoload.php';
Config::load(dirname(__DIR__) . '/.env');

$databaseName = (string) Config::get('DB_NAME', 'paper_page_library');
if (!preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
    fwrite(STDERR, "Tên database trong .env không hợp lệ.\n");
    exit(1);
}

$host = (string) Config::get('DB_HOST', '127.0.0.1');
$port = (string) Config::get('DB_PORT', '3306');
$user = (string) Config::get('DB_USER', 'root');
$password = (string) Config::get('DB_PASSWORD', '');
$charset = (string) Config::get('DB_CHARSET', 'utf8mb4');

try {
    $server = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=%s', $host, $port, $charset),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $connection = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $databaseName, $charset),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );

    $connection->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version VARCHAR(100) NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $migrationCheck = $connection->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
    $migrationRecord = $connection->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $migrations = [
        '001_initial_schema' => static function (PDO $connection): void {
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('Không đọc được database/schema.sql.');
            }
            foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
                $connection->exec($statement);
            }
        },
        '002_copy_level_inventory' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/002_copy_level_inventory.php';
            $migration($connection);
        },
        '003_reader_policies_reservations_notifications' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/003_reader_policies_reservations_notifications.php';
            $migration($connection);
        },
        '004_versioned_library_policies' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/004_versioned_library_policies.php';
            $migration($connection);
        },
        '005_fines_and_payments' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/005_fines_and_payments.php';
            $migration($connection);
        },
        '006_normalized_catalog_metadata' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/006_normalized_catalog_metadata.php';
            $migration($connection);
        },
        '007_action_permissions' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/007_action_permissions.php';
            $migration($connection);
        },
        '008_return_receipt_snapshot' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/008_return_receipt_snapshot.php';
            $migration($connection);
        },
        '009_mutation_requests' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/009_mutation_requests.php';
            $migration($connection);
        },
        '010_zero_operational_inventory' => static function (PDO $connection): void {
            $migration = require __DIR__ . '/migrations/010_zero_operational_inventory.php';
            $migration($connection);
        },
    ];
    foreach ($migrations as $version => $applyMigration) {
        $migrationCheck->execute([$version]);
        if ($migrationCheck->fetchColumn()) {
            continue;
        }

        $applyMigration($connection);
        $migrationRecord->execute([$version]);
    }

    $categories = ['Văn học', 'Khoa học', 'Lịch sử', 'Tin học', 'Kinh tế', 'Tâm lý - Kỹ năng sống', 'Thiếu nhi'];
    $categoryInsert = $connection->prepare('INSERT IGNORE INTO categories (name) VALUES (?)');
    foreach ($categories as $category) {
        $categoryInsert->execute([$category]);
    }

    $books = [
        ['B001', 'Giết con chim nhại', 'Harper Lee', '9780060935467', 'Văn học', 2015, 'Nhà xuất bản Văn học', 8, 'Một câu chuyện sâu sắc về công lý, lòng trắc ẩn và tuổi thơ.'],
        ['B002', 'Sapiens: Lược sử loài người', 'Yuval Noah Harari', '9780062316097', 'Lịch sử', 2017, 'Harper', 5, 'Hành trình nhìn lại lịch sử và những bước ngoặt của nhân loại.'],
        ['B003', 'Trí tuệ xúc cảm', 'Daniel Goleman', '9780553383713', 'Tâm lý - Kỹ năng sống', 2018, 'Bantam', 6, 'Khám phá vai trò của trí tuệ cảm xúc trong đời sống.'],
        ['B004', 'Nhà giả kim', 'Paulo Coelho', '9780061122415', 'Văn học', 2016, 'HarperOne', 10, 'Cuốn tiểu thuyết về hành trình theo đuổi ước mơ và khám phá bản thân.'],
        ['B005', 'Harry Potter và Hòn đá phù thủy', 'J.K. Rowling', '9780590353427', 'Thiếu nhi', 2014, 'Bloomsbury', 12, 'Cuộc phiêu lưu đầu tiên của cậu bé phù thủy tại Hogwarts.'],
        ['B006', 'Vật lý lý thú', 'Y. Perelman', null, 'Khoa học', 2019, 'Nhà xuất bản Giáo dục', 4, 'Những câu hỏi vật lý được giải thích bằng các ví dụ gần gũi.'],
        ['B007', 'Nền kinh tế tăng trưởng và thịnh vượng', 'William Bernstein', null, 'Kinh tế', 2020, 'Nhà xuất bản Trẻ', 5, 'Những nền tảng của tăng trưởng kinh tế và thịnh vượng.'],
        ['B008', 'HTML & CSS – Thiết kế Web', 'Jon Duckett', '9781118008188', 'Tin học', 2021, 'Wiley', 7, 'Cẩm nang trực quan về xây dựng giao diện web.'],
        ['B009', 'Kiến thức lập trình cơ bản', 'Nguyễn Văn A', null, 'Tin học', 2022, 'Nhà xuất bản Khoa học', 6, 'Tài liệu nhập môn tư duy và kỹ thuật lập trình.'],
    ];
    $categoryLookup = $connection->query('SELECT name, id FROM categories')->fetchAll(PDO::FETCH_KEY_PAIR);
    $bookInsert = $connection->prepare(
        'INSERT IGNORE INTO books (book_code, title, author, isbn, category_id, publisher, publication_year, total_copies, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($books as [$code, $title, $author, $isbn, $category, $year, $publisher, $copies, $description]) {
        $connection->beginTransaction();
        try {
            $bookInsert->execute([$code, $title, $author, $isbn, $categoryLookup[$category] ?? null, $publisher, $year, $copies, $description]);
            if ($bookInsert->rowCount() > 0) {
                $bookId = (int) $connection->lastInsertId();
                (new \Library\Repositories\BookRepository($connection))->update($bookId, [
                    'book_code'=>$code, 'title'=>$title, 'author'=>$author, 'isbn'=>$isbn,
                    'category_id'=>$categoryLookup[$category] ?? null, 'publisher'=>$publisher,
                    'publication_year'=>$year, 'total_copies'=>$copies, 'description'=>$description, 'cover_url'=>null,
                ]);
            }
            $connection->commit();
        } catch (Throwable $exception) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $exception;
        }
    }

    fwrite(STDOUT, "Đã tạo schema MySQL và nạp danh mục/sách mẫu.\n");
    fwrite(STDOUT, "Thiết lập tài khoản quản trị bằng lệnh: php database/seed.php\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Không thể khởi tạo database: {$exception->getMessage()}\n");
    exit(1);
}
