<?php
/**
 * Lightweight URL Router
 */

namespace WorkShift\Core;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, mixed $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function get(string $pattern, mixed $handler, array $middleware = []): self
    {
        return $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, mixed $handler, array $middleware = []): self
    {
        return $this->add('POST', $pattern, $handler, $middleware);
    }

    public function put(string $pattern, mixed $handler, array $middleware = []): self
    {
        return $this->add('PUT', $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, mixed $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): void
    {
        $requestMethod = $request->getMethod();
        $requestPath = rtrim($request->getPath(), '/');
        if (empty($requestPath)) {
            $requestPath = '/';
        }

        // Check CSRF for state-changing methods
        if (!$request->validateCsrf()) {
            if ($request->isAjax()) {
                json_error('Security token mismatch or session expired. Please refresh the page.', 'CSRF_FAILED', 419);
            } else {
                Session::flash('error', 'Security token mismatch. Please try again.');
                Response::redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
            return;
        }

        foreach ($this->routes as $route) {
            $pattern = rtrim($route['pattern'], '/');
            if (empty($pattern)) {
                $pattern = '/';
            }

            // Convert pattern to regex
            // e.g. {id} -> (?P<id>[^/]+) or {token:[a-zA-Z0-9_-]+}
            $regex = preg_replace_callback('/\{([a-zA-Z0-9_]+)(?::([^\}]+))?\}/', function ($matches) {
                $name = $matches[1];
                $subpattern = $matches[2] ?? '[^/]+';
                return '(?P<' . $name . '>' . $subpattern . ')';
            }, $pattern);

            $regex = '#^' . $regex . '$#i';

            if (preg_match($regex, $requestPath, $matches)) {
                if ($route['method'] !== $requestMethod) {
                    continue; // Keep checking in case another route matches with right method
                }

                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    if (is_callable($mw)) {
                        $res = $mw($request);
                        if ($res === false) return;
                    }
                }

                $handler = $route['handler'];

                if (is_callable($handler)) {
                    call_user_func($handler, $request, $params);
                    return;
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $method] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $method], array_merge([$request], array_values($params)));
                    return;
                }
            }
        }

        // If no match found, render 404
        if ($request->isAjax()) {
            json_error('The requested endpoint or resource was not found.', 'NOT_FOUND', 404);
        } else {
            Response::error(404, 'Page not found');
        }
    }
}
