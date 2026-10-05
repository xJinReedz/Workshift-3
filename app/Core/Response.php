<?php
/**
 * HTTP Response Handler
 */

namespace WorkShift\Core;

class Response
{
    public static function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public static function json(mixed $data, int $statusCode = 200): void
    {
        self::setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $url, int $statusCode = 302): void
    {
        self::setStatusCode($statusCode);
        header("Location: {$url}");
        exit;
    }

    public static function error(int $code, string $message = ''): void
    {
        self::setStatusCode($code);
        $viewPath = dirname(__DIR__) . "/Views/errors/{$code}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>Error {$code}</h1><p>" . htmlspecialchars($message) . "</p>";
        }
        exit;
    }
}
