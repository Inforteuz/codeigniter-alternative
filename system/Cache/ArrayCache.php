<?php
namespace System\Cache;

class ArrayCache
{
    private $storage = [];
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];
    
    public function get($key, $default = null)
    {
        if (!isset($this->storage[$key])) {
            $this->stats['misses']++;
            return $default;
        }
        
        $item = $this->storage[$key];
        
        if ($item['expires'] < time()) {
            unset($this->storage[$key]);
            $this->stats['misses']++;
            return $default;
        }
        
        $this->stats['hits']++;
        return $item['value'];
    }
    
    public function put($key, $value, $ttl)
    {
        $this->storage[$key] = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $this->stats['writes']++;
        return true;
    }
    
    public function forget($key)
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
            $this->stats['deletes']++;
            return true;
        }
        
        return false;
    }
    
    public function flush()
    {
        $count = count($this->storage);
        $this->storage = [];
        $this->stats['deletes'] += $count;
        return true;
    }
    
    public function has($key)
    {
        return isset($this->storage[$key]) && $this->storage[$key]['expires'] >= time();
    }
    
    public function getStats()
    {
        return array_merge($this->stats, [
            'items_count' => count($this->storage),
            'memory_usage' => memory_get_usage(true)
        ]);
    }
    
    public function cleanupExpired()
    {
        $cleaned = 0;
        $now = time();
        
        foreach ($this->storage as $key => $item) {
            if ($item['expires'] < $now) {
                unset($this->storage[$key]);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
}
?>