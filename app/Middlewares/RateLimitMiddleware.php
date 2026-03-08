<?php

namespace App\Middlewares;

use System\Cache\CacheHelper;

/**
 * Class RateLimitMiddleware
 * 
 * Middleware to limit the number of requests per client IP within a given time window.
 * Uses the native Cache system for high performance.
 */
class RateLimitMiddleware
{
    private $maxRequests = 100;   // Maximum allowed requests
    private $timeWindow = 3600;   // Time window in seconds (1 hour)
    
    public function handle($request, $next)
    {
        $ip = $this->getClientIp();
        $key = "rate_limit_{$ip}";
        
        // CacheHelper::rateLimit returns false if exceeded, otherwise remaining attempts
        $remaining = CacheHelper::rateLimit($key, $this->maxRequests, $this->timeWindow);

        if ($remaining === false) {
            $this->onFailure();
            return false;
        }
        
        return $next($request);
    }
    
    public function redirectTo()
    {
        return '/rate-limit-exceeded';
    }
    
    public function onFailure()
    {
        // Simple fallback retry time: 1 hour. Cache doesn't easily expose exact ttl remaining without manual query.
        $retryAfter = $this->timeWindow;
        
        header('Retry-After: ' . $retryAfter);
        http_response_code(429);
        
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => 'Please try again later.',
            'retry_after' => $retryAfter
        ]);
        exit();
    }
    
    private function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
?>