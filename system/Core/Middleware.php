<?php
namespace System\Core;

/**
 * Middleware Class
 * 
 * This class is responsible for managing middleware execution in the application.
 * It handles running multiple middleware classes before accessing a controller method,
 * allowing pre-processing or filtering of requests (e.g., authentication, authorization).
 * 
 * Features:
 *  - Stores the current controller, method, and parameters.
 *  - Executes an array of middleware classes by calling their `handle()` method.
 *  - Supports setting and getting flash messages stored in the session.
 *  - Checks user roles and authentication status.
 * 
 * @package    CodeIgniter Alternative
 * @subpackage System\Core
 * @version    1.0.0
 * @date       2024-12-01
 * 
 * @methods
 * - `execute($middlewares)`: Runs each middleware and stops execution if any middleware returns false.
 * - `setFlash($type, $message)`: Stores a flash message in the session.
 * - `getFlash($type)`: Retrieves and removes a flash message from the session.
 * - `hasRole($requiredRole)`: Checks if the current user has the required role or roles.
 * - `isAuthenticated()`: Checks if the user is logged in/authenticated.
 * 
 * @example
 * ```php
 * $middleware = new Middleware($controller, $method, $params);
 * $result = $middleware->execute(['AuthMiddleware', 'RoleMiddleware']);
 * if (!$result) {
 *     // Middleware blocked the request
 * }
 * ```
 */
class Middleware
{
    protected $controller;
    protected $method;
    protected $params;

    public function __construct($controller, $method, $params)
    {
        $this->controller = $controller;
        $this->method = $method;
        $this->params = $params;
    }

    public function execute($middlewares = [])
    {
        foreach ($middlewares as $middleware) {
            $middlewareClass = "App\\Middlewares\\" . $middleware;
            if (class_exists($middlewareClass)) {
                $middlewareObj = new $middlewareClass();
                if (!$middlewareObj->handle()) {
                    return false; // Middleware stopped the request
                }
            }
        }
        return true;
    }
    
    /**
     * Set a flash message in session
     */
    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        
        $_SESSION['flash_messages'][$type] = $message;
    }
    
    /**
     * Get and remove a flash message from session
     */
    protected function getFlash($type)
    {
        if (isset($_SESSION['flash_messages'][$type])) {
            $message = $_SESSION['flash_messages'][$type];
            unset($_SESSION['flash_messages'][$type]);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if the current user has the required role(s)
     */
    protected function hasRole($requiredRole)
    {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        if (is_array($requiredRole)) {
            return in_array($_SESSION['role'], $requiredRole);
        }
        
        return $_SESSION['role'] === $requiredRole;
    }
    
    /**
     * Check if the user is authenticated (logged in)
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    }
}
?>