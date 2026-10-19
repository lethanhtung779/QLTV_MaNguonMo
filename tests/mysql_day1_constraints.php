<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
require dirname(__DIR__).'/bootstrap/app.php';
$db=\Library\Core\Database::connection();$checks=0;
$expect=function(string $sql,int $code,string $label)use($db,&$checks){
 $db->exec('SAVEPOINT bad_data');
 try{$db->exec($sql);throw new RuntimeException('FAIL: '.$label.' accepted invalid data');}
 catch(PDOException $e){if((int)($e->errorInfo[1]??0)!==$code)throw $e;$checks++;echo 'PASS: '.$label.' (MySQL '.$code.')'.PHP_EOL;}
 finally{$db->exec('ROLLBACK TO SAVEPOINT bad_data');}
};
$tag='CONSTRAINT-'.bin2hex(random_bytes(4));$db->beginTransaction();
try{
 $expect('INSERT INTO book_copies(book_id,copy_code) SELECT book_id,copy_code FROM book_copies LIMIT 1',1062,'Unique physical copy code');
 $expect('INSERT INTO book_copies(book_id,copy_code) VALUES(18446744073709551615,'.$db->quote($tag).')',1452,'Physical copy FK rejects orphan book');
 $expect('INSERT INTO loan_items(loan_id,book_copy_id,borrowed_at,due_date,daily_fine) SELECT loan_id,book_copy_id,borrowed_at,due_date,daily_fine FROM loan_items WHERE returned_at IS NULL LIMIT 1',1062,'Generated active_copy_id prevents two open loans');
 $expect('UPDATE loan_items SET due_date=DATE_SUB(borrowed_at,INTERVAL 1 DAY) LIMIT 1',3819,'Loan date CHECK');
 $expect('UPDATE loan_items SET daily_fine=-1 LIMIT 1',3819,'Nonnegative fine CHECK');
 $expect('INSERT INTO fine_payments(reader_id,amount,method) SELECT id,0,"cash" FROM readers LIMIT 1',3819,'Positive payment CHECK');
 $expect('INSERT INTO fine_charges(loan_item_id,reader_id,charge_type,amount,description,deduplication_key) SELECT loan_item_id,reader_id,charge_type,amount,description,deduplication_key FROM fine_charges LIMIT 1',1062,'Charge deduplication unique key');
 $expect('INSERT INTO fine_payment_allocations(payment_id,fine_charge_id,amount) SELECT payment_id,fine_charge_id,amount FROM fine_payment_allocations LIMIT 1',1062,'Payment allocation composite primary key');
 $expect('UPDATE book_copies SET status="invalid" LIMIT 1',1265,'Strict copy status ENUM');
 $book=(int)$db->query('SELECT id FROM books ORDER BY id LIMIT 1')->fetchColumn();
 $q=$db->prepare('INSERT INTO book_copies(book_id,copy_code,barcode) VALUES(?,?,NULL)');
 $q->execute([$book,$tag.'A']);$q->execute([$book,$tag.'B']);
 $checks++;echo 'PASS: nullable unique barcode permits two unassigned barcodes.',PHP_EOL;
}finally{$db->rollBack();}
echo "Day1 schema constraints: {$checks} checks passed; invalid fixtures rolled back.",PHP_EOL;
