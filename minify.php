<?php
require_once 'utils/Minifier.php';

// Минифицируем CSS
$css = file_get_contents('css/style.css');
$minifiedCSS = Minifier::minifyCSS($css);
file_put_contents('css/style.min.css', $minifiedCSS);
echo "CSS минифицирован: " . round(strlen($minifiedCSS)/1024, 2) . " KB\n";

// Минифицируем JS
$js = file_get_contents('js/api.js');
$minifiedJS = Minifier::minifyJS($js);
file_put_contents('js/api.min.js', $minifiedJS);
echo "JS минифицирован: " . round(strlen($minifiedJS)/1024, 2) . " KB\n";