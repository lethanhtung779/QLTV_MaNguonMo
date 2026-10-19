<?php
declare(strict_types=1);

use Library\Core\Database;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\UserRepository;
use Library\Services\LoanService;
require_once __DIR__ . '/test_database_guard.php';
require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (($argv[1] ?? '') === '--worker') {
    [, , $readerId, $copyId, $actorId, $gateFile, $readyFile] = $argv;
    $db = Database::connection();
    $connectionId = (int) $db->query('SELECT CONNECTION_ID()')->fetchColumn();
    file_put_contents($readyFile, (string) $connectionId, LOCK_EX);

    $deadline = microtime(true) + 20;
    while (!is_file($gateFile) && microtime(true) < $deadline) {
        usleep(10000);
    }
    if (!is_file($gateFile)) {
        fwrite(STDERR, "Concurrency worker timed out waiting for the gate.\n");
        exit(2);
    }

    try {
        $code = (new LoanService($db, 5000))->checkout(
            (int) $readerId,
            [(int) $copyId],
            (int) $actorId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+1 day')),
            'Isolated MySQL concurrency test'
        );
        fwrite(STDOUT, json_encode([
            'outcome' => 'accepted',
            'connection_id' => $connectionId,
            'loan_code' => $code,
        ], JSON_THROW_ON_ERROR) . PHP_EOL);
        exit;
    } catch (PDOException $exception) {
        fwrite(STDERR, $exception::class . ': ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    } catch (RuntimeException $exception) {
        fwrite(STDOUT, json_encode([
            'outcome' => 'rejected',
            'connection_id' => $connectionId,
            'reason' => $exception->getMessage(),
        ], JSON_THROW_ON_ERROR) . PHP_EOL);
        exit;
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception::class . ': ' . $exception->getMessage() . PHP_EOL);
        exit(1);
    }
}

$db = Database::connection();
$actorId = (int) $db->query(
    'SELECT id FROM users WHERE role IN ("admin", "librarian") AND is_active = 1 ORDER BY id LIMIT 1'
)->fetchColumn();
if ($actorId < 1) {
    fwrite(STDERR, "MySQL concurrency test requires an active admin or librarian account.\n");
    exit(1);
}

$tag = strtoupper(bin2hex(random_bytes(5)));
$temporaryDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'paper-page-concurrency-' . strtolower($tag);
$gateFile = $temporaryDirectory . DIRECTORY_SEPARATOR . 'start';
$userIds = [];
$readerIds = [];
$bookId = 0;
$copyId = 0;
$workers = [];
$failure = 0;
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('MySQL concurrency test failed: ' . $message);
    }
};

