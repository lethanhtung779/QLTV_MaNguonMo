<?php
declare(strict_types=1);
namespace Library\Services;
use InvalidArgumentException;
final class CoverUpload
{
    public static function store(array $file): ?string {
        if (($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if (($file['error']??-1)!==UPLOAD_ERR_OK || !is_string($file['tmp_name']??null) || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('Không thể nhận ảnh bìa.');
        if ((int)($file['size']??0)>5*1024*1024 || filesize($file['tmp_name'])>5*1024*1024) throw new InvalidArgumentException('Ảnh bìa tối đa 5 MB.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        $dimensions=@getimagesize($file['tmp_name']);
        if (!isset($extensions[$mime]) || !$dimensions || $dimensions[0]<1 || $dimensions[1]<1 || $dimensions[0]>6000 || $dimensions[1]>6000 || $dimensions[0]*$dimensions[1]>16000000) throw new InvalidArgumentException('Chọn ảnh JPG, PNG hoặc WebP hợp lệ, tối đa 6000 px mỗi chiều và 16 triệu điểm ảnh.');
        $image=@imagecreatefromstring(file_get_contents($file['tmp_name']));
        if (!$image) throw new InvalidArgumentException('Nội dung ảnh không hợp lệ.');
        $directory=dirname(__DIR__,2).'/storage/uploads/covers';
        if (!is_dir($directory) && !mkdir($directory,0750,true)) throw new \RuntimeException('Không thể lưu ảnh bìa.');
        $name=bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        // Re-encode the bitmap: discard metadata, embedded payloads and the submitted filename.
        try {
            $success=match($mime) {
                'image/jpeg'=>imagejpeg($image,$directory.'/'.$name,90),
                'image/png'=>imagepng($image,$directory.'/'.$name),
                'image/webp'=>imagewebp($image,$directory.'/'.$name,90),
            };
            if (!$success) throw new \RuntimeException('Không thể lưu ảnh.');
        } finally { imagedestroy($image); }
        return '/covers/'.$name;
    }
    public static function discard(?string $url): void {
        if ($url && preg_match('#^/covers/([a-f0-9]{32}\.(?:jpg|png|webp))$#',$url,$m)) {
            $path=dirname(__DIR__,2).'/storage/uploads/covers/'.$m[1];
            if (is_file($path)) unlink($path);
        }
    }
}
