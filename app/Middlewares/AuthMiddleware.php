<?php

namespace App\Middlewares;

use App\Core\Auth\Auth;

class AuthMiddleware
{
    public function handle($request, $next)
    {
        if (Auth::check()) {
            return $next($request);
        }
        
        $this->redirectTo();
        return false;
    }
    
    public function redirectTo()
    {
        header("Location: /");
        exit();
    }
}
?>