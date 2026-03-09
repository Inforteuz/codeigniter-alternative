<?php

namespace System\Error;

class ErrorRenderer
{
    public function render(int $code, string $message = ''): void
    {
        http_response_code($code);

        $errorFile = __DIR__ . "/../../app/Views/errors/{$code}.php";
        if (is_file($errorFile)) {
            include $errorFile;
            return;
        }

        $title = $this->titleFor($code);
        $safeMessage = $message !== '' ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : '';

        echo "<!DOCTYPE html><html lang=\"en\"><head><meta charset=\"UTF-8\">";
        echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">";
        echo "<title>{$code} - {$title}</title>";
        echo "<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,sans-serif;background:#f6f7f9;color:#111;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:24px} .box{max-width:720px;background:#fff;border:1px solid #e6e8ee;border-radius:12px;padding:28px;box-shadow:0 10px 35px rgba(0,0,0,.08)} h1{margin:0 0 8px;font-size:22px} .code{font-weight:800;font-size:52px;line-height:1;margin:0 0 14px;color:#555} p{margin:10px 0;color:#444} a{color:#0b5fff;text-decoration:none} a:hover{text-decoration:underline}</style>";
        echo "</head><body><div class=\"box\">";
        echo "<div class=\"code\">{$code}</div>";
        echo "<h1>{$title}</h1>";
        if ($safeMessage !== '') {
            echo "<p>{$safeMessage}</p>";
        }
        echo "<p><a href=\"/\">Go Home</a> · <a href=\"javascript:history.back()\">Go Back</a></p>";
        echo "</div></body></html>";
    }

    protected function titleFor(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }
}

