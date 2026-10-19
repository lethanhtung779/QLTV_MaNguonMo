<?php
declare(strict_types=1);
require __DIR__.'/test_database_guard.php';
$image=imagecreatetruecolor(64,96);imagefill($image,0,0,imagecolorallocate($image,25,62,48));
imagejpeg($image,dirname(__DIR__).'/storage/qa-format.jpg',90);
imagewebp($image,dirname(__DIR__).'/storage/qa-format.webp',90);
imagedestroy($image);
echo "PASS: generated real JPEG and WebP fixtures.\n";
