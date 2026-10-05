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
            return $content;
        }

        // Render with layout
        $layoutFile = $this->viewPath . 'layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            return $content;
        }

        ob_start();
        include $layoutFile;
        return ob_get_clean();
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
