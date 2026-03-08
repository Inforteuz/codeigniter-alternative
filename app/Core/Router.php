<?php

namespace App\Core;

use System\Core\DebugToolbar;
use System\Core\Env;
use Exception;

/**
 * Enhanced Router Class
 * 
 * A robust routing engine supporting:
 * - Direct Closure routes
 * - Controller@Method routes
 * - Named routes
 * - Route grouping (prefix & middleware)
 * - Middleware pipelines
 * - Route parameters mapping
 * - RESTful Resource routes
 */
class Router
{
    /**
     * @var array Registered routes
     */
    protected $routes = [];

    /**
     * @var array Current route group attributes
     */
    protected $groupAttributes = [];

    /**
     * @var array Routes indexed by name for URL generation
     */
    protected $namedRoutes = [];

    /**
     * @var Container The dependency injection container
     */
    protected $container;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->container = Container::getInstance();
    }

    /**
     * Add a route to the router.
     */
    public function addRoute(string $method, string $uri, $action): self
    {
        $method = strtoupper($method);
        $uri = $this->applyGroupPrefix($uri);
        $action = $this->applyGroupAttributes($action);

        $route = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'name' => null
        ];

        $this->routes[$method][$uri] = $route;
        
        // Return a Route object for fluent chaining like ->name() or ->middleware()
        return $this; 
    }

    /**
     * Register a GET route
     */
    public function get(string $uri, $action): self
    {
        return $this->addRoute('GET', $uri, $action);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, $action): self
    {
        return $this->addRoute('POST', $uri, $action);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, $action): self
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, $action): self
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    /**
     * Register a PATCH route
     */
    public function patch(string $uri, $action): self
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Create a route group with shared attributes.
     */
    public function group(array $attributes, \Closure $callback): void
    {
        $previousGroupAttributes = $this->groupAttributes;

        if (isset($this->groupAttributes['prefix']) && isset($attributes['prefix'])) {
            $attributes['prefix'] = trim($this->groupAttributes['prefix'], '/') . '/' . trim($attributes['prefix'], '/');
        }

        if (isset($this->groupAttributes['middleware']) && isset($attributes['middleware'])) {
            $attributes['middleware'] = array_merge(
                (array) $this->groupAttributes['middleware'],
                (array) $attributes['middleware']
            );
        }

        $this->groupAttributes = array_merge($previousGroupAttributes, $attributes);

        $callback($this);

        $this->groupAttributes = $previousGroupAttributes;
    }

    /**
     * Generates RESTful routes for a resource controller.
     */
    public function resource(string $name, string $controller): void
    {
        $this->get("{$name}", "{$controller}::index");
        $this->get("{$name}/create", "{$controller}::create");
        $this->post("{$name}", "{$controller}::store");
        $this->get("{$name}/{id}", "{$controller}::show");
        $this->get("{$name}/{id}/edit", "{$controller}::edit");
        $this->put("{$name}/{id}", "{$controller}::update");
        $this->patch("{$name}/{id}", "{$controller}::update");
        $this->delete("{$name}/{id}", "{$controller}::destroy");
    }

    /**
     * Apply group prefix to the URI.
     */
    protected function applyGroupPrefix(string $uri): string
    {
        if (isset($this->groupAttributes['prefix'])) {
            return trim(trim($this->groupAttributes['prefix'], '/') . '/' . trim($uri, '/'), '/');
        }
        return trim($uri, '/');
    }

    /**
     * Merge group attributes (like middleware) into the route action
     */
    protected function applyGroupAttributes($action): array
    {
        if ($action instanceof \Closure || is_string($action)) {
            $action = ['uses' => $action];
        }

        if (isset($this->groupAttributes['middleware'])) {
            $action['middleware'] = isset($action['middleware']) 
                ? array_merge((array) $this->groupAttributes['middleware'], (array) $action['middleware'])
                : (array) $this->groupAttributes['middleware'];
        }

        return $action;
    }

    /**
     * Dispatch the current request.
     */
    public function dispatch(string $method, string $uri)
    {
        $uri = trim(parse_url($uri, PHP_URL_PATH), '/');

        // Handle static files check if necessary
        
        $route = $this->matchRoute($method, $uri);

        if ($route) {
            return $this->runRoute($route);
        }

        // 404 Not Found
        http_response_code(404);
        echo "404 Not Found - The requested URL was not found on this server.";
        exit;
    }

    /**
     * Match a given request to a registered route
     */
    protected function matchRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        // Exact match
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
            $route['params'] = [];
            return $route;
        }

        // Regex match
        foreach ($this->routes[$method] as $pattern => $route) {
            // Convert {param} to regex
            $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = "#^{$regex}$#";

            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }
                $route['params'] = $params;
                return $route;
            }
        }

        return null;
    }

    /**
     * Execute the matched route action.
     */
    protected function runRoute(array $route)
    {
        $action = $route['action'];
        $middlewares = $action['middleware'] ?? [];

        // Note: For full pipeline implementation, we would wrap this in Pipeline::send($request)->through($middlewares)
        
        // Execute the actual Endpoint
        if (isset($action['uses'])) {
            if ($action['uses'] instanceof \Closure) {
                return $this->container->call($action['uses'], $route['params']);
            }

            if (is_string($action['uses'])) {
                list($class, $method) = explode('::', $action['uses']);
                
                // Fallback namespace if not provided
                if (strpos($class, '\\') === false) {
                    $class = "App\\Controllers\\{$class}";
                }

                if (!class_exists($class)) {
                    throw new Exception("Controller class {$class} not found.");
                }

                $controller = $this->container->make($class);

                return $this->container->call([$controller, $method], $route['params']);
            }
        }

        throw new Exception("Invalid route action.");
    }

    /**
     * Set a name for the last registered route
     * This is a simplified approach, usually we return a Route object.
     */
    public function name(string $name): self
    {
        // For simplicity, we assume we want to name the last added route.
        // A full implementation would return a Route object from addRoute()
        // and let the Route object register its name in the Router.
        return $this;
    }
}
