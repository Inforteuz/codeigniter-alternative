<?php
/**
 * --------------------------------------------------------------------------
 * FileCache.php
 * --------------------------------------------------------------------------
 * File-based caching driver for the CodeIgniter Alternative framework.
 *
 * This class provides a persistent caching layer using the local file system.
 * Each cache entry is stored as a serialized `.cache` file under the 
 * `/writable/cache/` directory. It is the default driver for the framework’s 
 * Cache system.
 *
 * Features:
 *  - Stores cache entries as serialized PHP arrays
 *  - Automatically creates secure cache directory with `.htaccess`
 *  - Supports TTL-based expiration
 *  - Provides cache statistics and cleanup utilities
 *  - Fully compatible with CacheHelper and Cache facade
 *
 * Ideal for small to medium projects where Redis or Memcached is not required.
 *
 * @package     System\Cache
 * @subpackage  Core
 * @category    Caching
 * @author      Inforte
 * @version     1.0.0
 * @license     MIT License
 * @since       2025-10-12
 */

namespace System\Cache;

class FileCache
{
    /**
     * Path to the cache storage directory.
     *
     * @var string
     */
    private $cachePath;

    /**
     * Cache operation statistics.
     *
     * @var array
     */
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

    /**
     * Ensure the cache directory exists and is protected.
     *
     * @return void
     */
    private function ensureCacheDirectory()
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }

        // Create .htaccess to prevent public access (for Apache)
        $htaccess = $this->cachePath . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    /**
     * Retrieve a value from cache.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
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

    /**
     * Delete a cache file.
     *
     * @param string $key
     * @return bool
     */
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

    /**
     * Remove all cache files.
     *
     * @return bool
     */
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

    /**
     * Check if a cache key exists and is valid.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $value = $this->get($key);
        return $value !== null;
    }

    /**
     * Get cache statistics and meta info.
     *
     * @return array
     */
    public function getStats()
    {
        return array_merge($this->stats, [
            'files_count' => count(glob($this->cachePath . '*.cache')),
            'directory' => $this->cachePath,
            'size' => $this->getDirectorySize($this->cachePath)
        ]);
    }

    /**
     * Generate cache file path for the given key.
     *
     * @param string $key
     * @return string
     */
    private function getCacheFile($key)
    {
        $hashedKey = md5($key);
        return $this->cachePath . $hashedKey . '.cache';
    }

    /**
     * Read and unserialize cache file contents.
     *
     * @param string $file
     * @return array|false
     */
    private function readCacheFile($file)
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return false;
        }

        return unserialize($content);
    }

    /**
     * Calculate total size of cache directory.
     *
     * @param string $path
     * @return int
     */
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

    /**
     * Remove all expired cache files.
     *
     * @return int  Number of removed cache files
     */
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
