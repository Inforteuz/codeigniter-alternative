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
use System\Http\Request;
use System\Error\ErrorRenderer;
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
        $cacheFile = dirname(__DIR__) . '/writable/cache/routes_cache.php';
        $routesFile = dirname(__DIR__) . '/app/Routes/Routes.php';

        // Load from cache in production if enabled
        if (Env::get('APP_ENV') === 'production' && Env::get('ROUTE_CACHE_ENABLED') === 'true' && file_exists($cacheFile)) {
            $this->routes = require $cacheFile;
            return;
        }

        if (file_exists($routesFile)) {
            $router = $this;
            require_once $routesFile;

            // Save to cache if enabled in production
            if (Env::get('APP_ENV') === 'production' && Env::get('ROUTE_CACHE_ENABLED') === 'true') {
                if (!is_dir(dirname($cacheFile))) {
                    mkdir(dirname($cacheFile), 0777, true);
                }
                file_put_contents($cacheFile, '<?php return ' . var_export($this->routes, true) . ';');
            }

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
        $request = Request::fromGlobals();
        $method = $request->method();
        $url = $request->uri();

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

        // Global CORS preflight handling (even when no route/middleware matches)
        if ($method === 'OPTIONS') {
            $this->handlePreflight($request, $url);
            return;
        }

        $matchedRoute = $this->matchRoute($method, $url);

        if ($matchedRoute) {
            if (!$this->executeMiddlewares($matchedRoute['middlewares'], $request)) {
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

        // If the path exists for other HTTP methods, return 405 instead of falling back
        $allowed = $this->findAllowedMethods($url);
        if (!empty($allowed)) {
            header('Allow: ' . implode(', ', $allowed));
            $this->showError(405, 'Method Not Allowed');
            return;
        }

        $this->handleDynamicRoute($url);
    }

    /**
     * Handle OPTIONS preflight requests.
     */
    protected function handlePreflight(Request $request, string $url): void
    {
        $allowed = $this->findAllowedMethods($url);
        if (empty($allowed)) {
            // If no explicit route matches, still allow common methods to reduce friction for APIs
            $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        } else {
            if (!in_array('OPTIONS', $allowed, true)) {
                $allowed[] = 'OPTIONS';
            }
        }

        $origin = (string) ($request->header('Origin', $_SERVER['HTTP_ORIGIN'] ?? '') ?: '');
        $allowOrigin = $this->resolveCorsOrigin($origin);
        if ($allowOrigin !== '') {
            header('Access-Control-Allow-Origin: ' . $allowOrigin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $allowed));
        header('Access-Control-Allow-Headers: ' . $this->resolveCorsAllowHeaders($request));
        header('Access-Control-Max-Age: 600');
        http_response_code(204);
        exit();
    }

    protected function resolveCorsOrigin(string $origin): string
    {
        $defaultAllowed = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'https://yourdomain.com',
        ];

        $env = Env::get('CORS_ALLOWED_ORIGINS', '');
        $allowed = $defaultAllowed;
        if (is_string($env) && trim($env) !== '') {
            $allowed = array_values(array_filter(array_map('trim', explode(',', $env))));
        }

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            return $origin;
        }

        return $allowed[0] ?? '';
    }

    protected function resolveCorsAllowHeaders(Request $request): string
    {
        $requested = (string) ($request->header('Access-Control-Request-Headers', '') ?: '');
        if ($requested !== '') {
            return $requested;
        }
        return 'Content-Type, Authorization, X-Requested-With';
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

        $path = str_replace('/index.php', '', $path);
        return $path ?: '/';
    }

    /**
     * Check if the request is likely for a static file (contains '.').
     */
    protected function isFileRequest($url)
    {
        if (strpos($url, '.') === false) {
            return false;
        }

        $extension = pathinfo($url, PATHINFO_EXTENSION);
        $excludedExtensions = ['php', 'phtml', 'php7', 'php8'];
        $excludedFiles = ['index.php'];

        if (in_array($extension, $excludedExtensions) || in_array($url, $excludedFiles)) {
            return false;
        }

        return true;
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
     * Find which HTTP methods match a given URL pattern.
     * Used to return 405 Method Not Allowed.
     *
     * @param string $url Normalized URL without leading slash
     * @return string[]
     */
    protected function findAllowedMethods(string $url): array
    {
        $normalizedUrl = str_replace('-', '_', $url);
        $allowed = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $pattern => $_route) {
                $normalizedPattern = str_replace('-', '_', $pattern);

                if ($normalizedPattern === $normalizedUrl) {
                    $allowed[] = $method;
                    continue;
                }

                $patternRegex = preg_replace_callback('/\{([a-zA-Z0-9_\-]+)(?::([^}]+))?\}/', function ($matches) {
                    $name = $matches[1];
                    $regex = $matches[2] ?? '[^/]+';
                    return "(?P<{$name}>{$regex})";
                }, $normalizedPattern);
                $patternRegex = "#^{$patternRegex}$#";

                if (preg_match($patternRegex, $normalizedUrl)) {
                    $allowed[] = $method;
                }
            }
        }

        $allowed = array_values(array_unique($allowed));
        sort($allowed);
        return $allowed;
    }

    /**
     * Execute middlewares via Pipeline before dispatching controller.
     * Each middleware must implement handle($request, $next)
     */
    protected function executeMiddlewares($middlewares, $request = true)
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
                ->send($request)
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
        $this->logError("{$code} {$message}");
        (new ErrorRenderer())->render((int) $code, (string) $message);
        return;
    }

    // Error page rendering moved to System\Error\ErrorRenderer

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
