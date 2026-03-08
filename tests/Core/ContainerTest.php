<?php

namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Container;

class Engine
{
    public function start()
    {
        return 'Vroom!';
    }
}

class Car
{
    public $engine;
    
    public function __construct(Engine $engine)
    {
        $this->engine = $engine;
    }
    
    public function drive()
    {
        return 'Driving with ' . $this->engine->start();
    }
}

class ContainerTest extends TestCase
{
    protected $container;

    protected function setUp(): void
    {
        // Use reflection to reset the singleton for testing
        $reflector = new \ReflectionClass(Container::class);
        $property = $reflector->getProperty('instance');
        $property->setAccessible(true);
        $property->setValue(null);
        
        $this->container = Container::getInstance();
    }

    public function testSingletonInstance()
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testBindAndMakeWithClosure()
    {
        $this->container->bind('test_service', function() {
            return new \stdClass();
        });

        $obj1 = $this->container->make('test_service');
        $obj2 = $this->container->make('test_service');

        $this->assertInstanceOf(\stdClass::class, $obj1);
        $this->assertNotSame($obj1, $obj2);
    }

    public function testSingletonBinding()
    {
        $this->container->singleton('test_singleton', function() {
            return new \stdClass();
        });

        $obj1 = $this->container->make('test_singleton');
        $obj2 = $this->container->make('test_singleton');

        $this->assertSame($obj1, $obj2);
    }

    public function testAutoWiring()
    {
        $car = $this->container->make(Car::class);
        
        $this->assertInstanceOf(Car::class, $car);
        $this->assertInstanceOf(Engine::class, $car->engine);
        $this->assertEquals('Driving with Vroom!', $car->drive());
    }
}
