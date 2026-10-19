<?php
declare(strict_types=1);
namespace Library\Services;
final class ChatResponseFormatter
{
 public function response(string $intent,string $message,array $books=[],array $links=[],array $suggestions=[]):array {
  return ['intent'=>$intent,'message'=>$message,'books'=>array_map(fn($b)=>['id'=>(int)$b['id'],'book_code'=>$b['book_code']??'','title'=>$b['title'],'author'=>$b['author'],'category'=>$b['category_name']??'','available'=>(int)$b['available_copies'],'cover_url'=>book_cover($b,'M'),'cover_note'=>isbn_cover_source($b)?'Bìa tiếng Anh theo ISBN':'','url'=>url('/book/'.(int)$b['id'])],$books),'links'=>$links,'suggestions'=>$suggestions?:['Hoàng tử bé','Thời hạn mượn sách bao lâu?','Tôi đang mượn sách nào?','Gợi ý sách lập trình web'],'mode'=>'library'];
 }
}
