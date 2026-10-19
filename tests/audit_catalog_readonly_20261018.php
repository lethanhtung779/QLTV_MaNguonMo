<?php
declare(strict_types=1);
/**
 * Catalog truth report: strictly READ-ONLY on MySQL. PHP CLI only.
 * Run: php tests/audit_catalog_readonly_20261018.php
 * No guesses of ISBN or mismatching covers; no external downloads.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap/app.php';
use Library\Core\Database;

function ph_isbn_status(?string $raw): string {
    $raw = trim((string) $raw);
    if ($raw === '') return 'MISSING_UNVERIFIED';
    $n = strtoupper(preg_replace('/[^0-9Xx]/', '', $raw));
    if (strlen($n) === 13 && ctype_digit($n) && preg_match('/^97[89]/', $n)) {
        $sum=0; for ($i=0;$i<12;$i++) $sum += ((int)$n[$i])*($i%2 ? 3 : 1);
        return ((10-$sum%10)%10)===(int)$n[12] ? 'CHECKSUM_VALID_EDITION_NOT_PROVEN' : 'INVALID_CHECK_DIGIT';
    }
    if (strlen($n) === 10 && preg_match('/^[0-9]{9}[0-9X]$/', $n)) {
        $sum=0; for ($i=0;$i<10;$i++) $sum+=(10-$i)*($n[$i]==='X'?10:(int)$n[$i]);
        return $sum%11===0 ? 'CHECKSUM_VALID_EDITION_NOT_PROVEN' : 'INVALID_CHECK_DIGIT';
    }
    return 'INVALID_FORMAT_OR_NOT_ISBN';
}
function ph_csv_safe(mixed $value): string {
    $s=(string)($value??'');
    return preg_match('/^[\s]*[=+@\-\t\r]/u', $s) ? "'".$s : $s;
}
$root=dirname(__DIR__);
$db=Database::connection();
$all=$db->query('SELECT id,book_code,title,author,publisher,publication_year,isbn,cover_url,is_active,is_public FROM books ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$rows=[];
$stats=['total'=>0,'public'=>0,'public_without_verified_cover'=>0,'public_without_isbn'=>0,'public_with_invalid_isbn'=>0,'public_with_cover'=>0];
foreach ($all as $b) {
    $public=(int)$b['is_public']===1 && (int)$b['is_active']===1;
    $isbn=ph_isbn_status($b['isbn']);
    $url=book_cover($b, 'M');
    $coverStatus=$url===''?'NO_CONFIRMED_COVER':(preg_match('~^https?://~', $url)?'VERIFIED_EXTERNAL_MANIFEST':'LOCAL_FILE_EXISTS');
    $stats['total']++;
    if ($public) {
        $stats['public']++;
        if ($coverStatus==='NO_CONFIRMED_COVER') $stats['public_without_verified_cover']++; else $stats['public_with_cover']++;
        if ($isbn==='MISSING_UNVERIFIED') $stats['public_without_isbn']++;
        elseif (str_starts_with($isbn,'INVALID')) $stats['public_with_invalid_isbn']++;
    }
    $rows[]=[(string)$b['id'], $b['book_code'], $b['title'], $b['author'], $b['publisher'],
        $b['publication_year'], $b['isbn'], $isbn, $b['cover_url'], $coverStatus,
        (string)$b['is_active'],(string)$b['is_public']];
}
$dir=$root.'/storage/reports';
if (!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Cannot create reports directory');
$filename=$dir.'/catalog_missing_covers_isbn_'.date('Ymd_His').'.csv';
$fh=fopen($filename,'wb');
if ($fh===false) throw new RuntimeException('Cannot create CSV');
fwrite($fh,"\xEF\xBB\xBF");
fputcsv($fh, ['id','book_code','title','author','publisher','publication_year','isbn','isbn_status','cover_url_in_db','renderable_cover_status','is_active','is_public']);
foreach ($rows as $row) fputcsv($fh,array_map('ph_csv_safe',$row));
fclose($fh);
echo json_encode(['mode'=>'READ_ONLY_MYSQL','stats'=>$stats,'csv_report'=>$filename], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),PHP_EOL;
