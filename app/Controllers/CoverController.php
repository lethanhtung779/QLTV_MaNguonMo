<?php
declare(strict_types=1);
namespace Library\Controllers;
final class CoverController
{
    public function show(string $name): never {
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/',$name,$m)) {http_response_code(404);exit;}
        $path=dirname(__DIR__,2).'/storage/uploads/covers/'.$name;
        if (!is_file($path)) {http_response_code(404);exit;}
        header('Content-Type: '.['jpg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'][$m[1]]);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: '.filesize($path)); readfile($path); exit;
    }
}
