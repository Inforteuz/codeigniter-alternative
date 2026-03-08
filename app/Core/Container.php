<?php

namespace App\Core;

use ReflectionClass;
use ReflectionException;
use Exception;

/**
 * Service Container
 * 
 * A simple Dependency Injection Container with auto-wiring capabilities.
 * 
 * @package CodeIgniter Alternative
 */
class Container
{
    /**
     * @var Container|null Singleton instance
     */
    private static $instance = null;

    /**
     * Registered bindings
     * @var array
     */
    protected $bindings = [];

    /**
     * Resolved singleton instances
     * @var array
     */
    protected $instances = [];

    /**
     * Prevent direct instantiation
     */
    protected function __construct() {}

    /**
     * Get the singleton instance of the Container
     * 
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a binding in the container
     * 
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    /**
     * Register a shared binding (singleton)
     * 
     * @param string $abstract
     * @param \Closure|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a singleton
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve the given type from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Return existing instance if it's a singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if bound
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $isShared = $this->bindings[$abstract]['shared'] ?? false;

        // If it's a closure, execute it
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            // Auto-wire the class
            $object = $this->build($concrete, $parameters);
        }

        // Cache if shared
        if ($isShared) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance of the given type, resolving dependencies automatically.
     * 
     * @param string $concrete
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    protected function build(string $concrete, array $parameters = [])
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete;
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve all dependencies for a method/constructor
     * 
     * @param \ReflectionParameter[] $dependencies
     * @param array $parameters
     * @return array
     * @throws Exception
     */
    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $resolved = [];

        foreach ($dependencies as $dependency) {
            $name = $dependency->getName();
            $type = $dependency->getType();

            // Use provided parameter if available
            if (array_key_exists($name, $parameters)) {
                $resolved[] = $parameters[$name];
                continue;
            }

            // If it's a typed hint (class), resolve it from container
            if ($type !== null && !$type->isBuiltin()) {
                $resolved[] = $this->make($type->getName());
                continue;
            }

            // Check for default value
            if ($dependency->isDefaultValueAvailable()) {
                $resolved[] = $dependency->getDefaultValue();
                continue;
            }

            throw new Exception("Unresolvable dependency resolving [$name]");
        }

        return $resolved;
    }

    /**
     * Call a method and inject its dependencies automatically.
     * 
     * @param callable|array $callback
     * @param array $parameters
     * @return mixed
     * @throws Exception
     */
    public function call($callback, array $parameters = [])
    {
        if (is_array($callback)) {
            $reflector = new \ReflectionMethod($callback[0], $callback[1]);
        } elseif (is_string($callback) && strpos($callback, '::') !== false) {
            list($class, $method) = explode('::', $callback);
            $reflector = new \ReflectionMethod($class, $method);
            $callback = [$this->make($class), $method];
        } else {
            $reflector = new \ReflectionFunction($callback);
        }

        $dependencies = $this->resolveDependencies($reflector->getParameters(), $parameters);

        if (is_array($callback)) {
            return $reflector->invokeArgs($callback[0], $dependencies);
        }

        return $reflector->invokeArgs($dependencies);
    }
}
