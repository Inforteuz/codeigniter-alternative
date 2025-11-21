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
 * @version    1.1.0  [Enhanced for kebab-case URL support]
 * @date       2025-11-21
 */

namespace System;

use System\Core\Env;
use System\Core\DebugToolbar;

/**
 * The Router class handles HTTP requests and routes them to controllers.
 */
class Router
{
    /**
     * @var array $routes - All registered routes
     */
    protected $routes = [];
    protected $middlewares = [];
    protected $prefix = '';

    /**
     * Router constructor - Load routes from app/Routes/Routes.php
     */
    public function __construct()
    {
        Env::load();
        $this->loadAppRoutes();
    }

    /**
     * Load application routes from app/Routes/Routes.php
     *
     * @throws \Exception if the routes file does not exist
     */
    protected function loadAppRoutes()
    {
        $routesFile = dirname(__DIR__) . '/app/Routes/Routes.php';

        if (file_exists($routesFile)) {
            $router = $this;
            require_once $routesFile;

            if (Env::get('DEBUG_MODE') === 'true') {
                DebugToolbar::log("Application routes loaded from: {$routesFile}", 'router');
            }
        } else {
            throw new \Exception("Routes file not found: {$routesFile}");
        }
    }

    /**
     * Add a new route
     */
    public function addRoute($method, $pattern, $controller, $action, $middlewares = [])
    {
        $fullPattern = $this->prefix ? trim($this->prefix, '/') . '/' . trim($pattern, '/') : $pattern;
        $fullPattern = trim($fullPattern, '/');

        $this->routes[$method][$fullPattern] = [
            'controller' => $controller,
            'method' => $action,
            'middlewares' => $middlewares
        ];

        DebugToolbar::log("Route registered: {$method} {$fullPattern} -> {$controller}::{$action}", 'router');
    }

    /**
     * Add multiple routes with same middleware and optional prefix
     */
    public function group($options, $callback)
    {
        $previousMiddlewares = $this->middlewares;
        $previousPrefix = $this->prefix;

        if (isset($options['middleware'])) {
            $this->middlewares = array_merge($this->middlewares, (array)$options['middleware']);
        }

        if (isset($options['prefix']) && is_string($options['prefix'])) {
            $this->prefix = trim($previousPrefix, '/') . '/' . trim($options['prefix'], '/');
            $this->prefix = trim($this->prefix, '/');
        }

        call_user_func($callback, $this);

        $this->middlewares = $previousMiddlewares;
        $this->prefix = $previousPrefix;
    }

