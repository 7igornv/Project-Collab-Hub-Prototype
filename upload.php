<?php
require_once 'utils/ImageOptimizer.php';

if($_FILES['image']) {
    $optimizer = new ImageOptimizer();
    $filename = time() . '_' . basename($_FILES['image']['name']);
    $result = $optimizer->optimize($_FILES['image'], $filename);
    
    echo "<!DOCTYPE html>
    <html>
    <head><title>Результат</title></head>
    <body>
        <h2>Изображение обработано!</h2>
        <p>Файл: $result</p>
        <h3>Оригинал (сжатый):</h3>
        <img src='/uploads/$result' style='max-width:500px'><br>
        <h3>Превью:</h3>
        <img src='/uploads/thumbs/$result'><br>
        <a href='upload-test.html'>Загрузить еще</a>
    </body>
    </html>";
}
?>