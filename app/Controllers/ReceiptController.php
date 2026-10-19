<?php
declare(strict_types=1);
namespace Library\Controllers;
use Library\Repositories\LoanRepository;
use Library\Repositories\ActivityRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
final class ReceiptController extends Controller
{
    public function show(string $kind,int $id): never {
        $user=require_auth();
        if ($user['role']!=='reader') require_permission($kind==='payment'?'fines.view':'loans.view');
        $sql=$kind==='payment'
            ?'SELECT p.*,r.full_name,r.card_number,r.user_id,u.name AS staff_name FROM fine_payments p JOIN readers r ON r.id=p.reader_id LEFT JOIN users u ON u.id=p.received_by WHERE p.id=?'
            :'SELECT l.*,r.full_name,r.card_number,r.user_id,u.name AS staff_name FROM loans l JOIN readers r ON r.id=l.reader_id LEFT JOIN users u ON u.id=l.issued_by WHERE l.id=?';
        $s=$this->db->prepare($sql);$s->execute([$id]);$record=$s->fetch();
        if (!$record) {http_response_code(404);exit;}
        if ($user['role']==='reader' && (int)$record['user_id']!==(int)$user['id']) {http_response_code(403);exit;}
        $items=$kind==='payment'?[]:((new LoanRepository($this->db))->itemsForLoanIds([$id])[$id]??[]);
        if ($kind==='return') {
            $items=array_values(array_filter($items,fn($i)=>$i['returned_at']!==null));
            if (!$items) {http_response_code(409);exit('Phiếu chưa có bản sách được trả.');}
        }
        $title=['loan'=>'Phiếu mượn sách','return'=>'Biên nhận trả sách','payment'=>'Biên nhận thu phí'][$kind];
        $code=$kind==='payment'?'PT-'.str_pad((string)$id,8,'0',STR_PAD_LEFT):$record['loan_code'];
        $html='<html><head><meta charset="utf-8"><style>body{font-family:"DejaVu Sans";font-size:11px;color:#302B25}h1{color:#193E30;font-size:23px}h2{font-size:17px}table{width:100%;border-collapse:collapse;margin:20px 0}td,th{padding:9px;border:1px solid #E8D8C5;text-align:left}th{background:#F8F3EB}small{color:#74685c}</style></head><body><h1>Paper Haven</h1><h2>'.e($title).'</h2><p>Mã: '.e($code).'</p><p>Độc giả: '.e($record['full_name']).' · Thẻ '.e($record['card_number']).'</p>';
        if ($kind==='payment') {
            $html.='<p>Thời điểm thu: '.e($record['created_at']).'</p><p>Số tiền đã thu: <strong>'.number_format((float)$record['amount'],0,',','.').' đồng</strong></p><p>Hình thức: '.e(['cash'=>'Tiền mặt','bank_transfer'=>'Chuyển khoản','card'=>'Thẻ','other'=>'Khác'][$record['method']]??$record['method']).'</p><p>Tham chiếu: '.e($record['reference_code']??'').'</p><p>Người nhận: '.e($record['staff_name']??'').'</p>';
            $s=$this->db->prepare('SELECT a.amount,fc.description FROM fine_payment_allocations a JOIN fine_charges fc ON fc.id=a.fine_charge_id WHERE a.payment_id=? ORDER BY a.fine_charge_id');$s->execute([$id]);
            $html.='<table><tr><th>Khoản phí được thanh toán</th><th>Số tiền</th></tr>';
            foreach ($s->fetchAll() as $allocation) $html.='<tr><td>'.e($allocation['description']).'</td><td>'.number_format((float)$allocation['amount'],0,',','.').' đ</td></tr>';
        } else {
            $html.='<p>Ngày mượn: '.e(format_date($record['borrowed_at'])).' · Người lập: '.e($record['staff_name']??'').'</p><table><tr><th>Sách / bản sao</th><th>Hạn trả hiện tại</th>'.($kind==='return'?'<th>Ngày trả</th><th>Tình trạng khi trả</th>':'').'</tr>';
            foreach ($items as $item) {
                $html.='<tr><td>'.e($item['book_title']).'<br>'.e($item['copy_code']).'</td><td>'.e(format_date($item['due_date'])).'</td>';
                if ($kind==='return') $html.='<td>'.e($item['returned_at']).'</td><td>'.e(['good'=>'Tốt','worn'=>'Hao mòn','damaged'=>'Hỏng','lost'=>'Mất'][$item['return_condition']??'']??'Lịch sử chưa có ghi nhận tình trạng').'</td>';
                $html.='</tr>';
            }
        }
        $html.='</table><p>Ghi chú: '.e($record['note']??'').'</p><p>Độc giả ký: __________________ &nbsp;&nbsp; Nhân viên ký: __________________</p><small>Bản sao từ sổ thư viện lúc '.e(date('d/m/Y H:i')).'. Hạn trả hiển thị theo các lần gia hạn đã ghi nhận. Biên nhận trả liệt kê các bản đã trả đến thời điểm xuất.</small></body></html>';
        $options=new Options();$options->set('defaultFont','DejaVu Sans');$options->set('isRemoteEnabled',false);$options->set('isPhpEnabled',false);
        $cache=dirname(__DIR__,2).'/storage/cache/pdf';if (!is_dir($cache)) mkdir($cache,0750,true);
        $options->set('fontCache',$cache);$options->set('tempDir',$cache);$options->set('chroot',[$cache,dirname(__DIR__,2).'/vendor/dompdf/dompdf/lib/fonts']);
        $pdf=new Dompdf($options);$pdf->loadHtml($html,'UTF-8');$pdf->setPaper('A4');$pdf->render();
        (new ActivityRepository($this->db))->log((int)$user['id'],'receipt_exported',$kind,$id,'Xuất '.$title.' '.$code);
        header('Cache-Control: no-store');$pdf->stream('paper-haven-'.$kind.'-'.$id.'.pdf',['Attachment'=>true]);exit;
    }
}
