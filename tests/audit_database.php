<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap/app.php';
$db = \Library\Core\Database::connection();
$out = ['server' => $db->query('SELECT VERSION()')->fetchColumn(), 'database' => $db->query('SELECT DATABASE()')->fetchColumn(), 'migrations' => $db->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN)];
foreach (['books','book_copies','readers','users','loans','loan_items','reservations'] as $t) $out['counts'][$t] = $db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
$out['inventory_mismatch'] = $db->query('SELECT COUNT(*) FROM book_copies c WHERE (c.status = "on_loan") <> EXISTS(SELECT 1 FROM loan_items li WHERE li.book_copy_id=c.id AND li.returned_at IS NULL)')->fetchColumn();
$out['foreign_keys'] = $db->query('SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()')->fetchColumn();
$out['tables'] = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), PHP_EOL;
