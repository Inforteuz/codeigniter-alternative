<?php

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Router;
use App\Core\Container;

class TestController
{
    public function index()
    {
        return 'Index Method';
    }

    public function show($id)
    {
        return 'Show ' . $id;
    }
}

class RouterTest extends TestCase
{
    protected $router;

    protected function setUp(): void
    {
        // Reset container
        $reflector = new \ReflectionClass(Container::class);
        $property = $reflector->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null);

        $this->router = new Router();
    }

    public function testClosureRoute()
    {
        $this->router->get('/test', function() {
            return 'Closure Hello';
        });

        ob_start();
        $result = $this->router->dispatch('GET', '/test');
        ob_end_clean();
        
        $this->assertEquals('Closure Hello', $result);
    }

    public function testControllerRoute()
    {
        $this->router->get('/users', TestController::class . '::index');

        ob_start();
        $result = $this->router->dispatch('GET', '/users');
        ob_end_clean();

        $this->assertEquals('Index Method', $result);
    }

    public function testRouteWithParameters()
    {
        $this->router->get('/users/{id}', TestController::class . '::show');

        ob_start();
        $result = $this->router->dispatch('GET', '/users/123');
        ob_end_clean();

        $this->assertEquals('Show 123', $result);
    }

    public function testRouteGroupWithPrefix()
    {
        $this->router->group(['prefix' => 'api'], function($router) {
            $router->get('/users', function() {
                return 'API Users';
            });
        });

        ob_start();
        $result = $this->router->dispatch('GET', '/api/users');
        ob_end_clean();

        $this->assertEquals('API Users', $result);
    }
}
