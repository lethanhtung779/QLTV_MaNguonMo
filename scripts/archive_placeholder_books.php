<?php
declare(strict_types=1);

/**
 * Paper Haven — Archive only B009 and DEMO-B001..DEMO-B021 without deleting records.
 * Run from the Paper Haven project root:
 *   php scripts/archive_placeholder_books.php --preview
 *   php scripts/archive_placeholder_books.php --apply
 *   php scripts/archive_placeholder_books.php --verify
 *
 * Applies is_active=0 and is_public=0 only. Does not alter related records.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$mode = $argv[1] ?? '--preview';
if (!in_array($mode, ['--preview', '--apply', '--verify'], true) || $argc > 2) {
    fwrite(STDERR, "Usage: php scripts/archive_placeholder_books.php [--preview|--apply|--verify]\n");
    exit(2);
}

$root = dirname(__DIR__);
$bootstrap = $root . '/bootstrap/app.php';
if (!is_file($bootstrap)) {
    fwrite(STDERR, "ERROR: Run the script from inside the Paper Haven project structure.\n");
    exit(2);
}
require_once $bootstrap;

$codes = ['B009'];
for ($i = 1; $i <= 21; $i++) {
    $codes[] = sprintf('DEMO-B%03d', $i);
}
if (count($codes) !== 22 || count(array_unique($codes)) !== 22) {
    throw new RuntimeException('Internal safety check failed.');
}

function affectedBooks(PDO $pdo, array $codes, bool $lock = false): array
{
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $sql = "SELECT id, book_code, title, is_active, is_public FROM books WHERE book_code IN ($placeholders) ORDER BY book_code";
    if ($lock) $sql .= ' FOR UPDATE';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($codes);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $pdo = \Library\Core\Database::connection();
    $rows = affectedBooks($pdo, $codes);
    $found = array_column($rows, 'book_code');
    $missing = array_values(array_diff($codes, $found));
    $visible = array_values(array_filter($rows, static fn(array $r): bool =>
        (int)$r['is_active'] !== 0 || (int)$r['is_public'] !== 0
    ));

    echo "Paper Haven — archive 22 explicitly listed placeholder/demo books\n";
    echo "Found: ", count($rows), "/22 | Still visible/active: ", count($visible), "\n";
    foreach ($rows as $r) {
        printf("%4s | %-10s | active=%d public=%d | %s\n",
            $r['id'], $r['book_code'], $r['is_active'], $r['is_public'], $r['title']);
    }
    if ($missing) echo "Not found (will not be created): ", implode(', ', $missing), "\n";

    if ($mode === '--preview') {
        echo "PREVIEW ONLY — database unchanged. Run with --apply to archive.\n";
        exit(0);
    }
    if ($mode === '--verify') {
        if ($visible !== []) {
            fwrite(STDERR, "VERIFY FAILED: Some listed records are still active/public.\n");
            exit(1);
        }
        echo "VERIFY PASS: All found listed records are inactive and not public.\n";
        exit(0);
    }

    $backupDir = $root . '/storage/backups';
    if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Cannot create a local backup folder. No changes made.');
    }

    $pdo->beginTransaction();
    try {
        $before = affectedBooks($pdo, $codes, true);
        $backupName = 'archive_placeholder_books_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.json';
        $backupPath = $backupDir . '/' . $backupName;
        $backup = [
            'action' => 'archive_placeholder_books',
            'created_at' => date(DATE_ATOM),
            'books' => $before,
        ];
        $encoded = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($backupPath, $encoded, LOCK_EX) === false) {
            throw new RuntimeException('Cannot save backup. No changes made.');
        }
        @chmod($backupPath, 0600);

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $stmt = $pdo->prepare("UPDATE books SET is_active=0, is_public=0 WHERE book_code IN ($placeholders) AND (is_active<>0 OR is_public<>0)");
        $stmt->execute($codes);
        $changed = $stmt->rowCount();

        $after = affectedBooks($pdo, $codes);
        $notArchived = array_filter($after, static fn(array $r): bool =>
            (int)$r['is_active'] !== 0 || (int)$r['is_public'] !== 0
        );
        if ($notArchived) throw new RuntimeException('Post-update verification failed; rolling back.');

        $pdo->commit();
        echo "APPLIED: $changed rows changed; all listed records are archived.\n";
        echo "Local status backup: $backupPath\n";
        echo "Related loans, reservations, favorites, copies and history were NOT deleted.\n";
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
