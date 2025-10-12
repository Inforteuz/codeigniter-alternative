<?php
namespace System\Cache;

class FileCache
{
    private $cachePath;
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];
    
    public function __construct()
    {
        $this->cachePath = __DIR__ . '/../../writable/cache/';
        $this->ensureCacheDirectory();
    }
    
    private function ensureCacheDirectory()
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
        
        // .htaccess yaratish (Apache uchun)
        $htaccess = $this->cachePath . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }
    
    public function get($key, $default = null)
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            $this->stats['misses']++;
            return $default;
        }
        
        $data = $this->readCacheFile($file);
        
        if ($data === false) {
            $this->stats['misses']++;
            return $default;
        }
        
        if ($data['expires'] < time()) {
            $this->forget($key);
            $this->stats['misses']++;
            return $default;
        }
        
        $this->stats['hits']++;
        return $data['value'];
    }
    
    public function put($key, $value, $ttl)
    {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $result = file_put_contents($file, serialize($data), LOCK_EX);
        
        if ($result !== false) {
            $this->stats['writes']++;
            return true;
        }
        
        return false;
    }
    
    public function forget($key)
    {
        $file = $this->getCacheFile($key);
        
        if (file_exists($file)) {
            $result = unlink($file);
            if ($result) {
                $this->stats['deletes']++;
            }
            return $result;
        }
        
        return true;
    }
    
    public function flush()
    {
        $files = glob($this->cachePath . '*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted++;
            }
        }
        
        $this->stats['deletes'] += $deleted;
        return $deleted > 0;
    }
    
    public function has($key)
    {
        $value = $this->get($key);
        return $value !== null;
    }
    
    public function getStats()
    {
        return array_merge($this->stats, [
            'files_count' => count(glob($this->cachePath . '*.cache')),
            'directory' => $this->cachePath,
            'size' => $this->getDirectorySize($this->cachePath)
        ]);
    }
    
    private function getCacheFile($key)
    {
        $hashedKey = md5($key);
        return $this->cachePath . $hashedKey . '.cache';
    }
    
    private function readCacheFile($file)
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return false;
        }
        
        return unserialize($content);
    }
    
    private function getDirectorySize($path)
    {
        $size = 0;
        $files = glob($path . '*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }
        
        return $size;
    }
    
    public function cleanupExpired()
    {
        $files = glob($this->cachePath . '*.cache');
        $cleaned = 0;
        
        foreach ($files as $file) {
            $data = $this->readCacheFile($file);
            if ($data && $data['expires'] < time()) {
                unlink($file);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}
?>