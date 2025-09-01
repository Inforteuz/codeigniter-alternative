<?php

/**
 * Router.php
 *
 * This file is responsible for handling URL routing and directing HTTP requests
 * to the appropriate controller and method. The Router class is a core component
 * of your custom MVC framework.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    1.0.0
 * @date       2024-12-01
 */

namespace System;

use System\Core\Env;

/**
 * The Router class handles HTTPS requests.
 */
class Router
{
    /**
     * @var array $routes - All registered routes
     */
    protected $routes = [];
    protected $middlewares = [];

    /**
     * Router constructor - adding basic routes
     */
    public function __construct()
{
    // Load environment variables from .env file
    Env::load();

    /**
     * ------------------------------------------------
     * Define your application routes below
     * Use $this->addRoute(method, uri, controller, action, [middlewares])
     * ------------------------------------------------
     */

    // Public (Guest) routes
    $this->addRoute('GET', '', 'HomeController', 'index');
    $this->addRoute('GET', 'login', 'HomeController', 'index');
    $this->addRoute('POST', 'login', 'HomeController', 'login');

    // Logout route (optional middleware can be added)
    $this->addRoute('GET', 'logout', 'HomeController', 'logout');
    $this->addRoute('POST', 'logout', 'HomeController', 'logout');

    // Dashboard route (typically protected by AuthMiddleware)
    $this->addRoute('GET', 'dashboard', 'DashboardController', 'index');

    // Example API routes
    $this->addRoute('GET', 'api/user', 'ApiController', 'getUser');
    $this->addRoute('POST', 'api/user', 'ApiController', 'updateUser');

    // Static page routes
    $this->addRoute('GET', 'about', 'PageController', 'about');
    $this->addRoute('GET', 'contact', 'PageController', 'contact');
    $this->addRoute('POST', 'contact', 'PageController', 'sendMessage');

    // Profile routes
    $this->addRoute('GET', 'profile', 'ProfileController', 'index');
    $this->addRoute('POST', 'profile', 'ProfileController', 'update');

    // Error pages
    $this->addRoute('GET', 'error/403', 'ErrorController', 'forbidden');
    $this->addRoute('GET', 'error/404', 'ErrorController', 'notFound');
    $this->addRoute('GET', 'error/500', 'ErrorController', 'serverError');

    // Optional: Add custom route for language switching
    $this->addRoute('GET', 'change-language/{lang}', 'LanguageController', 'change');

    /**
     * ------------------------------
     * Add your own routes below
     * ------------------------------
     * You can add middlewares to routes as needed:
     * Example:
     * $this->addRoute('GET', 'admin', 'AdminController', 'dashboard', ['AuthMiddleware']);
     */
    }

    /**
     * Add a new route
     */
    public function addRoute($method, $pattern, $controller, $action, $middlewares = [])
    {
        $this->routes[$method][$pattern] = [
            'controller' => $controller,
            'method' => $action,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Processes the HTTPS request.
     */
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $_SERVER["REQUEST_URI"];
        
        $urlParts = explode('?', $url);
        $url = trim($urlParts[0], "/");
        
        if (isset($urlParts[1])) {
            parse_str($urlParts[1], $_GET);
        }

        $matchedRoute = $this->matchRoute($method, $url);
        
        if ($matchedRoute) {
            if (!$this->executeMiddlewares($matchedRoute['middlewares'])) {
                return;
            }
            
            $this->callControllerMethod($matchedRoute['controller'], $matchedRoute['method'], $matchedRoute['params'] ?? []);
            return;
        }

        $this->handleDynamicRoute($url);
    }

    /**
     * Finding the right route by URL
     */
    protected function matchRoute($method, $url)
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            if ($pattern === $url) {
                return $route;
            }
            
            $patternRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
            $patternRegex = "#^{$patternRegex}$#";
            
            if (preg_match($patternRegex, $url, $matches)) {
                $route['params'] = array_slice($matches, 1);
                return $route;
            }
        }
        
