<?php
declare(strict_types=1);
namespace Library\Services;
final class IntentClassifier
{
 public static function normalize(string $s): string {
  $s=mb_strtolower($s,'UTF-8');$s=str_replace('đ','d',$s);
  foreach (['a'=>'àáạảãâầấậẩẫăằắặẳẵ','e'=>'èéẹẻẽêềếệểễ','i'=>'ìíịỉĩ','o'=>'òóọỏõôồốộổỗơờớợởỡ','u'=>'ùúụủũưừứựửữ','y'=>'ỳýỵỷỹ'] as $base=>$letters) $s=preg_replace('/['.$letters.']/u',$base,$s)??$s;
  $s=preg_replace('/\p{Mn}/u','',$s)??$s;
  return trim(preg_replace('/\s+/',' ',preg_replace('/[^a-z0-9\s&-]/',' ',$s))??'');
 }
 public function classify(string $message): array {
  $s=self::normalize($message);$intent='unknown';$query='';$limit=5;
  if(preg_match('/\b(select|delete|drop|insert|update)\b.+\b(from|table|into|set)\b|api key|api secret|secret|session token|env|mat khau|password|system prompt|bo qua.*(chi dan|quy tac)|ignore.*instruction|reader[_ ]?id|cua nguoi khac|doc gia khac|tai khoan khac|doc gia [0-9]|the doc gia [0-9]/',$s))$intent='blocked';
  elseif(preg_match('/top\s*[0-9]*.*muon|doc gia hoat dong|tong (tien|phi) phat.*(chua thu|thu vien)|luot muon.*thang/',$s))$intent='admin_report';
  elseif(preg_match('/bao nhieu.*qua han|phieu muon.*(xu ly|can)|sach.*het ban/',$s))$intent='staff_report';
  elseif(preg_match('/cuon (thu )?(mot|hai|ba|bon|nam|sau|bay|tam|chin|muoi|[1-9][0-9]?)|chi tiet|cuon nay|cuon do/',$s)&&!preg_match('/tim|goi y|gia han/',$s))$intent='context';
  elseif(preg_match('/(toi|cua minh).*phat|phat.*(toi|minh)|(?:phi|tien) phat.*con no|con no.*bao nhieu/',$s))$intent='personal_fines';
  elseif(preg_match('/(tu sach|sach yeu thich|danh sach yeu thich|sach da luu)/',$s))$intent='personal_favorites';
  elseif(preg_match('/(dat truoc|yeu cau dat truoc).*(cua toi|cua minh|khong)|toi.*(dat truoc|co dat truoc)|sach.*dat truoc.*(cua toi|cua minh)/',$s))$intent='personal_reservations';
  elseif(preg_match('/(in tem|in ma vach|in qr|in nhan)/',$s))$intent='print_labels_guide';
  elseif(preg_match('/(kiem ke|dot kiem ke|kiem kho)/',$s))$intent='inventory_guide';
  elseif(str_contains($s,'gia han')&&!preg_match('/quy dinh|quy tac|chinh sach/',$s))$intent='renewal';
  elseif(preg_match('/toi.*muon|sach cua toi|toi.*tra|lich su.*(toi|minh)|han tra.*(toi|minh)|toi.*qua han/',$s)&&!preg_match('/muon hoc|toi muon (tim|doc|hoc)|toi thich/',$s))$intent=str_contains($s,'lich su')?'personal_history':'personal_loans';
  elseif(preg_match('/quy dinh|quy tac|chinh sach|dang ky the|dang ky|cach muon|cach tra|thoi han|bao lau|bao nhieu (ngay|cuon)|muon toi da|so ngay|muc phat|phi qua han|khoa the|lien he|so luong.*muon/',$s))$intent='policy';
  elseif(preg_match('/(?:ban|tro ly|chatbot) (?:co the )?(?:giup|ho tro|lam) (?:duoc )?(?:gi|nhung gi)|(?:tro ly|chatbot) co the lam gi|tro ly giup duoc gi|chuc nang.*(?:chatbot|tro ly)|huong dan su dung.*(?:chatbot|tro ly)|^help$|^giup toi$/',$s))$intent='help';
  elseif(preg_match('/^(xin chao|chao|hello|hi|cam on|chao ban|chao tro ly)$/',$s))$intent='greeting';
  elseif(preg_match('/goi y|sach hay|sach moi|sach nen doc|toi muon doc|toi thich|sinh vien|nguoi moi|cho toi [0-9]/',$s))$intent='recommend';
  else $intent='search';
  if(in_array($intent,['search','recommend'],true)) {
   $clean=preg_replace('/^(co sach cua|co sach ve|tim sach ve|tim sach|goi y sach|sach cua|sach ve|cho toi|toi muon hoc|toi thich sach|co sach|co cuon|tim kiem|tim)\s+/i','',$s);
   $clean=preg_replace('/\s+(khong|nao|vay|nhi)\??$/i','',$clean);
   $clean=preg_replace('/\b(sach|cuon|tac gia|isbn)\b/i',' ',$clean);
   $clean=trim(preg_replace('/\s+/',' ',$clean)??'');
   $query=$clean!==''?$clean:$s;
   if(preg_match('/\b([1-9][0-9]?)\s*(cuon|sach)\b/',$s,$m))$limit=max(1,min(10,(int)$m[1]));
   if(str_contains($s,'sinh vien cntt')||str_contains($s,'lap trinh web')||str_contains($s,'van phong')||str_contains($s,'tam ly hoc')) {
    $query=str_contains($s,'van phong')?'van phong':(str_contains($s,'tam ly')?'tam ly':'tin hoc');
   }
  }
  return ['intent'=>$intent,'query'=>mb_substr($query,0,160),'limit'=>$limit,'normalized'=>$s];
 }
}
