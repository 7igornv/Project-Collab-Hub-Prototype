<?php
class Minifier {
    public static function minifyCSS($css) {
        // Удаляем комментарии
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        // Удаляем пробелы
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        $css = preg_replace(['/\s+/', '/;\s*/'], [' ', ';'], $css);
        return $css;
    }
    
    public static function minifyJS($js) {
        // Удаляем комментарии
        $js = preg_replace('/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/', '', $js);
        // Удаляем пробелы
        $js = preg_replace('/\s+/', ' ', $js);
        return $js;
    }
    
    public static function saveMinified($content, $type) {
        $file = __DIR__ . '/../minified.' . $type;
        file_put_contents($file, $content);
        return $file;
    }
}
?>