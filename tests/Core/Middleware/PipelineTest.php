<?php

namespace Tests\Core\Middleware;

use PHPUnit\Framework\TestCase;
use App\Core\Middleware\Pipeline;

class TrimStringMiddleware
{
    public function handle($request, $next)
    {
        $request = trim($request);
        return $next($request);
    }
}

class AppendStringMiddleware
{
    public function handle($request, $next)
    {
        $request .= " (appended)";
        $response = $next($request);
        return $response . " [finished]";
    }
}

class PipelineTest extends TestCase
{
    public function testPipelineExecution()
    {
        $pipeline = new Pipeline();

        $payload = "  Hello World  ";

        $result = $pipeline->send($payload)
            ->through([
                TrimStringMiddleware::class,
                AppendStringMiddleware::class,
            ])
            ->then(function ($request) {
                return $request . " - destination";
            });

        $this->assertEquals("Hello World (appended) - destination [finished]", $result);
    }
}
