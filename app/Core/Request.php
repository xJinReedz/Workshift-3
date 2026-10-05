<?php
/**
 * HTTP Request Handler
 */

namespace WorkShift\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $get;
    private array $post;
    private array $files;
    private array $server;
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;

        // Support PUT/DELETE method spoofing via _method in POST
        if ($this->method === 'POST' && isset($this->post['_method'])) {
            $this->method = strtoupper($this->post['_method']);
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        return (!empty($this->server['HTTP_X_REQUESTED_WITH']) &&
                strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               str_contains($this->server['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (isset($this->post[$key])) {
            return is_string($this->post[$key]) ? trim($this->post[$key]) : $this->post[$key];
        }
        if (isset($this->get[$key])) {
            return is_string($this->get[$key]) ? trim($this->get[$key]) : $this->get[$key];
        }
        $json = $this->json();
        if (isset($json[$key])) {
            return $json[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json());
    }

    public function json(): array
    {
        if ($this->jsonBody !== null) {
            return $this->jsonBody;
        }

        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        } else {
            $this->jsonBody = [];
        }

        return $this->jsonBody;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function validateCsrf(): bool
    {
        if (in_array($this->method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $token = $this->input('_csrf_token') ??
                 $this->server['HTTP_X_CSRF_TOKEN'] ??
                 null;

        return Session::validateCsrfToken($token);
    }
}
