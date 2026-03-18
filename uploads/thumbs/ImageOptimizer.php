<?php
class ImageOptimizer {
    private $uploadDir;
    private $thumbDir;
    
    public function __construct() {
        $this->uploadDir = __DIR__ . '/../uploads/';
        $this->thumbDir = __DIR__ . '/../uploads/thumbs/';
        
        // Создаем папки если их нет
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
        if (!file_exists($this->thumbDir)) {
            mkdir($this->thumbDir, 0777, true);
        }
    }
    
    // Оптимизация и создание превью
    public function optimize($file, $filename) {
        $targetPath = $this->uploadDir . $filename;
        $thumbPath = $this->thumbDir . $filename;
        
        // Перемещаем загруженный файл
        move_uploaded_file($file['tmp_name'], $targetPath);
        
        // Получаем информацию о изображении
        list($width, $height, $type) = getimagesize($targetPath);
        
        // Создаем превью
        switch($type) {
            case IMAGETYPE_JPEG:
                $src = imagecreatefromjpeg($targetPath);
                break;
            case IMAGETYPE_PNG:
                $src = imagecreatefrompng($targetPath);
                break;
            default:
                return $filename;
        }
        
        // Размер превью
        $thumbWidth = 300;
        $thumbHeight = 200;
        
        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        
        // Сохраняем превью
        switch($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($thumb, $thumbPath, 80); // 80% качество
                break;
            case IMAGETYPE_PNG:
                imagepng($thumb, $thumbPath, 8); // 8 - компрессия
                break;
        }
        
        // Очищаем память
        imagedestroy($src);
        imagedestroy($thumb);
        
        // Сжимаем оригинал
        $this->compressOriginal($targetPath, $type);
        
        return $filename;
    }
    
    // Сжатие оригинала
    private function compressOriginal($path, $type) {
        list($width, $height) = getimagesize($path);
        
        // Если изображение больше 1200px, уменьшаем
        $maxSize = 1200;
        if($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize/$width, $maxSize/$height);
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;
            
            switch($type) {
                case IMAGETYPE_JPEG:
                    $src = imagecreatefromjpeg($path);
                    break;
                case IMAGETYPE_PNG:
                    $src = imagecreatefrompng($path);
                    break;
            }
            
            $dst = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            switch($type) {
                case IMAGETYPE_JPEG:
                    imagejpeg($dst, $path, 85);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($dst, $path, 7);
                    break;
            }
            
            imagedestroy($src);
            imagedestroy($dst);
        }
    }
}
?>