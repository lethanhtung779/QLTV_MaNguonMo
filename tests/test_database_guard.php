<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name=getenv('DB_NAME');
if (!is_string($name) || !preg_match('/^paper_page_test_[A-Za-z0-9_]+$/',$name)) {
    fwrite(STDERR,"BLOCKED: tests that write data require an isolated DB_NAME=paper_page_test_...; run tests/prepare_test_database.php first.\n");
    exit(2);
}
