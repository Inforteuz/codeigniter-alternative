<?php

namespace Tests\Advanced;

use PHPUnit\Framework\TestCase;
use System\Core\Event;
use System\Security\Csrf;
use System\Core\Env;

class AdvancedFeaturesTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testEventSystem()
    {
        $triggered = false;
        $dataReceived = null;

        Event::listen('test.event', function($payload) use (&$triggered, &$dataReceived) {
            $triggered = true;
            $dataReceived = $payload;
            return 'result';
        });

        $results = Event::dispatch('test.event', 'hello world');

        $this->assertTrue($triggered);
        $this->assertEquals('hello world', $dataReceived);
        $this->assertEquals(['result'], $results);
    }

    public function testEventPriority()
    {
        $log = [];

        Event::listen('priority.test', function() use (&$log) {
            $log[] = 'low';
        }, 1);

        Event::listen('priority.test', function() use (&$log) {
            $log[] = 'high';
        }, 1000);

        Event::dispatch('priority.test');

        $this->assertEquals(['high', 'low'], $log);
    }

    public function testCsrfTtl()
    {
        // 1. Valid token
        $token = Csrf::generateToken();
        $this->assertTrue(Csrf::verifyToken($token));

        // 2. Expired token
        $token = Csrf::generateToken();
        $_SESSION['csrf_token_time'] = time() - 4000; // Force 1 hour+ old
        
        // Mock ENV to ensure TTL is 3600
        $this->assertFalse(Csrf::verifyToken($token));
    }
}
