<?php

/**
 * Router.php — v1.2.0
 * 
 * Enhanced to auto-normalize REQUEST_URI when Valet serves full path
 * (e.g., /Users/.../writable/uploads/xxx.pdf → writable/uploads/xxx.pdf)
 *
 * @version    1.2.0
 * @date       2025-11-26
 */

namespace System;

use System\Core\Env;
use System\Core\DebugToolbar;
use App\Core\Container;
use App\Core\Middleware\Pipeline;

class Router
{
    protected $routes = [];
    protected $middlewares = [];
    protected $prefix = '';

    public function __construct()
    {
        Env::load();
        $this->loadAppRoutes();
    }

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

    public function get($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('GET', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    public function post($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('POST', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    public function put($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('PUT', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    public function delete($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('DELETE', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    public function patch($pattern, $controller, $action, $middlewares = [])
    {
        return $this->addRoute('PATCH', $pattern, $controller, $action, array_merge($this->middlewares, $middlewares));
    }

    /**
     * Processes the HTTP request
     */
    public function handleRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $url = $_SERVER["REQUEST_URI"];

        $url = $this->normalizeRequestUri($url);

        $urlParts = explode('?', $url);
        $url = trim($urlParts[0], "/");

        if (isset($urlParts[1])) {
            parse_str($urlParts[1], $_GET);
        }

        if ($this->isFileRequest($url)) {
            $this->serveFile($url);
            return;
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
     * Normalize REQUEST_URI: strip base path 
     */
    protected function normalizeRequestUri($uri)
    {
        $path = explode('?', $uri)[0];

        $baseDir = dirname(__DIR__, 1); 

        $baseDir = str_replace('\\', '/', $baseDir);
        $path = str_replace('\\', '/', $path);

        if (strpos($path, $baseDir) === 0) {
            $cleanPath = substr($path, strlen($baseDir));
            if ($cleanPath === '') $cleanPath = '/';
            return $cleanPath;
        }

        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $docRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/'));
            if (strpos($path, $docRoot) === 0) {
                $cleanPath = substr($path, strlen($docRoot));
                return $cleanPath ?: '/';
            }
        }

        return $uri;
    }

    /**
     * Check if the request is likely for a static file (contains '.').
     */
    protected function isFileRequest($url)
    {
        return strpos($url, '.') !== false;
    }

    /**
     * Safely serve static files from allowed directories.
     */
    protected function serveFile($url)
    {
        $baseDir = dirname(__DIR__, 1); 
        $filePath = $baseDir . '/' . ltrim($url, '/');

        $realPath = realpath($filePath);

        if ($realPath === false) {
            $this->showError(404, "File not found: {$url}");
            return;
        }

        $allowedSubDirs = [
            'writable/uploads',
            'public',
        ];

        $isAllowed = false;
        foreach ($allowedSubDirs as $subDir) {
            $allowedRealPath = realpath($baseDir . '/' . $subDir);
            if ($allowedRealPath && (
                $realPath === $allowedRealPath ||
                strpos($realPath, $allowedRealPath . DIRECTORY_SEPARATOR) === 0
            )) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            $this->showError(403, "Access denied: File is outside allowed directories.");
            return;
        }

        if (!is_file($realPath)) {
            $this->showError(404, "Not a file: {$url}");
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $realPath);
        finfo_close($finfo);

        $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimeMap = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'zip' => 'application/zip',
        ];
        if (!$mimeType || $mimeType === 'application/octet-stream') {
            $mimeType = $mimeMap[$ext] ?? 'application/octet-stream';
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($realPath));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');

        readfile($realPath);
        exit();
    }

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
     * Execute middlewares via Pipeline before dispatching controller.
     * Each middleware must implement handle($request, $next)
     */
    protected function executeMiddlewares($middlewares)
    {
        if (empty($middlewares)) {
            return true;
        }

        // Map short names to fully qualified class names
        $pipes = array_map(function ($mw) {
            if (strpos($mw, '\\') === false) {
                return "App\\Middlewares\\{$mw}";
            }
            return $mw;
        }, $middlewares);

        // We use the pipeline to pass a simple boolean request through middleware
        // Middleware classes use the CI4-compatible handle($request, $next) signature
        $result = true;
        try {
            $result = (new Pipeline())
                ->send(true)            // pass a simple truthy payload
                ->through($pipes)
                ->then(fn($req) => $req); // destination: just return the payload
        } catch (\Exception $e) {
            // If any middleware throws or returns falsy via alternative path
            $this->showError(403, $e->getMessage() ?: 'Access denied.');
            return false;
        }

        if (!$result) {
            $this->showError(403, 'Access denied.');
            return false;
        }

        return true;
    }

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
     * Instantiate a controller via the DI Container and call the action method.
     * Container auto-wires constructor dependencies.
     */
    protected function callControllerMethod($controller, $method, $params)
    {
        $controllerClass = "App\\Controllers\\{$controller}";
        $controllerFile  = "app/Controllers/{$controller}.php";

        if (!file_exists($controllerFile)) {
            $this->showError(404, "Controller file '{$controllerFile}' does not exist.");
            return;
        }

        require_once $controllerFile;

        if (!class_exists($controllerClass)) {
            $this->showError(404, "Controller class '{$controllerClass}' does not exist.");
            return;
        }

        if (!method_exists($controllerClass, $method)) {
            $this->showError(404, "Method '{$method}' does not exist in controller '{$controllerClass}'.");
            return;
        }

        $debugEnabled = class_exists('System\\Core\\Debug') && Env::get('DEBUG_MODE') === 'true';

        if ($debugEnabled) {
            $startTime = microtime(true);
        }

        // Use Container for auto-wired instantiation instead of plain `new`
        $container     = Container::getInstance();
        $controllerObj = $container->make($controllerClass);

        call_user_func_array([$controllerObj, $method], $params);

        if ($debugEnabled) {
            $endTime = microtime(true);
            \System\Core\Debug::addQuery(
                "Controller: {$controller}::{$method}",
                $params,
                round(($endTime - $startTime) * 1000, 2)
            );
        }
    }

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

    public function getRoutes()
    {
        return $this->routes;
    }
}
?>
