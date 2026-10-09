<?php
declare(strict_types=1);
namespace Library\Repositories;
use PDO;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
final class MetadataRepository
{
    private readonly string $table;
    public function __construct(private readonly PDO $db, public readonly string $kind)
    {
        if (!in_array($kind,['authors','publishers'],true)) throw new InvalidArgumentException('Danh mục không hợp lệ.');
        $this->table=$kind;
    }
    public function all(string $q='', int $limit=20,int $offset=0): array
    {
        $related=$this->kind==='authors' ? '(SELECT COUNT(*) FROM book_authors ba WHERE ba.author_id=m.id)' : '(SELECT COUNT(*) FROM books b WHERE b.publisher_id=m.id)';
        $s=$this->db->prepare('SELECT m.*, '.$related.' AS book_count FROM '.$this->table.' m WHERE m.name LIKE ? ORDER BY m.name,m.id LIMIT '.max(1,min(100,$limit)).' OFFSET '.max(0,$offset));
        $s->execute(['%'.$q.'%']); return $s->fetchAll();
    }
    public function count(string $q=''): int
    {
        $s=$this->db->prepare('SELECT COUNT(*) FROM '.$this->table.' WHERE name LIKE ?'); $s->execute(['%'.$q.'%']); return (int)$s->fetchColumn();
    }
    public function save(int $id,string $name,int $actor): int
    {
        if ($name==='' || mb_strlen($name)>190 || ($this->kind==='authors' && str_contains($name,';'))) throw new InvalidArgumentException('Tên không hợp lệ; mỗi tác giả dùng một tên, tối đa 190 ký tự.');
        $this->db->beginTransaction();
        try {
            if ($id>0) {
                $s=$this->db->prepare('SELECT id FROM '.$this->table.' WHERE id=? FOR UPDATE'); $s->execute([$id]);
                if (!$s->fetchColumn()) throw new RuntimeException('Không tìm thấy mục cần cập nhật.');
                $this->db->prepare('UPDATE '.$this->table.' SET name=? WHERE id=?')->execute([$name,$id]);
                if ($this->kind==='publishers') $this->db->prepare('UPDATE books SET publisher=? WHERE publisher_id=?')->execute([$name,$id]);
                else {
                    $s=$this->db->prepare('SELECT book_id FROM book_authors WHERE author_id=?'); $s->execute([$id]);
                    foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $bookId) {
                        $names=$this->db->prepare('SELECT a.name FROM authors a JOIN book_authors ba ON ba.author_id=a.id WHERE ba.book_id=? ORDER BY a.id'); $names->execute([$bookId]);
                        $canonical=implode('; ',$names->fetchAll(PDO::FETCH_COLUMN));
                        if (mb_strlen($canonical)>190) throw new RuntimeException('Tổng tên tác giả trên sách vượt 190 ký tự.');
                        $this->db->prepare('UPDATE books SET author=? WHERE id=?')->execute([$canonical,$bookId]);
                    }
                }
            } else { $this->db->prepare('INSERT INTO '.$this->table.' (name) VALUES (?)')->execute([$name]); $id=(int)$this->db->lastInsertId(); }
            (new ActivityRepository($this->db))->log($actor,'metadata_saved',$this->kind,$id,'Lưu '.$name);
            $this->db->commit(); return $id;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    public function delete(int $id,int $actor): void
    {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT name FROM '.$this->table.' WHERE id=? FOR UPDATE'); $s->execute([$id]); $name=$s->fetchColumn();
            if (!$name) throw new RuntimeException('Không tìm thấy danh mục.');
            $query=$this->kind==='authors' ? 'SELECT 1 FROM book_authors WHERE author_id=? LIMIT 1' : 'SELECT 1 FROM books WHERE publisher_id=? LIMIT 1';
            $s=$this->db->prepare($query); $s->execute([$id]);
            if ($s->fetchColumn()) throw new RuntimeException('Danh mục đang được sách sử dụng, không thể xóa.');
            $this->db->prepare('DELETE FROM '.$this->table.' WHERE id=?')->execute([$id]);
            (new ActivityRepository($this->db))->log($actor,'metadata_deleted',$this->kind,$id,'Xóa '.$name);
            $this->db->commit();
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
