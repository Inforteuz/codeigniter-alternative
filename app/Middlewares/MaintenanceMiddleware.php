<?php

namespace App\Middlewares;

use System\BaseController;

/**
 * Class MaintenanceMiddleware
 * 
 * Middleware to check if the application is in maintenance mode.
 * Allows only specified IPs to access the site during maintenance.
 */
class MaintenanceMiddleware extends BaseController
{
    /**
     * Handle the maintenance mode check.
     * 
     * @return bool Returns false if maintenance mode is active and the IP is not allowed.
     */
    public function handle()
    {
        $maintenanceMode = env('APP_MAINTENANCE', false);
        
        // If maintenance mode is enabled and client IP is not allowed, deny access
        if ($maintenanceMode && !$this->isAllowedIp()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Handles the failure response when maintenance mode is active.
     * Sends JSON response for API requests or displays an HTML page.
     */
    public function onFailure()
    {
        http_response_code(503); // Service Unavailable
        
        // For JSON requests, return a JSON error message
        if ($this->isJsonRequest()) {
            echo json_encode([
                'error' => 'Maintenance mode',
                'message' => 'The site is temporarily unavailable due to maintenance work.',
                'estimated_recovery_time' => env('MAINTENANCE_ESTIMATED_TIME', '1 hour')
            ]);
            exit();
        }
        
        // For HTML requests, display a maintenance page or fallback message
        $maintenanceView = __DIR__ . '/../Views/errors/maintenance.php';
        if (file_exists($maintenanceView)) {
            include $maintenanceView;
        } else {
            echo "<h1>Maintenance in progress</h1>
                  <p>The site is temporarily unavailable. Please try again later.</p>
                  <p>Estimated recovery time: " . env('MAINTENANCE_ESTIMATED_TIME', '1 hour') . "</p>";
        }
        exit();
    }
    
    /**
     * Check if the client IP is in the list of allowed IPs during maintenance.
     * 
     * @return bool Returns true if IP is allowed, false otherwise.
     */
    private function isAllowedIp()
    {
        // List of IPs allowed during maintenance (e.g. localhost and admin IPs)
        $allowedIps = ['127.0.0.1', '::1', '192.168.1.1']; 
        $clientIp = $_SERVER['REMOTE_ADDR'];
        
        return in_array($clientIp, $allowedIps);
    }
    
    /**
     * Detects if the request expects a JSON response.
     * 
     * @return bool Returns true if request Accept header or Content-Type indicates JSON.
     */
    private function isJsonRequest()
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false ||
               strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
    }
}
?>