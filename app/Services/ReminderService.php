<?php
declare(strict_types=1);
namespace Library\Services;
use PDO;
use Library\Repositories\NotificationRepository;
final class ReminderService
{
    public function __construct(private readonly PDO $db) {}
    public function synchronize(?int $userId=null): int
    {
        $sql='SELECT li.id,li.due_date,li.loan_id,r.user_id,b.title,cp.copy_code,DATEDIFF(CURRENT_DATE,li.due_date) AS overdue_days FROM loan_items li JOIN loans l ON l.id=li.loan_id JOIN readers r ON r.id=l.reader_id JOIN users u ON u.id=r.user_id JOIN book_copies cp ON cp.id=li.book_copy_id JOIN books b ON b.id=cp.book_id WHERE li.returned_at IS NULL AND r.is_active=1 AND u.is_active=1 AND li.due_date<=DATE_ADD(CURRENT_DATE,INTERVAL 3 DAY)';
        $params=[]; if($userId!==null){$sql.=' AND r.user_id=?';$params[]=$userId;}
        $s=$this->db->prepare($sql);$s->execute($params);$count=0;
        foreach($s->fetchAll() as $item) {
            $overdue=(int)$item['overdue_days']>0;
            (new NotificationRepository($this->db))->create((int)$item['user_id'], $overdue?'Sách đã quá hạn':'Sách sắp đến hạn',
                $item['title'].' ('.$item['copy_code'].') có hạn trả '.format_date($item['due_date']).($overdue ? '. Đã quá hạn '.$item['overdue_days'].' ngày.' : '. Vui lòng trả hoặc gia hạn theo quy định.'),
                'loan', 'loan',(int)$item['loan_id'], ($overdue?'overdue-reminder:':'due-reminder:').$item['id'].':'.$item['due_date']);
            $count++;
        }
        return $count;
    }
}