    /**
     * Add GET route
     */
    public function get($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('GET', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Add POST route
     */
    public function post($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('POST', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Add PUT route
     */
    public function put($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('PUT', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Add DELETE route
     */
    public function delete($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('DELETE', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Add PATCH route
     */
    public function patch($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('PATCH', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Processes the HTTP request.
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

            $controllerName = $matchedRoute['controller'];
            $action = $matchedRoute['method'];

            DebugToolbar::setRoute(
                $method,
                $url,
                $controllerName,
                $action,
                $matchedRoute['middlewares']
            );

            $this->callControllerMethod($controllerName, $action, $matchedRoute['params'] ?? []);
            DebugToolbar::log("Route matched successfully", 'router');
            return;
        }

        $this->handleDynamicRoute($url);
    }

    /**
     * Finding the right route by URL
     * Enhanced to support kebab-case (e.g., business-plan) → business_plan
     */
    protected function matchRoute($method, $url)
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        $normalizedUrl = str_replace('-', '_', $url);

        foreach ($this->routes[$method] as $pattern => $route) {
            $normalizedPattern = str_replace('-', '_', $pattern);

            if ($normalizedPattern === $normalizedUrl) {
                return $route;
            }

            $patternRegex = preg_replace_callback('/\{([a-zA-Z0-9_\-]+)(?::([^}]+))?\}/', function ($matches) {
                $name = $matches[1];
                $regex = $matches[2] ?? '[^/]+';
                return "(?P<{$name}>{$regex})";
            }, $normalizedPattern);
            $patternRegex = "#^{$patternRegex}$#";

            if (preg_match($patternRegex, $normalizedUrl, $matches)) {
                $routeParams = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $routeParams[$key] = $value;
                    }
                }
                $route['params'] = array_values($routeParams); 
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
                $debugEnabled = class_exists('System\\Core\\Debug') && Env::get('DEBUG_MODE') === 'true';

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
                $this->showError(404, "Method '{$method}' does not exist in controller '{$controllerClass}'.");
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

        $errorConfig = $this->getErrorConfig($code);

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <meta name="generator" content="CodeIgniter Alternative">
            <meta name="description" content="<?= $code ?> - <?= $errorConfig['title'] ?> - CodeIgniter Alternative Framework">
            <link rel="icon" href="favicon.ico" type="image/png">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <title><?= $code ?> - <?= $errorConfig['title'] ?> | CodeIgniter Alternative</title>
            <style>
                :root {
                    --ci-primary: #dd4814;
                    --ci-primary-dark: #bf3c10;
                    --ci-light: #f8f9fa;
                    --ci-dark: #212529;
                    --ci-border: #dee2e6;
                    --ci-bg: #ffffff;
                    --ci-text: #212529;
                    --ci-text-muted: #6c757d;
                    --error-color: <?= $errorConfig['color'] ?>;
                    --error-color-dark: <?= $errorConfig['colorDark'] ?>;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                    background: linear-gradient(135deg, var(--ci-light) 0%, var(--ci-bg) 100%);
                    color: var(--ci-text);
                    line-height: 1.6;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .error-container {
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    animation: fadeInUp 0.8s ease-out;
                }

                .error-content {
                    background: var(--ci-bg);
                    padding: 50px 30px;
                    border-radius: 16px;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                    border: 1px solid var(--ci-border);
                }

                .error-code {
                    font-size: 6rem;
                    font-weight: 800;
                    color: var(--error-color);
                    line-height: 1;
                    margin-bottom: 20px;
                    text-shadow: 4px 4px 0px rgba(0, 0, 0, 0.1);
                    animation: pulse 2s infinite;
                }

                .error-title {
                    font-size: 1.75rem;
                    font-weight: 700;
                    color: var(--ci-dark);
                    margin-bottom: 16px;
                }

                .error-message {
                    font-size: 1.125rem;
                    color: var(--ci-text-muted);
                    margin-bottom: 30px;
                    line-height: 1.6;
                }

                .error-icon {
                    font-size: 4rem;
                    color: var(--error-color);
                    margin-bottom: 20px;
                    animation: bounce 2s infinite;
                }

                .error-actions {
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                    flex-wrap: wrap;
                }

                .btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    padding: 12px 24px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 0.95rem;
                    transition: all 0.3s ease;
                    border: 2px solid transparent;
                }

                .btn-primary {
                    background: var(--error-color);
                    color: white;
                    border-color: var(--error-color);
                }

                .btn-primary:hover {
                    background: var(--error-color-dark);
                    border-color: var(--error-color-dark);
                    transform: translateY(-2px);
                    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
                }

                .btn-secondary {
                    background: transparent;
                    color: var(--ci-text);
                    border-color: var(--ci-border);
                }

                .btn-secondary:hover {
                    background: var(--ci-light);
                    border-color: var(--error-color);
                    transform: translateY(-2px);
                }

                .footer {
                    text-align: center;
                    color: var(--ci-text-muted);
                    margin-top: 30px;
                    font-size: 0.85rem;
                }

                .footer a {
                    color: var(--error-color);
                    text-decoration: none;
                    font-weight: 500;
                }

                .footer a:hover {
                    text-decoration: underline;
                }

                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% {
                        transform: translateY(0);
                    }
                    40% {
                        transform: translateY(-10px);
                    }
                    60% {
                        transform: translateY(-5px);
                    }
                }

                @keyframes pulse {
                    0% {
                        transform: scale(1);
                    }
                    50% {
                        transform: scale(1.05);
                    }
                    100% {
                        transform: scale(1);
                    }
                }

                @media (max-width: 768px) {
                    .error-content {
                        padding: 40px 24px;
                    }

                    .error-code {
                        font-size: 5rem;
                    }

                    .error-title {
                        font-size: 1.5rem;
                    }

                    .error-message {
                        font-size: 1rem;
                    }

                    .error-icon {
                        font-size: 3.5rem;
                    }

                    .error-actions {
                        flex-direction: column;
                        align-items: center;
                    }

                    .btn {
                        width: 100%;
                        max-width: 250px;
                        justify-content: center;
                    }
                }

                @media (max-width: 480px) {
                    .error-code {
                        font-size: 4rem;
                    }

                    .error-title {
                        font-size: 1.25rem;
                    }

                    body {
                        padding: 16px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-content">
                    <div class="error-icon">
                        <i class="fas <?= $errorConfig['icon'] ?>"></i>
                    </div>
                    <div class="error-code"><?= $code ?></div>
                    <h1 class="error-title"><?= $errorConfig['title'] ?></h1>
                    <p class="error-message"><?= $errorConfig['message'] ?></p>

                    <div class="error-actions">
                        <a href="/" class="btn btn-primary">
                            <i class="fas fa-home"></i> Go Home
                        </a>
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Go Back
                        </a>
                        <?php if ($code >= 500): ?>
                        <a href="javascript:location.reload()" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="footer">
                    <p>&copy; <?= date("Y") ?> CodeIgniter Alternative Framework - v2.0.0</p>
                    <p>PHP <?= PHP_VERSION ?> | Server: <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></p>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const errorCode = document.querySelector('.error-code');

                    errorCode.addEventListener('click', function() {
                        this.style.animation = 'none';
                        setTimeout(() => {
                            this.style.animation = 'pulse 2s infinite';
                        }, 10);
                    });

                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            window.history.back();
                        } else if (e.key === 'Home' || e.key === 'h') {
                            window.location.href = '/';
                        }
                        <?php if ($code >= 500): ?>
                        else if (e.key === 'r' || e.key === 'F5') {
                            location.reload();
                        }
                        <?php endif; ?>
                    });

                    console.log('%c<?= $errorConfig['consoleIcon'] ?> <?= $code ?> - <?= $errorConfig['title'] ?>', 'color: <?= $errorConfig['color'] ?>; font-size: 16px; font-weight: bold;');
                    console.log('%c<?= $errorConfig['consoleMessage'] ?>', 'color: #6c757d;');
                });
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Get error configuration based on error code
     */
    private function getErrorConfig($code)
    {
        $configs = [
            400 => [
                'title' => 'Bad Request',
                'message' => 'The server cannot process the request due to a client error. Please check your request and try again.',
                'icon' => 'fa-exclamation-triangle',
                'color' => '#ffc107',
                'colorDark' => '#e0a800',
                'consoleIcon' => '\u26A0',
                'consoleMessage' => 'The server could not understand the request due to invalid syntax.'
            ],
            401 => [
                'title' => 'Unauthorized',
                'message' => 'Authentication is required to access this resource. Please log in and try again.',
                'icon' => 'fa-user-lock',
                'color' => '#ff6b35',
                'colorDark' => '#e55a2b',
                'consoleIcon' => '\uD83D\uDD12',
                'consoleMessage' => 'Authentication required for this resource.'
            ],
            403 => [
                'title' => 'Forbidden',
                'message' => 'You do not have permission to access this resource. Please contact the administrator if you believe this is an error.',
                'icon' => 'fa-ban',
                'color' => '#dc3545',
                'colorDark' => '#c82333',
                'consoleIcon' => '\uD83D\uDEAB',
                'consoleMessage' => 'Access to this resource is forbidden.'
            ],
            404 => [
                'title' => 'Page Not Found',
                'message' => 'The page you\'re looking for doesn\'t exist or has been moved. Please check the URL or navigate back to the homepage.',
                'icon' => 'fa-search',
                'color' => '#6c757d',
                'colorDark' => '#545b62',
                'consoleIcon' => '\uD83D\uDD0D',
                'consoleMessage' => 'The requested URL was not found on this server.'
            ],
            405 => [
                'title' => 'Method Not Allowed',
                'message' => 'The request method is not supported for this resource. Please check the HTTP method and try again.',
                'icon' => 'fa-times-circle',
                'color' => '#fd7e14',
                'colorDark' => '#e56a00',
                'consoleIcon' => '\u274C',
                'consoleMessage' => 'The request method is not allowed for this resource.'
            ],
            500 => [
                'title' => 'Internal Server Error',
                'message' => 'The server encountered an unexpected condition. Our technical team has been notified and is working to resolve the issue.',
                'icon' => 'fa-server',
                'color' => '#dc3545',
                'colorDark' => '#c82333',
                'consoleIcon' => '\uD83D\uDD04',
                'consoleMessage' => 'The server encountered an unexpected condition.'
            ],
            503 => [
                'title' => 'Service Unavailable',
                'message' => 'The server is currently unable to handle the request due to maintenance or capacity problems. Please try again later.',
                'icon' => 'fa-tools',
                'color' => '#17a2b8',
                'colorDark' => '#138496',
                'consoleIcon' => '\uD83D\uDD27',
                'consoleMessage' => 'The server is temporarily unavailable.'
            ]
        ];

        return $configs[$code] ?? [
            'title' => 'Server Error',
            'message' => 'An unexpected error occurred. Please try again later or contact support if the problem persists.',
            'icon' => 'fa-exclamation-circle',
            'color' => '#6c757d',
            'colorDark' => '#545b62',
            'consoleIcon' => '\u2757',
            'consoleMessage' => 'An unexpected server error occurred.'
        ];
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

        $method = preg_replace_callback('/-(.)/', function ($m) {
            return strtoupper($m[1]);
        }, $method);

        $this->callControllerMethod($controller, $method, $params);
    }

    /**
     * Get all registered routes (for debugging)
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
?>
