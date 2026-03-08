<?php

namespace App\Core\Middleware;

use App\Core\Container;
use Closure;

/**
 * Middleware Pipeline
 * 
 * An "Onion" architecture for middleware execution.
 * Allows passing a request through a series of middleware closures or classes.
 */
class Pipeline
{
    /**
     * @var Container The container instance.
     */
    protected $container;

    /**
     * @var mixed The object being passed through the pipeline (usually the Request).
     */
    protected $passable;

    /**
     * @var array The array of class pipes (middlewares).
     */
    protected $pipes = [];

    /**
     * Create a new Pipeline instance.
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?: Container::getInstance();
    }

    /**
     * Set the object being sent through the pipeline.
     */
    public function send($passable): self
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     */
    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * Get the final piece of the Closure onion.
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            return $destination($passable);
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_callable($pipe)) {
                    // Call the closure directly
                    return $pipe($passable, $stack);
                } elseif (!is_object($pipe)) {
                    // Parse string to class and resolve via container
                    list($name, $parameters) = $this->parsePipeString($pipe);
                    
                    // Fallback to App\Middlewares if no namespace provided
                    if (strpos($name, '\\') === false) {
                        $name = "App\\Middlewares\\{$name}";
                    }
                    
                    $pipe = $this->container->make($name);
                    
                    // Construct parameters if necessary
                    $parameters = array_merge([$passable, $stack], $parameters);
                } else {
                    $parameters = [$passable, $stack];
                }

                // Assume handle() is the method on the middleware class
                $method = 'handle';

                if (method_exists($pipe, $method)) {
                    return $pipe->{$method}(...$parameters);
                }
                
                // Fallback for older middleware that might not take $next
                // E.g current legacy Middleware system
                if (method_exists($pipe, 'execute')) {
                     // This is mostly pseudo-behavior if legacy doesn't pass next
                     return $pipe->execute();
                }

                throw new \Exception(sprintf('Method %s does not exist on pipe %s.', $method, get_class($pipe)));
            };
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     */
    protected function parsePipeString(string $pipe): array
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }
}
