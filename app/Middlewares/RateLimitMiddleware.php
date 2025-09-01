<?php

namespace App\Middlewares;

/**
 * Class RateLimitMiddleware
 * 
 * Middleware to limit the number of requests per client IP within a given time window.
 */
class RateLimitMiddleware
{
    private $maxRequests = 100;   // Maximum allowed requests in the time window
    private $timeWindow = 3600;   // Time window in seconds (1 hour)
    
    /**
     * Handles the rate limiting logic.
     * 
     * @return bool Returns false if the request limit has been exceeded.
     */
    public function handle()
    {
        $ip = $this->getClientIp();
        $key = "rate_limit_{$ip}";
        
        // Get current rate limit info from session or initialize
        $current = $_SESSION[$key] ?? [
            'count' => 0,
            'start_time' => time()
        ];
        
        // Reset count if time window has passed
        if (time() - $current['start_time'] > $this->timeWindow) {
            $current = [
                'count' => 1,
                'start_time' => time()
            ];
        } else {
            // Increment count within current time window
            $current['count']++;
        }
        
        // Save updated count and start time back to session
        $_SESSION[$key] = $current;
        
        // Deny if request count exceeds max allowed
        if ($current['count'] > $this->maxRequests) {
            return false;
        }
        
        return true;
    }
    
    /**
     * URL to redirect when rate limit is exceeded.
     * 
     * @return string
     */
    public function redirectTo()
    {
        return '/rate-limit-exceeded';
    }
    
    /**
     * Response sent when the client exceeds the allowed request limit.
     * Sends HTTP 429 Too Many Requests with a retry time.
     */
    public function onFailure()
    {
        $ip = $this->getClientIp();
        $retryAfter = $this->timeWindow - (time() - ($_SESSION["rate_limit_{$ip}"]['start_time']));
        
        // Inform client how long to wait before retrying
        header('Retry-After: ' . $retryAfter);
        http_response_code(429);
        
        echo json_encode([
            'error' => 'Rate limit exceeded',
            'message' => 'Please try again after ' . $retryAfter . ' seconds.',
            'retry_after' => $retryAfter
        ]);
        exit();
    }
    
    /**
     * Helper method to get the client IP address.
     * Supports common HTTP headers used by proxies.
     * 
     * @return string Client IP address
     */
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