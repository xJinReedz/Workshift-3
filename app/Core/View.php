<?php
/**
 * Server-rendered View Engine
 */

namespace WorkShift\Core;

class View
{
    private string $viewPath;

    public function __construct()
    {
        $this->viewPath = dirname(__DIR__) . '/Views/';
    }

    public function render(string $view, array $data = [], ?string $layout = 'main'): string
    {
        // Extract data into scope
        extract($data);

        // Capture view content
        $viewFile = $this->viewPath . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View not found: {$view} (looked at {$viewFile})");
        }

        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // If no layout is specified, return raw content (e.g. AJAX modals)
        if ($layout === null) {
            $html = $content;
        } else {
            // Render with layout
            $layoutFile = $this->viewPath . 'layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                $html = $content;
            } else {
                ob_start();
                include $layoutFile;
                $html = ob_get_clean();
            }
        }

        // Auto-prefix root-relative URLs if app runs in a subdirectory
        if (function_exists('base_path')) {
            $base = base_path();
            if ($base !== '') {
                $baseParts = array_filter(explode('/', trim($base, '/')));
                $guards = [];
                $accum = '';
                foreach ($baseParts as $part) {
                    $accum .= ($accum ? '/' : '') . $part;
                    $guards[] = preg_quote($accum, '~');
                }
                $guardPattern = implode('|', array_reverse($guards));

                // Prefix href, action, src that start with '/' but not '//', '#', or any segment of $base
                $pattern = '~\b(href|action|src)=([\'"])/(?!(' . $guardPattern . ')(?=[/\'"?#]|$)|/|#)(.*?)\2~i';
                $html = preg_replace_callback($pattern, function($matches) use ($base) {
                    return $matches[1] . '=' . $matches[2] . $base . '/' . ltrim($matches[4], '/') . $matches[2];
                }, $html);
            }
        }

        return $html;
    }

    public function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public function csrf(): string
    {
        $token = Session::getCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . $this->e($token) . '">';
    }

    public function csrfToken(): string
    {
        return Session::getCsrfToken();
    }
}
