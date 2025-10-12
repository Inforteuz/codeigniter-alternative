<?php
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
        $pattern = "tag_{$tag}_*";
        $files = glob(__DIR__ . '/../../writable/cache/' . $pattern . '.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return count($files);
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