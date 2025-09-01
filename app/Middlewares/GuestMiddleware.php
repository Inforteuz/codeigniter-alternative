<?php

namespace App\Middlewares;

use System\BaseController;

/**
 * Class GuestMiddleware
 * 
 * Middleware that ensures the user is a guest (not logged in).
 * 
 * If the user is already logged in, access will be denied and optionally redirected.
 * 
 * @package App\Middlewares
 */
class GuestMiddleware extends BaseController
{
    /**
     * Handle the guest check.
     * 
     * Returns true if the user is NOT logged in, false otherwise.
     * 
     * @return bool True if user is a guest; false if logged in.
     */
    public function handle()
    {
        return !(isset($_SESSION['logged_in']) && $_SESSION['logged_in']);
    }
    
    /**
     * Define where to redirect logged-in users trying to access guest-only pages.
     * 
     * @return string URL to redirect to.
     */
    public function redirectTo()
    {
        return '/user/dashboard';
    }
    
    /**
     * Action to perform on failure (when a logged-in user tries to access guest pages).
     * 
     * Sets an informational flash message to notify the user.
     * 
     * @return void
     */
    public function onFailure()
    {
        $this->setFlash('info', 'Siz allaqachon tizimga kirgansiz');
    }
}

?>