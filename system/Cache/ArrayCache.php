<?php
/**
 * --------------------------------------------------------------------------
 * ArrayCache.php
 * --------------------------------------------------------------------------
 * Lightweight in-memory caching system for the CodeIgniter Alternative framework.
 *
 * This class provides a simple array-based caching layer that stores data
 * during a single request lifecycle. It is ideal for:
 *  - Reducing redundant computations or DB queries in the same request
 *  - Fast, temporary key-value data storage
 *  - Tracking cache performance (hits, misses, writes, deletes)
 *
 * Unlike file or Redis caches, ArrayCache does not persist data after 
 * the script execution ends — it is purely in-memory and volatile.
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

class ArrayCache
{
    /**
     * Internal cache storage.
     *
     * @var array
     */
    private $storage = [];

    /**
     * Cache performance statistics.
     *
     * @var array
     */
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'writes' => 0,
        'deletes' => 0
    ];

    /**
     * Retrieve a value from cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
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

    /**
     * Store a value in cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
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

    /**
     * Remove a specific cache entry.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key)
    {
        if (isset($this->storage[$key])) {
            unset($this->storage[$key]);
            $this->stats['deletes']++;
            return true;
        }

        return false;
    }

    /**
     * Clear all cache entries.
     *
     * @return bool
     */
    public function flush()
    {
        $count = count($this->storage);
        $this->storage = [];
        $this->stats['deletes'] += $count;
        return true;
    }

    /**
     * Determine if a cache entry exists and is valid.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->storage[$key]) && $this->storage[$key]['expires'] >= time();
    }

    /**
     * Get cache statistics and metadata.
     *
     * @return array
     */
    public function getStats()
    {
        return array_merge($this->stats, [
            'items_count' => count($this->storage),
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    /**
     * Remove expired cache entries.
     *
     * @return int  Number of removed entries
     */
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

