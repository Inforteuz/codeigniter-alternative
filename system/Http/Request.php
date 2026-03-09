<?php

namespace System\Http;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query;
    protected array $post;
    protected array $files;
    protected array $headers;
    protected string $userAgent;
    protected string $ip;
    protected string $rawBody;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $post = [],
        array $files = [],
        array $headers = [],
        string $userAgent = '',
        string $ip = '',
        string $rawBody = ''
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
        $this->headers = $headers;
        $this->userAgent = $userAgent;
        $this->ip = $ip;
        $this->rawBody = $rawBody;
    }

    public static function fromGlobals(): self
    {
        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $rawBody = file_get_contents('php://input') ?: '';

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET ?? [],
            $_POST ?? [],
            $_FILES ?? [],
            $headers,
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            self::detectIp(),
            $rawBody
        );
    }

    protected static function detectIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (empty($_SERVER[$key])) {
                continue;
            }
            $ip = (string) $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }

    public function method(): string { return $this->method; }
    public function uri(): string { return $this->uri; }
    public function query(): array { return $this->query; }
    public function post(): array { return $this->post; }
    public function files(): array { return $this->files; }
    public function headers(): array { return $this->headers; }
    public function userAgent(): string { return $this->userAgent; }
    public function ip(): string { return $this->ip; }
    public function rawBody(): string { return $this->rawBody; }

    public function header(string $name, $default = null)
    {
        $name = strtolower($name);
        foreach ($this->headers as $k => $v) {
            if (strtolower((string) $k) === $name) {
                return $v;
            }
        }
        return $default;
    }
}

