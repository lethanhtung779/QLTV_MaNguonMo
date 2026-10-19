<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/Services/BarcodeService.php';
use Library\Services\BarcodeService;

$service = new BarcodeService();
$failures = [];
$check = static function(bool $ok, string $message) use (&$failures): void {
    if (!$ok) $failures[] = $message;
};

/* Black-box QR decoder: reads only the public SVG, never the private matrix. */
$decodeQr = static function(string $svg): array {
    preg_match('/<svg[^>]+width="(\d+)"/', $svg, $dim);
    preg_match_all('/<rect x="(\d+)" y="(\d+)" width="(\d+)" height="\3" fill="#111827"\/>/', $svg, $rects, PREG_SET_ORDER);
    if (!$dim || !$rects) throw new RuntimeException('missing QR geometry');
    $cell = (int)$rects[0][3];
    $n = intdiv((int)$dim[1], $cell) - 8;
    $version = intdiv($n - 17, 4);
    if ($version < 1 || $version > 4 || $n !== 17 + 4 * $version) throw new RuntimeException('invalid QR size');
    $m = array_fill(0, $n, array_fill(0, $n, 0));
    foreach ($rects as $r) {
        if ((int)$r[3] !== $cell) throw new RuntimeException('non-square module');
        $x = intdiv((int)$r[1], $cell) - 4; $y = intdiv((int)$r[2], $cell) - 4;
        if ($x < 0 || $x >= $n || $y < 0 || $y >= $n) throw new RuntimeException('quiet-zone violation');
        $m[$y][$x] = 1;
    }
    $fn = array_fill(0, $n, array_fill(0, $n, false));
    $finder = static function(int $y, int $x) use (&$fn, $n): void {
        for ($r=-1;$r<=7;$r++) for ($c=-1;$c<=7;$c++) {
            $yy=$y+$r;$xx=$x+$c;
            if ($yy>=0&&$yy<$n&&$xx>=0&&$xx<$n) $fn[$yy][$xx]=true;
        }
    };
    $finder(0,0); $finder(0,$n-7); $finder($n-7,0);
    if ($version >= 2) {
        $m0=$n-7;
        for($r=-2;$r<=2;$r++) for($c=-2;$c<=2;$c++) $fn[$m0+$r][$m0+$c]=true;
    }
    for($i=8;$i<$n-8;$i++){ $fn[6][$i]=true;$fn[$i][6]=true; }
    for($i=0;$i<9;$i++){ $fn[8][$i]=true;$fn[$i][8]=true; }
    for($i=$n-8;$i<$n;$i++) $fn[8][$i]=true;
    for($i=$n-7;$i<$n;$i++) $fn[$i][8]=true;
    $fn[4*$version+9][8]=true;
    for($i=0;$i<15;$i++){
        $expected=(0x5412>>$i)&1;
        $a=$i<6?$m[$i][8]:($i<8?$m[$i+1][8]:($i===8?$m[8][7]:$m[8][14-$i]));
        $b=$i<8?$m[8][$n-1-$i]:$m[$n-15+$i][8];
        if($a!==$expected||$b!==$expected) throw new RuntimeException("bad format bit $i");
    }
    $bits='';$up=true;
    for($col=$n-1;$col>0;$col-=2){
        if($col===6)$col--;
        foreach($up?range($n-1,0):range(0,$n-1) as $row) foreach([$col,$col-1] as $x) if(!$fn[$row][$x]){
            $bit=$m[$row][$x]; if(($row+$x)%2===0)$bit^=1; $bits.=$bit;
        }
        $up=!$up;
    }
    $total=[1=>26,2=>44,3=>70,4=>100][$version];
    $dataCount=[1=>16,2=>28,3=>44,4=>64][$version];
    $cw=[]; for($i=0;$i<$total*8;$i+=8)$cw[]=bindec(substr($bits,$i,8));
    /* Verify the public matrix's Reed-Solomon parity, not only its readable
       payload. A damaged/incorrect QR implementation must not get a green
       test merely because the leading data bytes still happen to be intact. */
    $gfExp=array_fill(0,512,0);$gfLog=array_fill(0,256,0);$x=1;
    for($i=0;$i<255;$i++){
        $gfExp[$i]=$x;$gfLog[$x]=$i;$x<<=1;
        if($x&0x100)$x^=0x11D;
    }
    for($i=255;$i<512;$i++)$gfExp[$i]=$gfExp[$i-255];
    $mul=static function(int $a,int $b)use($gfExp,$gfLog):int{
        return ($a===0||$b===0)?0:$gfExp[$gfLog[$a]+$gfLog[$b]];
    };
    $blocks=[];$ecCount=$version===1?10:($version===2?16:($version===3?26:18));
    if($version===4){
        $blocks=[array_fill(0,50,0),array_fill(0,50,0)];
        for($i=0;$i<32;$i++)for($block=0;$block<2;$block++)$blocks[$block][$i]=$cw[$i*2+$block];
        for($i=0;$i<18;$i++)for($block=0;$block<2;$block++)$blocks[$block][32+$i]=$cw[64+$i*2+$block];
    }else $blocks=[$cw];
    foreach($blocks as $block=>$codewords)for($root=0;$root<$ecCount;$root++){
        $syndrome=0;$alpha=$gfExp[$root];
        foreach($codewords as $byte)$syndrome=$mul($syndrome,$alpha)^$byte;
        if($syndrome!==0)throw new RuntimeException("bad Reed-Solomon parity in block $block at root $root");
    }
    if($version===4){
        $data=[];
        for($block=0;$block<2;$block++) for($i=0;$i<32;$i++) $data[]=$cw[$i*2+$block];
    } else $data=array_slice($cw,0,$dataCount);
    $db=''; foreach($data as $byte)$db.=str_pad(decbin($byte),8,'0',STR_PAD_LEFT);
    if(substr($db,0,4)!=='0100') throw new RuntimeException('not byte mode');
    $len=bindec(substr($db,4,8)); $payload='';
    for($i=0;$i<$len;$i++)$payload.=chr(bindec(substr($db,12+$i*8,8)));
    return [$payload,$version];
};

