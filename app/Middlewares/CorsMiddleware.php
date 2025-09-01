<?php

namespace App\Middlewares;

/**
 * Class CorsMiddleware
 * 
 * Middleware to handle Cross-Origin Resource Sharing (CORS).
 * Sets the necessary headers to allow or restrict requests from different origins.
 * Also handles preflight OPTIONS requests.
 * 
 * @package App\Middlewares
 */
class CorsMiddleware
{
    /**
     * Handle the CORS logic.
     * 
     * Sends CORS headers and handles OPTIONS preflight requests by
     * responding with HTTP 200 status and exiting.
     * 
     * @return bool Returns true if request is allowed to proceed.
     */
    public function handle()
    {
        header('Access-Control-Allow-Origin: ' . $this->getAllowedOrigins());
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        return true;
    }
    
    /**
     * Get allowed origin for CORS.
     * 
     * Checks the incoming request's Origin header against a whitelist.
     * If the origin is allowed, it returns that origin; otherwise, returns the first allowed origin.
     * 
     * @return string The allowed origin URL.
     */
    private function getAllowedOrigins()
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'https://yourdomain.com'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }
        
        // Default to first allowed origin if incoming origin is not whitelisted
        return $allowedOrigins[0];
    }
}
?>