        return null;
    }

    /**
     * Implementing middleware
     */
    protected function executeMiddlewares($middlewares)
    {
        foreach ($middlewares as $middleware) {
            $middlewareClass = "App\\Middlewares\\" . $middleware;
            
            if (!class_exists($middlewareClass)) {
                $this->showError(500, "Middleware class '{$middlewareClass}' does not exist.");
                return false;
            }
            
            $middlewareObj = new $middlewareClass();
            
            if (!method_exists($middlewareObj, 'handle')) {
                $this->showError(500, "Middleware '{$middleware}' does not have handle method.");
                return false;
            }
            
            if (!$middlewareObj->handle()) {
                $this->handleMiddlewareFailure($middlewareObj);
                return false;
            }
        }
        
        return true;
    }

    /**
     * Action to take when middleware fails
     */
    protected function handleMiddlewareFailure($middleware)
    {
        if (method_exists($middleware, 'onFailure')) {
            $middleware->onFailure();
            return;
        }
        
        if (method_exists($middleware, 'redirectTo')) {
            $redirectUrl = $middleware->redirectTo();
            header("Location: {$redirectUrl}");
            exit();
        }
        
        $this->showError(403, "Access denied.");
    }

    /**
     * Controller and method call
     */
    protected function callControllerMethod($controller, $method, $params)
    {
        $controllerClass = "App\\Controllers\\" . $controller;

        $controllerFile = "app/Controllers/{$controller}.php";

        if (!file_exists($controllerFile)) {
            $this->showError(404, "Controller file '{$controllerFile}' does not exist.");
            return;
        }

        require_once $controllerFile;

        if (class_exists($controllerClass)) {
            $controllerObj = new $controllerClass();

            if (method_exists($controllerObj, $method)) {
                $debugEnabled = class_exists('System\\Core\\Debug') && Env::get('APP_DEBUG') === 'true';
                
                if ($debugEnabled) {
                    $startTime = microtime(true);
                }
                
                call_user_func_array([$controllerObj, $method], $params);
                
                if ($debugEnabled) {
                    $endTime = microtime(true);
                    \System\Core\Debug::addQuery(
                        "Controller: {$controller}::{$method}",
                        $params,
                        round(($endTime - $startTime) * 1000, 2)
                    );
                }
            } else {
                $this->showError(404, "Method '{$method}' does not exist.");
            }
        } else {
            $this->showError(404, "Controller class '{$controllerClass}' does not exist.");
        }
    }

    /**
     * Writes errors to a log file.
     */
    protected function logError($message)
    {
        $logDir = __DIR__ . '/../writable/logs';
        date_default_timezone_set("Asia/Tashkent");
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/error_' . date('Y-m-d') . '.log';
        $dateTime = date('Y-m-d H:i:s');
        $logMessage = "[{$dateTime}] ERROR: {$message}\n";
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * It displays the error and writes it to the log.
     */
    private function showError($code, $message)
    {
        http_response_code($code);

        $errorFile = __DIR__ . "/../app/Views/errors/{$code}.php";
        
        if (file_exists($errorFile)) {
            include($errorFile);
            
            $this->logError("{$code} {$message}");
            return;
        }

        $this->logError("{$code} {$message}");
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <link rel='icon' href='favicon.ico' type='image/png'>
            <title>{$code} - Xatolik</title>
            <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css'>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background-color: #f5f5f5;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    color: #333;
                }

                .error-container {
                    background-color: #fff;
                    padding: 30px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 420px;
                    width: 100%;
                }

                .error-container h1 {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 10px;
                }

                .error-container h2 {
                    font-size: 20px;
                    color: #555;
                    margin-bottom: 15px;
                }

                .error-container p {
                    font-size: 14px;
                    color: #777;
                    margin-bottom: 20px;
                }

                .error-container .button {
                    text-decoration: none;
                    background-color: #3498db;
                    color: #fff;
                    font-size: 14px;
                    padding: 8px 18px;
                    border-radius: 5px;
                    display: inline-block;
                    transition: background-color 0.3s ease;
                }

                .error-container .button:hover {
                    background-color: #2980b9;
                }

                .error-container .icon {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 15px;
                }

                @media (max-width: 600px) {
                    .error-container h1 {
                        font-size: 50px;
                    }

                    .error-container h2 {
                        font-size: 18px;
                    }

                    .error-container p {
                        font-size: 12px;
                    }

                    .error-container .button {
                        padding: 6px 12px;
                        font-size: 12px;
                    }

                    .error-container .icon {
                        font-size: 50px;
                    }
                }

            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='icon'>
                    <i class='fas fa-exclamation-triangle'></i>
                </div>
                <h1>{$code}</h1>
                <h2>Sorry, the request could not be processed.</h2>
                <p>This page does not exist or the request was made incorrectly.</p>
                <a href='/' class='button'>Return to home page</a>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Fallback to dynamic routing (controller/method/params)
     */
    protected function handleDynamicRoute($url)
    {
        $url = $url ? $url : "home/index";
        $segments = explode("/", $url);

        $controller = ucfirst($segments[0]) . "Controller";
        $method = $segments[1] ?? "index";
        $params = array_slice($segments, 2);

        $this->callControllerMethod($controller, $method, $params);
    }
}
?>