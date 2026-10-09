<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
try {
    $db=\Library\Core\Database::connection();
    $expired=(new \Library\Services\LoanService($db,5000))->expireReservations();
    (new \Library\Services\FineService($db))->synchronizeAll();
    $reminders=(new \Library\Services\ReminderService($db))->synchronize();
    echo "PASS: maintenance, expired reservations=$expired, eligible reminders=$reminders.\n";
} catch(Throwable $e){fwrite(STDERR,"FAIL: maintenance could not complete; check database configuration.\n");exit(1);}
