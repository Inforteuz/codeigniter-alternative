<?php
/**
 * --------------------------------------------------------------------------
 * CacheHelper.php
 * --------------------------------------------------------------------------
 * Core cache utility class for the CodeIgniter Alternative framework.
 *
 * This helper provides a lightweight tag-based caching system built on top of 
 * the main Cache class. It allows developers to:
 *  - Manage cache entries grouped by tags
 *  - Perform rate limiting using cache-based counters
 *  - Automatically clear tagged cache data efficiently
 *  - Simplify complex caching logic with helper methods
 *
 * The CacheHelper class is designed to keep the framework modular and fast,
 * while enabling easy cache invalidation through tag groups.
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

class CacheHelper
{
    public static function cacheTags()
    {
        return new CacheTagManager();
    }

    public static function rateLimit($key, $maxAttempts, $decaySeconds = 60)
    {
        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return false;
        }

        Cache::put($key, $attempts + 1, $decaySeconds);
        return $maxAttempts - $attempts - 1;
    }

    public static function rememberWithTags($tags, $key, $ttl, $callback)
    {
        $taggedKey = self::buildTaggedKey($tags, $key);
        return Cache::remember($taggedKey, $ttl, $callback);
    }

    public static function flushTag($tag)
    {
        $cacheDir = realpath(__DIR__ . '/../../writable/cache');
        if ($cacheDir === false || !is_dir($cacheDir)) {
            return 0;
        }

        $pattern = "tag_{$tag}_*.cache";
        $iterator = new \FilesystemIterator($cacheDir);
        $deleted = 0;

        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                @unlink($file->getPathname());
                $deleted++;
            }
        }

        return $deleted;
    }

    private static function buildTaggedKey($tags, $key)
    {
        if (is_array($tags)) {
            $tags = implode('|', $tags);
        }

        return "tag_{$tags}_{$key}";
    }
}

class CacheTagManager
{
    private $tags = [];

    public function tag($tags)
    {
        if (is_array($tags)) {
            $this->tags = array_merge($this->tags, $tags);
        } else {
            $this->tags[] = $tags;
        }
        return $this;
    }

    public function remember($key, $ttl, $callback)
    {
        return CacheHelper::rememberWithTags($this->tags, $key, $ttl, $callback);
    }

    public function flush()
    {
        foreach ($this->tags as $tag) {
            CacheHelper::flushTag($tag);
        }
    }
}
?>
