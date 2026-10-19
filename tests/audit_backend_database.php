<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/bootstrap/app.php';
$db=Library\Core\Database::connection();$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();$id=fn($s)=>chr(96).$s.chr(96);
$tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);$q=fn($s)=>$db->query($s)->fetchAll();$results=['time'=>date(DATE_ATOM),'database'=>$name,'version'=>$db->query('SELECT VERSION()')->fetchColumn(),'isolation'=>$db->query('SELECT @@transaction_isolation')->fetchColumn(),'tables'=>[]];
$db->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
try{
foreach($tables as $t){
 $indexes=$q('SHOW INDEX FROM '.$id($t));$columns=$q('SHOW COLUMNS FROM '.$id($t));$unique=[];foreach($indexes as $idx)if(!(int)$idx['Non_unique'])$unique[$idx['Key_name']][]=$idx['Column_name'];
 $duplicates=[];foreach($unique as $key=>$cols){$cols=array_map($id,$cols);$notNull=implode(' AND ',array_map(fn($c)=>$c.' IS NOT NULL',$cols));$sql='SELECT COUNT(*) FROM (SELECT '.implode(',',$cols).' FROM '.$id($t).' WHERE '.$notNull.' GROUP BY '.implode(',',$cols).' HAVING COUNT(*)>1) d';$duplicates[$key]=(int)$db->query($sql)->fetchColumn();}
 $results['tables'][$t]=['rows'=>(int)$db->query('SELECT COUNT(*) FROM '.$id($t))->fetchColumn(),'has_pk'=>isset($unique['PRIMARY']),'indexes'=>$indexes,'columns'=>$columns,'unique_duplicates'=>$duplicates];
}
$s=$db->prepare('SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=? AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME,CONSTRAINT_NAME,ORDINAL_POSITION');$s->execute([$name]);$fks=[];foreach($s->fetchAll() as $fk)$fks[$fk['TABLE_NAME'].'.'.$fk['CONSTRAINT_NAME']][]=$fk;
$results['foreign_keys']=[];foreach($fks as $key=>$parts){$p=$parts[0];$join=implode(' AND ',array_map(fn($r)=>'c.'.$id($r['COLUMN_NAME']).'=p.'.$id($r['REFERENCED_COLUMN_NAME']),$parts));$notNull=implode(' AND ',array_map(fn($r)=>'c.'.$id($r['COLUMN_NAME']).' IS NOT NULL',$parts));$sql='SELECT COUNT(*) FROM '.$id($p['TABLE_NAME']).' c LEFT JOIN '.$id($p['REFERENCED_TABLE_SCHEMA']).'.'.$id($p['REFERENCED_TABLE_NAME']).' p ON '.$join.' WHERE '.$notNull.' AND p.'.$id($p['REFERENCED_COLUMN_NAME']).' IS NULL';$results['foreign_keys'][$key]=(int)$db->query($sql)->fetchColumn();}
$results['foreign_key_index_support']=[];
foreach($fks as $key=>$parts){
 $columns=array_column($parts,'COLUMN_NAME');$indexes=[];
 foreach($results['tables'][$parts[0]['TABLE_NAME']]['indexes'] as $index)$indexes[$index['Key_name']][]=$index['Column_name'];
 $supported=false;foreach($indexes as $indexColumns)if(array_slice($indexColumns,0,count($columns))===$columns)$supported=true;
 $results['foreign_key_index_support'][$key]=$supported;
}
$results['check_constraints']=$q('SELECT tc.TABLE_NAME,cc.CONSTRAINT_NAME,cc.CHECK_CLAUSE,tc.ENFORCED
 FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc
 ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME
 WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.CONSTRAINT_TYPE="CHECK" ORDER BY tc.TABLE_NAME,cc.CONSTRAINT_NAME');
$results['copy_states']=$q('SELECT status,item_condition,COUNT(*) copies FROM book_copies GROUP BY status,item_condition ORDER BY status,item_condition');
$invariants=[
 'unusable_condition_mismatch'=>'SELECT COUNT(*) FROM book_copies WHERE status IN ("damaged","lost") AND status<>item_condition',
 'copy_vs_open_item'=>'SELECT COUNT(*) FROM book_copies c WHERE (c.status="on_loan") <> EXISTS(SELECT 1 FROM loan_items i WHERE i.book_copy_id=c.id AND i.returned_at IS NULL)',
 'reserved_copy_without_ready'=>'SELECT COUNT(*) FROM book_copies c WHERE c.status="reserved" AND NOT EXISTS(SELECT 1 FROM reservations r WHERE r.book_copy_id=c.id AND r.status="ready")',
 'ready_vs_copy_book'=>'SELECT COUNT(*) FROM reservations r LEFT JOIN book_copies c ON c.id=r.book_copy_id WHERE r.status="ready" AND (c.id IS NULL OR c.status<>"reserved" OR c.book_id<>r.book_id)',
 'duplicate_ready_copy'=>'SELECT COUNT(*) FROM (SELECT book_copy_id FROM reservations WHERE status="ready" AND book_copy_id IS NOT NULL GROUP BY book_copy_id HAVING COUNT(*)>1) d',
 'duplicate_reader_queue'=>'SELECT COUNT(*) FROM (SELECT reader_id,book_id FROM reservations WHERE status IN ("ready","pending") GROUP BY reader_id,book_id HAVING COUNT(*)>1) d',
 'available_bad_condition'=>'SELECT COUNT(*) FROM book_copies WHERE status="available" AND item_condition NOT IN ("good","worn")',
 'header_open_state'=>'SELECT COUNT(*) FROM loans l WHERE (l.returned_at IS NULL) <> EXISTS(SELECT 1 FROM loan_items i WHERE i.loan_id=l.id AND i.returned_at IS NULL)',
 'payment_allocation_sum'=>'SELECT COUNT(*) FROM fine_payments p WHERE p.amount <> COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.payment_id=p.id),0)',
 'overpaid_charge'=>'SELECT COUNT(*) FROM fine_charges c WHERE c.amount < COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.fine_charge_id=c.id),0)+COALESCE((SELECT SUM(w.amount) FROM fine_waivers w WHERE w.fine_charge_id=c.id),0)',
 'book_cached_inventory'=>'SELECT COUNT(*) FROM books b WHERE b.total_copies <> (SELECT COUNT(*) FROM book_copies c WHERE c.book_id=b.id AND c.status<>"retired")'
];foreach($invariants as $k=>$sql)$results['invariants'][$k]=(int)$db->query($sql)->fetchColumn();
$results['expiry_backlog']=(int)$db->query('SELECT COUNT(*) FROM reservations WHERE status="ready" AND expires_at<NOW()')->fetchColumn();
$results['migrations']=$q('SELECT * FROM schema_migrations ORDER BY version');
$plans=['copy_status'=>'SELECT id FROM book_copies WHERE book_id=1 AND status="available" ORDER BY id LIMIT 1','reader_active_items'=>'SELECT i.id FROM loans l JOIN loan_items i ON i.loan_id=l.id WHERE l.reader_id=1 AND i.returned_at IS NULL','catalog_substring'=>'SELECT id FROM books WHERE is_active=1 AND title LIKE "%sách%" ORDER BY updated_at DESC LIMIT 12','monthly'=>'SELECT DATE_FORMAT(borrowed_at,"%Y-%m"),COUNT(*) FROM loan_items WHERE borrowed_at>="2026-01-01" GROUP BY DATE_FORMAT(borrowed_at,"%Y-%m")'];
foreach($plans as $k=>$sql)$results['query_plans'][$k]=$q('EXPLAIN '.$sql);
$db->commit();
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
$dir=getenv('QA_OUTPUT') ?: dirname(__DIR__).'/storage/backups/day1-20261011';if(!is_dir($dir))mkdir($dir,0700,true);
$file=$dir.'/schema-'.$name.'.json';file_put_contents($file,json_encode($results,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
$bad=array_sum($results['invariants'])+array_sum($results['foreign_keys'])+count(array_filter($results['foreign_key_index_support'],fn($v)=>!$v));
foreach($results['check_constraints'] as $constraint)if($constraint['ENFORCED']!=='YES')$bad++;foreach($results['tables'] as $t=>$r){if(!$r['has_pk'])$bad++;$bad+=array_sum($r['unique_duplicates']);}
echo json_encode(['database'=>$name,'tables'=>count($tables),'foreign_keys'=>count($fks),'books'=>$results['tables']['books']['rows'],'copies'=>$results['tables']['book_copies']['rows'],'invariants'=>$results['invariants'],'expiry_backlog'=>$results['expiry_backlog'],'violations'=>$bad,'evidence'=>$file],JSON_PRETTY_PRINT),PHP_EOL;if($bad)exit(1);