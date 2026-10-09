<?php
declare(strict_types=1);
namespace Library\Controllers;
use Library\Repositories\BookRepository;
use Library\Repositories\CopyRepository;
use Library\Repositories\MetadataRepository;
use Library\Core\Pagination;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;
final class InventoryController extends Controller
{
    public function copies(int $bookId): void
    {
        require_auth(['admin','librarian']);
        $book=(new BookRepository($this->db))->find($bookId);
        if (!$book) { http_response_code(404); $this->render('errors/404',['title'=>'Không tìm thấy sách']); return; }
        $this->render('admin/copies',['title'=>'Bản sao sách','book'=>$book,'copies'=>(new CopyRepository($this->db))->all($bookId)]);
    }
    public function saveCopy(int $bookId): never
    {
        $actor=require_auth(['admin','librarian']); validate_csrf(); $path='/admin/books/'.$bookId.'/copies';
        try {
            (new CopyRepository($this->db))->save($bookId,request_int('id',0),[
                'copy_code'=>request_string('copy_code',60,true), 'barcode'=>request_string('barcode',80) ?: null,
                'shelf_location'=>request_string('shelf_location',120) ?: null,
                'item_condition'=>request_string('item_condition',20,true), 'status'=>request_string('status',20,true),
            ],(int)$actor['id']);
            $this->success('Đã lưu bản sao và cập nhật tồn kho.',$path);
        } catch (PDOException $e) { $this->fail($e->getCode()==='23000' ? 'Mã bản sao hoặc mã vạch đã tồn tại.' : 'Không thể lưu bản sao lúc này.',$path);
        } catch (InvalidArgumentException|RuntimeException $e) { $this->fail($e->getMessage(),$path);
        } catch (Throwable $e) { error_log('Copy update failed: '.$e::class); $this->fail('Không thể lưu bản sao lúc này.',$path); }
    }
    public function reader(int $readerId): void
    {
        require_auth(['admin','librarian']);
        $s=$this->db->prepare('SELECT * FROM readers WHERE id=?'); $s->execute([$readerId]); $reader=$s->fetch();
        if (!$reader) { http_response_code(404); $this->render('errors/404',['title'=>'Không tìm thấy độc giả']); return; }
        $this->render('admin/reader_history',['title'=>'Lịch sử độc giả','reader'=>$reader,
            'loans'=>(new \Library\Repositories\LoanRepository($this->db))->forReader($readerId),
            'charges'=>(new \Library\Repositories\FineRepository($this->db))->chargesForReader($readerId)]);
    }
    public function metadata(string $kind): void
    {
        require_auth(['admin','librarian']); $repo=new MetadataRepository($this->db,$kind); $q=query_string('q');
        $pagination=new Pagination($repo->count($q),20);
        $this->render('admin/metadata',['title'=>$kind==='authors'?'Tác giả':'Nhà xuất bản','kind'=>$kind,'search'=>$q,'items'=>$repo->all($q,20,$pagination->offset),'pagination'=>$pagination]);
    }
    public function saveMetadata(string $kind,bool $delete=false): never
    {
        $actor=require_auth(['admin','librarian']); validate_csrf(); $path='/admin/'.$kind;
        try {
            $repo=new MetadataRepository($this->db,$kind); $id=request_int('id',0);
            if ($delete) $repo->delete($id,(int)$actor['id']); else $repo->save($id,request_string('name',190,true),(int)$actor['id']);
            $this->success('Đã lưu danh mục.',$path);
        } catch (PDOException $e) { $this->fail($e->getCode()==='23000'?'Tên đã tồn tại hoặc danh mục đang được sử dụng.':'Không thể lưu danh mục lúc này.',$path);
        } catch (InvalidArgumentException|RuntimeException $e) { $this->fail($e->getMessage(),$path); }
    }
}
