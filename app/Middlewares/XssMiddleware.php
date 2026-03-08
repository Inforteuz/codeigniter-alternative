<?php

namespace App\Middlewares;

use App\Core\Security\Security;

/**
 * Class XssMiddleware
 * 
 * Automatically sanitizes incoming request data globally to prevent Cross-Site Scripting.
 */
class XssMiddleware
{
    public function handle($request, $next)
    {
        // Sanitize global input variables
        if (!empty($_GET)) {
            $_GET = Security::xssClean($_GET);
        }
        
        if (!empty($_POST)) {
            $_POST = Security::xssClean($_POST);
        }
        
        if (!empty($_COOKIE)) {
            $_COOKIE = Security::xssClean($_COOKIE);
        }
        
        if (!empty($_REQUEST)) {
            $_REQUEST = Security::xssClean($_REQUEST);
        }

        // Proceed to next middleware/controller
        return $next($request);
    }
}
