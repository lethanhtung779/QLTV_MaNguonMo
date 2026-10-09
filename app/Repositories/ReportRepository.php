<?php
declare(strict_types=1);
namespace Library\Repositories;
use PDO;
use InvalidArgumentException;
final class ReportRepository
{
    public function __construct(private readonly PDO $db) {}
    public function rows(string $kind,int $limit=10001): array
    {
        $limit=max(1,min(10001,$limit));
        if ($kind==='inventory') {
            $sql='SELECT b.book_code,b.title,cp.copy_code,cp.barcode,cp.shelf_location,cp.item_condition,cp.status,b.is_active FROM book_copies cp JOIN books b ON b.id=cp.book_id ORDER BY b.book_code,cp.copy_code';
        } elseif ($kind==='fines') {
            $sql='SELECT r.card_number,r.full_name,l.loan_code,cp.copy_code,fc.charge_type,fc.amount,COALESCE(p.paid,0) AS paid,COALESCE(w.waived,0) AS waived,GREATEST(fc.amount-COALESCE(p.paid,0)-COALESCE(w.waived,0),0) AS balance,fc.created_at FROM fine_charges fc JOIN readers r ON r.id=fc.reader_id JOIN loan_items li ON li.id=fc.loan_item_id JOIN loans l ON l.id=li.loan_id JOIN book_copies cp ON cp.id=li.book_copy_id LEFT JOIN (SELECT fine_charge_id,SUM(amount) paid FROM fine_payment_allocations GROUP BY fine_charge_id) p ON p.fine_charge_id=fc.id LEFT JOIN (SELECT fine_charge_id,SUM(amount) waived FROM fine_waivers GROUP BY fine_charge_id) w ON w.fine_charge_id=fc.id ORDER BY fc.id DESC';
        } else throw new InvalidArgumentException('Loại báo cáo không hợp lệ.');
        return $this->db->query($sql.' LIMIT '.$limit)->fetchAll();
    }
    public static function headers(string $kind): array
    {
        return $kind==='inventory' ? ['Mã sách','Tên sách','Mã bản sao','Mã vạch','Vị trí kệ','Tình trạng','Trạng thái','Đầu sách hoạt động'] : ['Mã thẻ','Độc giả','Mã phiếu','Mã bản sao','Loại phí','Phí phát sinh','Đã thu','Miễn giảm','Còn phải thu','Ngày ghi nhận'];
    }
}
