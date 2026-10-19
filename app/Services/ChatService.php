<?php
declare(strict_types=1);
namespace Library\Services;
use PDO;
use Library\Repositories\ChatRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Repositories\FineRepository;
use Library\Repositories\FavoriteRepository;

final class ChatService
{
 private ChatAuthorizationService $auth;
 private LibrarySearchService $search;
 private ChatResponseFormatter $format;
 public function __construct(private readonly PDO $db,private readonly ?LLMProvider $llm=null){$this->auth=new ChatAuthorizationService($db);$this->search=new LibrarySearchService($db);$this->format=new ChatResponseFormatter();}
 public function reset():void{unset($_SESSION['_chat']);}
 private function context(?array $user):void {
  $identity=$this->auth->identity($user);$old=$_SESSION['_chat']??[];
  if(($old['identity']??null)!==$identity||time()-(int)($old['time']??0)>1200)$_SESSION['_chat']=['identity'=>$identity,'books'=>[],'selected'=>null];
  $_SESSION['_chat']['time']=time();
 }
 public function state():array{$u=$this->auth->user();$this->context($u);return ['greeting'=>'Chào bạn! Tôi là Trợ lý Paper Haven. Tôi giúp bạn tìm sách, kiểm tra số bản còn sẵn, tra cứu hồ sơ mượn và giải đáp quy định thư viện.','suggestions'=>['Hoàng tử bé','Thời hạn mượn sách bao lâu?','Tôi đang mượn sách nào?','Gợi ý sách lập trình web'],'ai_available'=>($this->llm??new LLMProvider())->enabled(),'history_stored'=>false,'csrf'=>\Library\Core\Csrf::token()];}
 public function reply(string $message,bool $useAi=false):array {
  $user=$this->auth->user();$this->context($user);$plan=(new IntentClassifier())->classify($message);$intent=$plan['intent'];$s=$plan['normalized'];$publicOnly=!$this->auth->allows($user,'books.view');$repo=new ChatRepository($this->db);
  if($intent==='blocked')return $this->format->response($intent,'Tôi chỉ hỗ trợ tra cứu thư viện theo quyền của bạn; không cung cấp mật khẩu, thông tin người khác hoặc thực thi SQL.');
  if($intent==='help') {
   $text="Tôi là Trợ lý Paper Haven, có thể hỗ trợ bạn:\n• Tìm sách theo tên, tác giả, mã sách (Bxxx), ISBN hoặc thể loại; kiểm tra tình trạng còn sách thực tế và mở chi tiết.\n• Gợi ý sách theo chủ đề từ kho sách thư viện.\n• Hướng dẫn quy định mượn/trả, thời hạn, gia hạn và đặt trước sách.\n• Khi đăng nhập Độc giả: tra cứu sách đang mượn, hạn trả, lịch sử mượn, yêu cầu đặt trước và tiền phạt của chính bạn.\nBạn có thể thử hỏi: ‘Hoàng tử bé’, ‘Thời hạn mượn sách bao lâu?’, ‘Tôi đang mượn sách nào?’.";
   if($this->auth->allows($user,'loans.view')&&$this->auth->allows($user,'reports.view'))$text.="\n• Theo quyền nhân viên: tra cứu báo cáo sách quá hạn, hướng dẫn kiểm kê kho và in tem nhãn.";
   if(($user['role']??'')==='admin'&&$this->auth->allows($user,'reports.view'))$text.="\n• Theo quyền Admin: xem thống kê mượn, độc giả và phí chưa thu.";
   $text.="\nTôi tra cứu trực tiếp dữ liệu thật từ cơ sở dữ liệu thư viện; mọi thao tác mượn/trả/gia hạn được hướng dẫn đến đúng trang để bạn thực hiện.";
   return $this->format->response($intent,$text,[],[['label'=>'Mở danh mục sách','url'=>url('/catalog')]],['Hoàng tử bé','Thời hạn mượn sách bao lâu?','Tôi đang mượn sách nào?','Quy định mượn sách']);
  }
  if($intent==='unknown'&&$useAi){$provider=$this->llm??new LLMProvider();$extra=$provider->classify($message);if($extra){$plan=$extra;$intent=$extra['intent'];}}
  if(in_array($intent,['search','recommend'],true)) {
   $books=$this->search->search($plan['query'],$plan['limit'],$publicOnly);$_SESSION['_chat']['books']=array_column($books,'id');$_SESSION['_chat']['selected']=count($books)===1?(int)$books[0]['id']:null;
   if(!$books){
    $text='Không tìm thấy sách nào phù hợp với yêu cầu "'.$plan['query'].'" trong kho sách công khai của thư viện. Bạn hãy thử tìm với tên sách, tác giả hoặc mã ISBN khác.';
    return $this->format->response($intent,$text,[],[['label'=>'Khám phá danh mục','url'=>url('/catalog')]],['Hoàng tử bé','Giết con chim nhại','Quy định mượn sách']);
   }
   if(count($books)===1){
    $b=$books[0];$avail=(int)$b['available_copies'];
    $statusText=$avail>0?"Hiện có {$avail} bản sẵn sàng phục vụ mượn đọc tại quầy.":"Hiện chưa có bản khả dụng để mượn (đang có độc giả mượn hoặc đang trong quá trình kiểm kê).";
    $text="Tìm thấy sách: {$b['title']}".(!empty($b['author'])?" — {$b['author']}":"")."\n{$statusText}\nBạn có thể xem chi tiết bên dưới để biết thông tin ấn bản, số trang và vị trí kệ.";
   }else{
    $text=$intent==='recommend'?'Gợi ý các tựa sách phù hợp từ danh mục thư viện Paper Haven:':'Tìm thấy '.count($books).' cuốn sách phù hợp trong kho thư viện (kèm tình trạng bản sao thực tế):';
   }
   return $this->format->response($intent,$text,$books,[],['Thời hạn mượn sách bao lâu?','Quy định đặt trước','Tôi đang mượn sách nào?']);
  }
  if($intent==='context') {
   $ids=$_SESSION['_chat']['books']??[];$selected=$_SESSION['_chat']['selected']??null;
   if(preg_match('/cuon (?:thu )?(mot|hai|ba|bon|nam|sau|bay|tam|chin|muoi|[1-9][0-9]?)/',$s,$m)){$words=['mot'=>1,'hai'=>2,'ba'=>3,'bon'=>4,'nam'=>5,'sau'=>6,'bay'=>7,'tam'=>8,'chin'=>9,'muoi'=>10];$index=($words[$m[1]]??(int)$m[1])-1;$selected=$ids[$index]??null;}
   if(!$selected&&count($ids)===1)$selected=$ids[0];$book=$selected?$this->search->find((int)$selected,$publicOnly):null;
   if(!$book)return $this->format->response($intent,'Tôi chưa xác định được cuốn sách. Hãy tìm lại hoặc nói rõ thứ tự trong danh sách vừa trả lời.');
   $_SESSION['_chat']['selected']=(int)$book['id'];return $this->format->response($intent,$book['title'].': '.((int)$book['available_copies']>0?'còn '.$book['available_copies'].' bản có thể mượn.':'hiện chưa có bản khả dụng.').' Tình trạng được kiểm tra lại từ MySQL.',$book?[$book]:[]);
  }
  if($intent==='personal_favorites') {
   $readerId=$this->auth->readerId($user);
   if(!$readerId)return $this->format->response($intent,'Hãy đăng nhập tài khoản độc giả để xem tủ sách và danh sách yêu thích của bạn.',[],[['label'=>'Đăng nhập độc giả','url'=>url('/login')]]);
   $favRepo=new FavoriteRepository($this->db);$favs=$favRepo->listForReader($readerId);$link=[['label'=>'Mở tủ sách của tôi','url'=>url('/account/bookshelf')]];
   if(!$favs)return $this->format->response($intent,'Tủ sách của bạn hiện chưa có sách yêu thích nào. Hãy khám phá danh mục và bấm nút "♥ Lưu vào yêu thích" ở cuốn sách bạn quan tâm.',[],$link);
   $text='Tủ sách của bạn có '.count($favs).' cuốn sách đã lưu yêu thích:';
   foreach(array_slice($favs,0,5) as $f)$text.="\n• ".$f['title'].(!empty($f['author'])?' — '.$f['author']:'');
   return $this->format->response($intent,$text,[],$link);
  }
  if($intent==='print_labels_guide') {
   return $this->format->response($intent,'Để in tem nhãn mã vạch Code 128 và mã QR cho sách: Hãy vào mục Quản lý sách > Bản sao > bấm nút "In tem nhãn gáy sách". Hệ thống hỗ trợ xem trước và in nhanh trên khổ giấy nhiệt hoặc A4 độ nét cao.',[],[['label'=>'Quản lý sách','url'=>url('/admin/books')]]);
  }
  if($intent==='inventory_guide') {
   return $this->format->response($intent,'Để kiểm kê kho thư viện: Hãy vào mục "Kiểm kê kho" (/admin/inventory/audits), bấm "Tạo đợt kiểm kê mới". Bạn có thể quét mã bản sao bằng máy đọc USB hoặc camera để đối soát tự động vị trí thực tế so với vị trí kệ trên hệ thống.',[],[['label'=>'Kiểm kê kho','url'=>url('/admin/inventory/audits')]]);
  }
  if($intent==='personal_reservations') {
   $readerId=$this->auth->readerId($user);
   if(!$readerId)return $this->format->response($intent,'Hãy đăng nhập tài khoản độc giả của bạn để tra cứu yêu cầu đặt trước sách.',[],[['label'=>'Đăng nhập độc giả','url'=>url('/login')]]);
   $reservations=$repo->reservations($readerId);$link=[['label'=>'Quản lý tài khoản','url'=>url('/account')]];
   if(!$reservations)return $this->format->response($intent,'Hiện tại bạn không có yêu cầu đặt trước sách nào đang chờ xử lý.',[],$link);
   $text='Các yêu cầu đặt trước sách của bạn:';
   foreach($reservations as $r){
    $stLabel=$r['status']==='ready'?'Sách đã sẵn sàng giữ tại quầy (hết hạn giữ: '.date('d/m/Y',strtotime($r['expires_at']??'')).')':'Đang chờ có bản sách khả dụng';
    $text.="\n• ".$r['title'].' ['.$r['book_code'].'] — '.$stLabel;
   }
   return $this->format->response($intent,$text,[],$link);
  }
  if(in_array($intent,['personal_loans','personal_history','personal_fines','renewal'],true)) {
   $readerId=$this->auth->readerId($user);if(!$readerId)return $this->format->response($intent,'Hãy đăng nhập tài khoản độc giả của bạn để tra cứu thông tin cá nhân (sách đang mượn, hạn trả và lịch sử).',[],[['label'=>'Đăng nhập độc giả','url'=>url('/login')]]);
   $link=[['label'=>'Mở tài khoản và nghiệp vụ','url'=>url('/account')]];
   if($intent==='personal_fines')return $this->format->response($intent,'Tổng phí còn nợ của bạn tính đến hôm nay: '.number_format((new FineService($this->db))->previewBalance($readerId),0,',','.').' ₫. Mức phí dùng chính sách đã lưu trên phiếu; chatbot không thu tiền.',[],$link);
   if($intent==='renewal'){$policy=(new SystemSettingRepository($this->db))->currentPolicy();return $this->format->response($intent,'Gia hạn tối đa '.$policy['max_renewals'].' lần, mỗi lần '.$policy['renewal_days'].' ngày theo chính sách hiện hành. Bản sách phải đang mượn, chưa quá hạn, còn lượt gia hạn, không có người đặt trước và thẻ còn hiệu lực. Mở tài khoản để chọn bản sách; backend sẽ kiểm tra lại và yêu cầu bạn thực hiện thao tác. Chatbot chưa gia hạn sách.',[],$link);}
   $items=$repo->loans($readerId,$intent==='personal_history');if(str_contains($s,'qua han'))$items=array_values(array_filter($items,fn($i)=>$i['returned_at']===null&&$i['due_date']<date('Y-m-d')));
   if(!$items){
    $emptyMsg=$intent==='personal_history'?'Hồ sơ của bạn chưa có lịch sử mượn sách nào.':'Hiện tại bạn không có bản sách nào đang mượn tại thư viện. Bạn có thể tra cứu sách trên danh mục và đến quầy để mượn.';
    return $this->format->response($intent,$emptyMsg,[],$link);
   }
   $text='Thông tin mượn sách của bạn (tối đa 20 bản gần nhất):';
   foreach($items as $item)$text.="\n• ".$item['title'].' — '.$item['copy_code'].' — hạn '.date('d/m/Y',strtotime($item['due_date'])).($item['returned_at']!==null?' — đã trả':($item['due_date']<date('Y-m-d')?' — quá hạn':' — đang mượn'));
   return $this->format->response($intent,$text,[],$link);
  }
  if($intent==='policy') {
   $p=(new SystemSettingRepository($this->db))->currentPolicy();
   $message="Quy định mượn trả của Thư viện Paper Haven:\n"
       ."• Số lượng mượn: Tối đa {$p['max_books_per_reader']} cuốn sách cùng lúc cho mỗi độc giả.\n"
       ."• Thời hạn mượn: {$p['default_loan_days']} ngày/lần mượn.\n"
       ."• Gia hạn: Tối đa {$p['max_renewals']} lần, mỗi lần thêm {$p['renewal_days']} ngày (khi sách chưa quá hạn và không có người đặt trước).\n"
       ."• Phí quá hạn: ".number_format((float)$p['fine_per_overdue_day'],0,',','.')." ₫/ngày/cuốn trễ hạn.\n"
       ."• Đặt trước sách: Khi sách hết bản khả dụng, độc giả có thể đặt trước trên trang chi tiết sách. Sách được giữ tối đa {$p['reservation_expiry_days']} ngày tại quầy sau khi sẵn sàng.\n"
       ."Đăng ký thẻ mới trực tuyến qua mục 'Đăng ký thẻ'. Để mượn hoặc nhận sách, bạn mang thẻ đến thư viện để thủ thư đối soát.";
   return $this->format->response($intent,$message,[],[['label'=>'Đăng ký thẻ','url'=>url('/register')],['label'=>'Danh mục sách','url'=>url('/catalog')],['label'=>'Liên hệ thư viện','url'=>url('/').'#contact']],['Thời hạn mượn sách bao lâu?','Tôi đang mượn sách nào?','Hoàng tử bé']);
  }
  if($intent==='staff_report') {
   if(!$this->auth->allows($user,'loans.view')||!$this->auth->allows($user,'reports.view'))return $this->format->response('forbidden','Bạn không có quyền xem báo cáo nghiệp vụ nội bộ.');
   if(str_contains($s,'het ban')){if(!$this->auth->allows($user,'books.view'))return $this->format->response('forbidden','Bạn không có quyền xem kho sách nội bộ.');return $this->format->response($intent,'Các sách chưa có bản khả dụng (tối đa 10):',$repo->staff('unavailable'));}
   $text='Có '.$repo->overdueCount().' bản đang mượn quá hạn.';foreach($repo->staff('overdue') as $r)$text.="\n• ".$r['loan_code'].' — '.$r['overdue_copies'].' bản quá hạn — hạn sớm nhất '.date('d/m/Y',strtotime($r['earliest_due']));return $this->format->response($intent,$text,[],[['label'=>'Xử lý phiếu mượn','url'=>url('/admin/loans?status=overdue')]]);
  }
  if($intent==='admin_report') {
   if(($user['role']??'')!=='admin'||!$this->auth->allows($user,'reports.view'))return $this->format->response('forbidden','Báo cáo này dành cho Admin có quyền xem báo cáo.');
   $data=$repo->admin();$text='Tháng '.date('m/Y').': '.$data['loans'].' phiếu mượn; '.$data['readers'].' độc giả đang hoạt động.';
   if(str_contains($s,'phat')){$sum=(new FineService($this->db))->previewTotalBalance();$text.="\nTổng phí chưa thu tính đến hôm nay: ".number_format($sum,2,',','.').' ₫.';}
   $text.="\nTop tối đa 5 đầu sách theo số bản mượn trong tháng:";foreach($data['top'] as $row)$text.="\n• ".$row['title'].' — '.$row['borrow_count'].' lượt bản sách';if(!$data['top'])$text.=' chưa có dữ liệu mượn trong tháng.';
   return $this->format->response($intent,$text,[],[['label'=>'Báo cáo thư viện','url'=>url('/admin/statistics')]]);
  }
  return $this->format->response($intent,$intent==='greeting'?'Chào bạn! Tôi có thể tìm sách, kiểm tra bản còn, tra cứu hồ sơ của bạn và hướng dẫn quy định.':'Tôi chưa hiểu rõ yêu cầu. Bạn có thể hỏi tên sách, thể loại, quy định hoặc thông tin mượn của mình. Tôi không trả lời kiến thức ngoài dữ liệu thư viện.');
 }
}
