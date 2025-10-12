<?php
namespace System\Cache;

class Cache
{
    private static $instance;
    private $driver;
    private $defaultTtl = 3600; 
    
    public function __construct()
    {
        $this->initializeDriver();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initializeDriver()
    {
        $driverType = \System\Core\Env::get('CACHE_DRIVER', 'file');
        
        switch ($driverType) {
            case 'file':
                $this->driver = new FileCache();
                break;
            case 'array':
                $this->driver = new ArrayCache();
                break;
            case 'redis':
                $this->driver = new FileCache();
                break;
            default:
                $this->driver = new FileCache();
        }
        
        \System\Core\DebugToolbar::log("Cache driver initialized: {$driverType}", 'cache');
    }
    
    public static function get($key, $default = null)
    {
        return self::getInstance()->driver->get($key, $default);
    }
    
    public static function put($key, $value, $ttl = null)
    {
        $ttl = $ttl ?? self::getInstance()->defaultTtl;
        return self::getInstance()->driver->put($key, $value, $ttl);
    }
    
    public static function remember($key, $ttl, $callback)
    {
        $cached = self::get($key);
        
        if ($cached !== null) {
            \System\Core\DebugToolbar::log("Cache hit: {$key}", 'cache');
            return $cached;
        }
        
        \System\Core\DebugToolbar::log("Cache miss: {$key}", 'cache');
        $value = $callback();
        self::put($key, $value, $ttl);
        
        return $value;
    }
    
    public static function rememberForever($key, $callback)
    {
        return self::remember($key, 31536000, $callback);
    }
    
    public static function forget($key)
    {
        return self::getInstance()->driver->forget($key);
    }
    
    public static function flush()
    {
        return self::getInstance()->driver->flush();
    }
    
    public static function has($key)
    {
        return self::getInstance()->driver->has($key);
    }
    
    public static function increment($key, $value = 1)
    {
        $current = self::get($key, 0);
        $newValue = $current + $value;
        self::put($key, $newValue);
        return $newValue;
    }
    
    public static function decrement($key, $value = 1)
    {
        return self::increment($key, -$value);
    }
    
    public static function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = self::get($key, $default);
        }
        return $results;
    }
    
    public static function putMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            self::put($key, $value, $ttl);
        }
        return true;
    }
    
    public static function getStats()
    {
        return self::getInstance()->driver->getStats();
    }
}
?>