/* Black-box Code 128 decoder with an independent modulo-103 check. */
$decode128 = static function(string $svg): string {
    $gcd = static function(int $a, int $b): int {
        while ($b !== 0) { $remainder = $a % $b; $a = $b; $b = $remainder; }
        return abs($a);
    };
    $patterns=[
      '212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
    $lookup=array_flip($patterns);
    preg_match_all('/<rect x="(\d+)" y="24" width="(\d+)" height="\d+" fill="#111827"\/>/', $svg, $bars, PREG_SET_ORDER);
    if(!$bars)throw new RuntimeException('missing bars');
    $scale=0;foreach($bars as $b)$scale=$scale===0?(int)$b[2]:$gcd($scale,(int)$b[2]);
    $runs=[];
    foreach($bars as $i=>$b){
        $x=(int)$b[1];$w=(int)$b[2];$runs[]=intdiv($w,$scale);
        if(isset($bars[$i+1]))$runs[]=intdiv((int)$bars[$i+1][1]-$x-$w,$scale);
    }
    $encoded=implode('',$runs);$values=[];
    while($encoded!==''){
        $length=strlen($encoded)===7?7:6;$pattern=substr($encoded,0,$length);
        if(!isset($lookup[$pattern]))throw new RuntimeException("unknown pattern $pattern");
        $values[]=(int)$lookup[$pattern];$encoded=substr($encoded,$length);
    }
    if(count($values)<4||array_pop($values)!==106)throw new RuntimeException('missing stop');
    $checksum=array_pop($values);$sum=$values[0];
    for($i=1;$i<count($values);$i++)$sum+=$values[$i]*$i;
    if($sum%103!==$checksum)throw new RuntimeException('checksum mismatch');
    $subset=array_shift($values)===105?'C':'B';$payload='';
    foreach($values as $v){
        if($subset==='C'){
            if($v===100){$subset='B';continue;}
            if($v>99)throw new RuntimeException('invalid subset C');
            $payload.=str_pad((string)$v,2,'0',STR_PAD_LEFT);
        }else{
            if($v===99){$subset='C';continue;}
            if($v>94)throw new RuntimeException('invalid subset B');
            $payload.=chr($v+32);
        }
    }
    return $payload;
};

foreach([1=>['A',str_repeat('A',14)],2=>[str_repeat('A',15),str_repeat('A',26)],3=>[str_repeat('A',27),str_repeat('A',42)],4=>[str_repeat('A',43),str_repeat('Q',62)]] as $version=>$payloads){
    foreach($payloads as $payload)try{
        [$decoded,$actualVersion]=$decodeQr($service->renderQrSvg($payload));
        $check($decoded===$payload,"QR payload mismatch at length ".strlen($payload));
        $check($actualVersion===$version,"QR version mismatch at length ".strlen($payload));
    }catch(Throwable $e){$failures[]="QR decode failed at length ".strlen($payload).": ".$e->getMessage();}
}
// Prove that this test rejects a symbol whose payload bytes remain readable
// but whose public matrix has one corrupted data module.
$damagedQr=$service->renderQrSvg('ECC-MUST-BE-CHECKED');
preg_match_all('/<rect x="(\d+)" y="(\d+)" width="(\d+)" height="\3" fill="#111827"\/>/',$damagedQr,$damagedRects,PREG_SET_ORDER|PREG_OFFSET_CAPTURE);
$damaged=false;
for($i=count($damagedRects)-1;$i>=0;$i--){
    $cell=(int)$damagedRects[$i][3][0];
    $gridX=intdiv((int)$damagedRects[$i][1][0],$cell)-4;
    $gridY=intdiv((int)$damagedRects[$i][2][0],$cell)-4;
    if($gridX>8&&$gridY>8){
        $damagedQr=substr_replace($damagedQr,'',$damagedRects[$i][0][1],strlen($damagedRects[$i][0][0]));
        $damaged=true;
        break;
    }
}
if(!$damaged)$failures[]='could not construct damaged QR fixture';
else try{$decodeQr($damagedQr);$failures[]='QR with corrupted Reed-Solomon data was accepted';}catch(RuntimeException){}
foreach(['B810-0001','12345678901234567890','ABCD12345EF6789','! PAPER-HAVEN ~',str_repeat('B',80)] as $payload)try{
    $check($decode128($service->renderBarcodeSvg($payload))===$payload,"Code 128 mismatch: $payload");
}catch(Throwable $e){$failures[]="Code 128 decode failed: ".$e->getMessage();}

$numeric=$service->renderBarcodeSvg('12345678901234567890');$alpha=$service->renderBarcodeSvg('ABCDEFGHIJKLMNOPQRST');
preg_match('/width="(\d+)"/',$numeric,$nw);preg_match('/width="(\d+)"/',$alpha,$aw);
$check((int)($nw[1]??0)<(int)($aw[1]??0),'numeric subset C must be narrower');
foreach([fn()=>$service->renderQrSvg(str_repeat('Q',63)),fn()=>$service->renderBarcodeSvg(str_repeat('B',81)),fn()=>$service->renderBarcodeSvg("bad\ncode")] as $call){
    try{$call();$failures[]='invalid input accepted';}catch(InvalidArgumentException){}
}
if($failures){fwrite(STDERR,implode(PHP_EOL,$failures).PHP_EOL);exit(1);}
echo "Barcode/QR black-box decode + ECC checks PASS (8 QR + 5 Code 128)".PHP_EOL;
