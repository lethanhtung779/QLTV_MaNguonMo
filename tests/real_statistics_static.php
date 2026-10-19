<?php
declare(strict_types=1);

/** Narrow static regression checks; not a substitute for MySQL integration tests. */
$root = dirname(__DIR__);
$paths = [
    'repository' => $root . '/app/Repositories/RealStatisticsRepository.php',
    'controller' => $root . '/app/Controllers/DashboardController.php',
    'loans' => $root . '/app/Repositories/LoanRepository.php',
    'reports' => $root . '/app/Repositories/ReportRepository.php',
    'view' => $root . '/app/Views/admin/statistics.php',
];
foreach ($paths as $key => $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "FAIL missing: {$key}\n");
        exit(1);
    }
    $content[$key] = file_get_contents($path);
}
$checks = [
    'seed-book filter' => str_contains($content['repository'], "b.book_code NOT LIKE 'DEMO-%'"),
    'seed-reader filter' => str_contains($content['repository'], "r.card_number NOT LIKE 'DEMO-%'"),
    'seed-loan note filter' => str_contains($content['repository'], "DEMO_FIXTURE:%"),
    'statistics controller uses real book metric' => str_contains($content['controller'], '$real->bookMetrics()'),
    'statistics controller uses real loan metric' => str_contains($content['controller'], '$real->loanMetrics()'),
    'statistics controller uses real monthly' => str_contains($content['controller'], '$real->filteredMonthly('),
    'statistics controller uses real fee collections' => str_contains($content['controller'], '$real->collected('),
    'statistics controller uses real fee outstanding' => str_contains($content['controller'], '$real->outstanding()'),
    'loan report supports optional realOnly' => str_contains($content['loans'], 'bool $realOnly = false'),
    'inventory and fine report supports optional realOnly' => str_contains($content['reports'], 'bool $realOnly=false'),
    'loan report CSV realOnly' => str_contains($content['controller'], '$status, 10001, 0, false, true'),
    'exports real monthly' => str_contains($content['controller'], '->monthlySummary('),
    'view labels operational data' => str_contains($content['view'], 'Không tính sách DEMO-B'),
    'empty result note' => str_contains($content['view'], 'Chưa phát sinh lượt mượn thực tế'),
];
$fail = 0;
foreach ($checks as $label => $ok) {
    echo ($ok ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;
    $fail += (int)!$ok;
}
echo 'Static checks ' . (count($checks)-$fail) . '/' . count($checks) . PHP_EOL;
exit($fail ? 1 : 0);
