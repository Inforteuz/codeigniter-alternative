<?php

namespace App\Middlewares;

use System\BaseController;

/**
 * Class AuthMiddleware
 * 
 * Middleware for authentication checking.
 * Ensures that the user session is valid before allowing access.
 * Extends BaseController to utilize session or controller utilities if any.
 * 
 * @package App\Middlewares
 */
class AuthMiddleware extends BaseController
{
    /**
     * Handle the middleware logic.
     * 
     * Checks if the current session is valid.
     * 
     * @return bool Returns true if session is valid, otherwise false.
     */
    public function handle()
    {
        if ($this->isSessionValid()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get the URL to redirect unauthorized users to.
     * 
     * @return string The redirect path (default is root '/').
     */
    public function redirectTo()
    {
        return '/';
    }
    
    /**
     * Validate the user session.
     * 
     * Checks that session keys exist and are valid:
     *  - 'logged_in' flag must be true
     *  - 'user_id' and 'username' must be set
     *  - session lifetime must not exceed 8 hours
     * 
     * @return bool True if session is valid, false otherwise.
     */
    private function isSessionValid()
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
            return false;
        }
        
        // Define session lifetime (8 hours in seconds)
        $sessionLifetime = 8 * 60 * 60;
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $sessionLifetime)) {
            return false;
        }
        
        return true;
    }
}

?>