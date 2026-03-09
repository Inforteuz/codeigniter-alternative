<?php

namespace System\Http;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];

    public function status(int $code): self
    {
        $this->statusCode = $code;
        http_response_code($code);
        return $this;
    }

    public function header(string $name, string $value, bool $replace = true): self
    {
        $this->headers[$name] = $value;
        header($name . ': ' . $value, $replace);
        return $this;
    }

    public function json($data, int $status = 200, array $headers = []): never
    {
        $this->status($status);
        $this->header('Content-Type', 'application/json');
        foreach ($headers as $k => $v) {
            $this->header((string) $k, (string) $v);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function noContent(int $status = 204): never
    {
        $this->status($status);
        exit;
    }
}

