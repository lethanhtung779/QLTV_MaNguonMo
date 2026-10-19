<?php
declare(strict_types=1);
namespace Library\Repositories;
use PDO;
final class ChatRepository
{
 public function __construct(private readonly PDO $db){}
 public function loans(int $readerId,bool $history=false):array {
  $sql='SELECT b.title,cp.copy_code,li.due_date,li.returned_at,li.id,cp.book_id FROM loans l JOIN loan_items li ON li.loan_id=l.id JOIN book_copies cp ON cp.id=li.book_copy_id JOIN books b ON b.id=cp.book_id WHERE l.reader_id=?'.($history?'':' AND li.returned_at IS NULL').' ORDER BY li.borrowed_at DESC,li.id DESC LIMIT 20';$q=$this->db->prepare($sql);$q->execute([$readerId]);return $q->fetchAll();
 }
 public function reservations(int $readerId): array {
  $sql='SELECT r.id,r.status,r.reserved_at,r.expires_at,b.title,b.book_code FROM reservations r JOIN books b ON b.id=r.book_id WHERE r.reader_id=? AND r.status IN ("pending","ready") ORDER BY r.reserved_at DESC LIMIT 10';$q=$this->db->prepare($sql);$q->execute([$readerId]);return $q->fetchAll();
 }
 public function staff(string $kind):array {
  if($kind==='unavailable')return (new BookRepository($this->db))->search('',0,'unavailable',10,0,'title',false);
  $q=$this->db->query('SELECT l.loan_code,COUNT(*) AS overdue_copies,MIN(li.due_date) earliest_due FROM loans l JOIN loan_items li ON li.loan_id=l.id WHERE li.returned_at IS NULL AND li.due_date<CURRENT_DATE GROUP BY l.id,l.loan_code ORDER BY earliest_due,l.id LIMIT 10');return $q->fetchAll();
 }
 public function overdueCount():int{return (int)$this->db->query('SELECT COUNT(*) FROM loan_items WHERE returned_at IS NULL AND due_date<CURRENT_DATE')->fetchColumn();}
 public function admin():array {
  $month=date('Y-m-01');$next=(new \DateTimeImmutable($month))->modify('+1 month')->format('Y-m-d');
  $q=$this->db->prepare('SELECT COUNT(*) FROM loans WHERE borrowed_at>=? AND borrowed_at<?');$q->execute([$month,$next]);$loans=(int)$q->fetchColumn();
  $q=$this->db->prepare('SELECT b.title,COUNT(*) borrow_count FROM loans l JOIN loan_items li ON li.loan_id=l.id JOIN book_copies cp ON cp.id=li.book_copy_id JOIN books b ON b.id=cp.book_id WHERE l.borrowed_at>=? AND l.borrowed_at<? GROUP BY b.id,b.title ORDER BY borrow_count DESC,b.id LIMIT 5');$q->execute([$month,$next]);
  return ['loans'=>$loans,'readers'=>(new DashboardRepository($this->db))->metrics()['reader_count'],'top'=>$q->fetchAll()];
 }
}
