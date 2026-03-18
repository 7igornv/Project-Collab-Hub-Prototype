<?php
class Cache {
    private $cacheDir;
    private $ttl = 3600; // 1 час
    
    public function __construct() {
        $this->cacheDir = __DIR__ . '/../cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        
        if(file_exists($file) && (time() - filemtime($file)) < $this->ttl) {
            return unserialize(file_get_contents($file));
        }
        return null;
    }
    
    public function set($key, $data) {
        $file = $this->cacheDir . md5($key) . '.cache';
        file_put_contents($file, serialize($data));
    }
    
    public function clear() {
        $files = glob($this->cacheDir . '*.cache');
        foreach($files as $file) {
            unlink($file);
        }
    }
}
?>