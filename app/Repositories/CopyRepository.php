<?php
declare(strict_types=1);
namespace Library\Repositories;
use PDO;
use RuntimeException;
use Throwable;
use Library\Services\LoanService;
final class CopyRepository
{
    public function __construct(private readonly PDO $db) {}
    public function all(int $bookId): array
    {
        $s=$this->db->prepare('SELECT cp.*, (SELECT COUNT(*) FROM loan_items li WHERE li.book_copy_id=cp.id) AS history_count FROM book_copies cp WHERE book_id=? ORDER BY cp.id');
        $s->execute([$bookId]); return $s->fetchAll();
    }
    public function findForLabel(string $copyCode): ?array
    {
        $s=$this->db->prepare(
            'SELECT cp.id,cp.copy_code,cp.barcode,cp.status,cp.item_condition,b.id AS book_id,b.is_active,b.is_public
             FROM book_copies cp JOIN books b ON b.id=cp.book_id
             WHERE cp.copy_code=? OR cp.barcode=? LIMIT 1'
        );
        $s->execute([$copyCode, $copyCode]);
        $copy=$s->fetch();
        return $copy ?: null;
    }
    public function save(int $bookId, int $id, array $data, int $actor): int
    {
        if (!preg_match('/\A[A-Za-z0-9._-]{1,60}\z/', (string) $data['copy_code'])) {
            throw new RuntimeException('Mã bản sao chỉ được gồm chữ, số, dấu chấm, gạch dưới và gạch ngang (tối đa 60 ký tự).');
        }
        if ($data['barcode'] !== null && !preg_match('/\A[\x20-\x7E]{1,80}\z/', (string) $data['barcode'])) {
            throw new RuntimeException('Mã vạch phải gồm 1–80 ký tự ASCII in được.');
        }
        if (!in_array($data['status'], ['available','damaged','lost','retired'], true) ||
            !in_array($data['item_condition'], ['good','worn','damaged','lost'], true)) throw new RuntimeException('Trạng thái bản sao không hợp lệ.');
        if ($data['status']==='available' && in_array($data['item_condition'], ['damaged','lost'], true)) throw new RuntimeException('Sách hỏng hoặc mất không thể đưa lên kệ phục vụ.');
        if (in_array($data['status'], ['damaged','lost'],true)) $data['item_condition']=$data['status'];
        $this->db->beginTransaction();
        try {
            $book=(new BookRepository($this->db))->findForUpdate($bookId);
            if (!$book || !$book['is_active']) throw new RuntimeException('Đầu sách không còn hoạt động.');
            if ($id>0) {
                $s=$this->db->prepare('SELECT * FROM book_copies WHERE id=? AND book_id=? FOR UPDATE'); $s->execute([$id,$bookId]); $copy=$s->fetch();
                if (!$copy) throw new RuntimeException('Không tìm thấy bản sao.');
                $s=$this->db->prepare('SELECT id FROM loan_items WHERE book_copy_id=? AND returned_at IS NULL FOR UPDATE'); $s->execute([$id]);
                if ($s->fetchColumn() || in_array($copy['status'],['on_loan','reserved'],true)) throw new RuntimeException('Bản sao đang mượn hoặc giữ chỗ; hãy xử lý qua nghiệp vụ mượn/trả hoặc đặt trước.');
                $s=$this->db->prepare('SELECT id FROM reservations WHERE book_copy_id=? AND status="ready" FOR UPDATE'); $s->execute([$id]);
                if ($s->fetchColumn()) throw new RuntimeException('Bản sao đang được giữ chỗ.');
                $s=$this->db->prepare('UPDATE book_copies SET copy_code=:copy_code, barcode=:barcode, shelf_location=:shelf_location, item_condition=:item_condition, status=:status WHERE id=:id');
                $s->execute($data+['id'=>$id]);
            } else {
                $s=$this->db->prepare('INSERT INTO book_copies (book_id,copy_code,barcode,shelf_location,item_condition,status) VALUES (:book_id,:copy_code,:barcode,:shelf_location,:item_condition,:status)');
                $s->execute($data+['book_id'=>$bookId]); $id=(int)$this->db->lastInsertId();
            }
            $this->db->prepare('UPDATE books SET total_copies=(SELECT COUNT(*) FROM book_copies WHERE book_id=? AND status<>"retired") WHERE id=?')->execute([$bookId,$bookId]);
            (new LoanService($this->db,5000))->allocateAvailableReservations($bookId,$actor);
            (new ActivityRepository($this->db))->log($actor,'copy_saved','book_copy',$id,'Cập nhật bản sao '.$data['copy_code']);
            $this->db->commit(); return $id;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
