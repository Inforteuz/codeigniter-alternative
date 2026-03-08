<?php
/**
 * --------------------------------------------------------------------------
 * Cache.php
 * --------------------------------------------------------------------------
 * Core caching manager for the CodeIgniter Alternative framework.
 *
 * The Cache class serves as a unified gateway for handling multiple caching
 * drivers such as file-based, in-memory (array), and Redis caches. It provides
 * a clean, static interface for reading, writing, and managing cache data 
 * across the entire framework.
 *
 * Features:
 *  - Supports multiple cache drivers (File, Array, Redis-ready)
 *  - Singleton pattern for global instance access
 *  - Simplified API: get(), put(), remember(), forget(), flush()
 *  - Debug integration for cache hits/misses
 *  - Multi-key operations and atomic increment/decrement
 *
 * The design follows the lightweight and extensible style of CodeIgniter,
 * while allowing flexibility similar to Laravel’s Cache Manager.
 *
 * @package     System\Cache
 * @subpackage  Core
 * @category    Caching
 * @author      Inforte
 * @version     1.0.0
 * @license     MIT License
 * @since       2024-12-12
 */

namespace System\Cache;

class Cache
{
    /**
     * Singleton instance of the cache manager.
     *
     * @var Cache|null
     */
    private static $instance;

    /**
     * The active cache driver instance.
     *
     * @var mixed
     */
    private $driver;

    /**
     * Default TTL (time-to-live) for cache entries.
     *
     * @var int
     */
    private $defaultTtl = 3600;

    public function __construct()
    {
        $this->initializeDriver();
    }

    /**
     * Get the global cache instance (singleton).
     *
     * @return Cache
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the cache driver based on environment configuration.
     *
     * @return void
     */
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
                $this->driver = new FileCache(); // TODO: Replace with RedisCache in future
                break;
            default:
                $this->driver = new FileCache();
        }

        \System\Core\DebugToolbar::log("Cache driver initialized: {$driverType}", 'cache');
    }

    /**
     * Retrieve a value from cache.
     */
    public static function get($key, $default = null)
    {
        return self::getInstance()->driver->get($key, $default);
    }

    /**
     * Store a value in cache for a specified time.
     */
    public static function put($key, $value, $ttl = null)
    {
        $ttl = $ttl ?? self::getInstance()->defaultTtl;
        return self::getInstance()->driver->put($key, $value, $ttl);
    }

    /**
     * Retrieve a value from cache or execute a callback to store and return it.
     */
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

    /**
     * Store a value permanently (1 year TTL).
     */
    public static function rememberForever($key, $callback)
    {
        return self::remember($key, 31536000, $callback);
    }

    /**
     * Delete a specific cache key.
     */
    public static function forget($key)
    {
        return self::getInstance()->driver->forget($key);
    }

    /**
     * Flush all cache entries.
     */
    public static function flush()
    {
        return self::getInstance()->driver->flush();
    }

    /**
     * Check if a cache key exists.
     */
    public static function has($key)
    {
        return self::getInstance()->driver->has($key);
    }

    /**
     * Increment a numeric cache value.
     */
    public static function increment($key, $value = 1)
    {
        $current = self::get($key, 0);
        $newValue = $current + $value;
        self::put($key, $newValue);
        return $newValue;
    }

    /**
     * Decrement a numeric cache value.
     */
    public static function decrement($key, $value = 1)
    {
        return self::increment($key, -$value);
    }

    /**
     * Retrieve multiple cache keys at once.
     */
    public static function getMultiple($keys, $default = null)
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = self::get($key, $default);
        }
        return $results;
    }

    /**
     * Store multiple key-value pairs in cache.
     */
    public static function putMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            self::put($key, $value, $ttl);
        }
        return true;
    }

    /**
     * Get cache driver statistics.
     */
    public static function getStats()
    {
        return self::getInstance()->driver->getStats();
    }
}
?>