try {
    if (!mkdir($temporaryDirectory, 0700) && !is_dir($temporaryDirectory)) {
        throw new RuntimeException('Unable to create isolated concurrency-test directory.');
    }

    $categoryId = (int) $db->query('SELECT id FROM categories ORDER BY id LIMIT 1')->fetchColumn();
    $users = new UserRepository($db);
    $readers = new ReaderRepository($db);
    for ($index = 1; $index <= 2; $index++) {
        $email = "qa-concurrency-{$tag}-{$index}@example.test";
        $userIds[] = $users->create([
            'name' => "QA Concurrency {$tag} {$index}",
            'email' => $email,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'role' => 'reader',
        ]);
        $readerIds[] = $readers->create([
            'user_id' => $userIds[array_key_last($userIds)],
            'card_number' => "QAC-{$tag}-{$index}",
            'full_name' => "QA Concurrency Reader {$index}",
            'phone' => null,
            'email' => $email,
            'address' => null,
            'expires_on' => date('Y-m-d', strtotime('+1 year')),
        ]);
    }

    $bookId = (new BookRepository($db))->create([
        'book_code' => 'QAC-' . $tag,
        'title' => 'QA concurrency fixture ' . $tag,
        'author' => 'Concurrency test',
        'isbn' => null,
        'category_id' => $categoryId ?: null,
        'publisher' => null,
        'publication_year' => (int) date('Y'),
        'description' => 'Isolated temporary record for tests/mysql_concurrency.php.',
        'total_copies' => 1,
        'cover_url' => null,
    ]);
    $copyId = (int) $db->query(
        'SELECT id FROM book_copies WHERE book_id = ' . $bookId . ' ORDER BY id LIMIT 1'
    )->fetchColumn();
    $assert($copyId > 0, 'The isolated book fixture has exactly one physical copy.');

    $db->beginTransaction();
    $lock = $db->prepare('SELECT id FROM book_copies WHERE id = :id FOR UPDATE');
    $lock->execute(['id' => $copyId]);
    $assert((int) $lock->fetchColumn() === $copyId, 'The coordinator holds the target copy row lock.');

    foreach ($readerIds as $index => $readerId) {
        $readyFile = $temporaryDirectory . DIRECTORY_SEPARATOR . 'ready-' . ($index + 1);
        $command = [
            PHP_BINARY,
            __FILE__,
            '--worker',
            (string) $readerId,
            (string) $copyId,
            (string) $actorId,
            $gateFile,
            $readyFile,
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), null, ['bypass_shell' => true]);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start an independent PHP concurrency worker.');
        }
        fclose($pipes[0]);
        $workers[] = ['process' => $process, 'pipes' => $pipes, 'ready' => $readyFile];
    }

    $readyDeadline = microtime(true) + 15;
    while (microtime(true) < $readyDeadline) {
        if (count(array_filter($workers, static fn(array $worker): bool => is_file($worker['ready']))) === 2) {
            break;
        }
        usleep(10000);
    }
    $connectionIds = [];
    foreach ($workers as $worker) {
        $assert(is_file($worker['ready']), 'Each independent worker connected to MySQL before the race started.');
        $connectionIds[] = (int) file_get_contents($worker['ready']);
    }
    $assert(count(array_unique($connectionIds)) === 2, 'Workers use two distinct MySQL CONNECTION_ID values.');
    touch($gateFile);

    $observedWaiters = 0;
    $waitMonitoring = 'performance_schema.data_lock_waits';
    $waitDeadline = microtime(true) + 12;
    try {
        $waitQuery = $db->prepare(
            'SELECT COUNT(DISTINCT t.PROCESSLIST_ID)
             FROM performance_schema.data_lock_waits w
             JOIN performance_schema.data_locks requested
               ON requested.ENGINE = "INNODB"
              AND requested.ENGINE_LOCK_ID = w.REQUESTING_ENGINE_LOCK_ID
             JOIN performance_schema.threads t ON t.THREAD_ID = w.REQUESTING_THREAD_ID
             WHERE requested.OBJECT_SCHEMA = DATABASE()
               AND requested.OBJECT_NAME IN ("book_copies","books")
               AND t.PROCESSLIST_ID IN (?, ?)'
        );
        while (microtime(true) < $waitDeadline) {
            $waitQuery->execute($connectionIds);
            $observedWaiters = (int) $waitQuery->fetchColumn();
            if ($observedWaiters === 2) {
                break;
            }
            usleep(20000);
        }
    } catch (PDOException) {
        $waitMonitoring = 'information_schema.PROCESSLIST fallback';
        $processQuery = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.PROCESSLIST
             WHERE ID IN (?, ?) AND STATE LIKE "%lock%"'
        );
        while (microtime(true) < $waitDeadline) {
            $processQuery->execute($connectionIds);
            $observedWaiters = (int) $processQuery->fetchColumn();
            if ($observedWaiters === 2) {
                break;
            }
            usleep(20000);
        }
    }
    $assert(
        $observedWaiters === 2,
        "Both independent sessions must be observed waiting on the locked copy or its parent book ({$waitMonitoring}); observed {$observedWaiters}."
    );

    $db->commit();
    $results = [];
    foreach ($workers as $worker) {
        $stdout = stream_get_contents($worker['pipes'][1]);
        $stderr = stream_get_contents($worker['pipes'][2]);
        fclose($worker['pipes'][1]);
        fclose($worker['pipes'][2]);
        $exitCode = proc_close($worker['process']);
        if ($exitCode !== 0) {
            throw new RuntimeException('Concurrency worker failed: ' . trim($stderr ?: $stdout));
        }
        $result = json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR);
        $assert(in_array($result['outcome'], ['accepted', 'rejected'], true), 'Worker returned an explicit business outcome.');
        $results[] = $result;
    }

    $accepted = array_values(array_filter($results, static fn(array $result): bool => $result['outcome'] === 'accepted'));
    $rejected = array_values(array_filter($results, static fn(array $result): bool => $result['outcome'] === 'rejected'));
    $assert(count($accepted) === 1 && count($rejected) === 1, 'Exactly one checkout succeeds and one is rejected.');
    $assert(
        str_contains($rejected[0]['reason'], 'đã được mượn')
            || str_contains($rejected[0]['reason'], 'không còn khả dụng'),
        'The losing checkout is rejected by the application after the copy row lock is released: '
            . $rejected[0]['reason']
    );
    $assert(
        count(array_unique(array_column($results, 'connection_id'))) === 2,
        'Both completed results came from distinct MySQL sessions.'
    );

    $copyStatus = (string) $db->query('SELECT status FROM book_copies WHERE id = ' . $copyId)->fetchColumn();
    $activeItemCount = (int) $db->query(
        'SELECT COUNT(*) FROM loan_items WHERE book_copy_id = ' . $copyId . ' AND returned_at IS NULL'
    )->fetchColumn();
    $loanCount = (int) $db->query(
        'SELECT COUNT(*) FROM loans WHERE reader_id IN (' . implode(',', array_map('intval', $readerIds)) . ')'
    )->fetchColumn();
    $assert($copyStatus === 'on_loan', 'The physical copy ends in the on_loan state.');
    $assert($activeItemCount === 1 && $loanCount === 1, 'MySQL contains one active loan item and one loan header only.');

    fwrite(STDOUT, "MySQL checkout concurrency: PASS; 2 independent sessions, 2 observed row-lock waiters, 1 accepted / 1 rejected, 1 active loan item; {$waitMonitoring}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class . ': ' . $exception->getMessage() . PHP_EOL);
    $failure = 1;
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($workers) {
        touch($gateFile);
        foreach ($workers as $worker) {
            if (is_resource($worker['process'])) {
                foreach ([1, 2] as $pipeIndex) {
                    if (isset($worker['pipes'][$pipeIndex]) && is_resource($worker['pipes'][$pipeIndex])) {
                        stream_set_blocking($worker['pipes'][$pipeIndex], false);
                        fclose($worker['pipes'][$pipeIndex]);
                    }
                }
                proc_terminate($worker['process']);
                proc_close($worker['process']);
            }
        }
    }
    try {
        if ($readerIds) {
            $readerList = implode(',', array_map('intval', $readerIds));
            $loanIds = $db->query(
                'SELECT id FROM loans WHERE reader_id IN (' . $readerList . ')'
            )->fetchAll(PDO::FETCH_COLUMN);
            if ($loanIds) {
                $loanList = implode(',', array_map('intval', $loanIds));
                $db->exec('DELETE FROM notifications WHERE entity_type = "loan" AND entity_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM activity_logs WHERE subject_type = "loan" AND subject_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM loan_renewals WHERE loan_item_id IN (SELECT id FROM loan_items WHERE loan_id IN (' . $loanList . '))');
                $db->exec('DELETE FROM loan_items WHERE loan_id IN (' . $loanList . ')');
                $db->exec('DELETE FROM loans WHERE id IN (' . $loanList . ')');
            }
            $db->exec('DELETE FROM reservations WHERE reader_id IN (' . $readerList . ')');
            $db->exec('DELETE FROM activity_logs WHERE user_id IN (' . implode(',', array_map('intval', $userIds)) . ')');
            $db->exec('DELETE FROM notifications WHERE user_id IN (' . implode(',', array_map('intval', $userIds)) . ')');
            $db->exec('DELETE FROM readers WHERE id IN (' . $readerList . ')');
        }
        if ($userIds) {
            $db->exec('DELETE FROM users WHERE id IN (' . implode(',', array_map('intval', $userIds)) . ')');
        }
        if ($bookId > 0) {
            $db->exec('DELETE FROM activity_logs WHERE subject_type = "book" AND subject_id = ' . $bookId);
            $db->exec('DELETE FROM book_copies WHERE book_id = ' . $bookId);
            $db->exec('DELETE FROM books WHERE id = ' . $bookId);
        }
        $db->exec(
            'DELETE FROM authors WHERE name = "Concurrency test"
             AND NOT EXISTS (SELECT 1 FROM book_authors WHERE book_authors.author_id = authors.id)'
        );
    } catch (Throwable $cleanupException) {
        fwrite(STDERR, 'QA data cleanup failed: ' . $cleanupException->getMessage() . PHP_EOL);
        $failure = 1;
    }

    foreach (glob($temporaryDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    if (is_dir($temporaryDirectory)) {
        rmdir($temporaryDirectory);
    }
}

exit($failure);
