<?php
/**
 * Base Controller Class
 */

namespace WorkShift\Core;

use WorkShift\Helpers\Auth;
use WorkShift\Services\PlanLimitService;

abstract class Controller
{
    protected View $viewEngine;

    public function __construct()
    {
        $this->viewEngine = new View();
    }

    protected function view(string $view, array $data = [], ?string $layout = 'main'): void
    {
        // Inject global helpers and currentUser into views
        $data['currentUser'] = Auth::user();
        $data['unreadNotificationCount'] = Auth::check() ? (new \WorkShift\Models\Notification())->getUnreadCount(Auth::id()) : 0;
        $data['planLimits'] = Auth::check() ? PlanLimitService::getUserPlanDetails(Auth::user()) : null;
        $data['appConfig'] = require dirname(__DIR__, 2) . '/config/config.php';

        echo $this->viewEngine->render($view, $data, $layout);
    }

    protected function json(mixed $data, int $statusCode = 200): void
    {
        Response::json($data, $statusCode);
    }

    protected function redirect(string $url): void
    {
        Response::redirect($url);
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please log in to continue.');
            $this->redirect('/login');
        }
    }

    protected function requireGuest(): void
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
    }

    protected function requirePro(): bool
    {
        $this->requireAuth();
        $user = Auth::user();
        if (($user['plan'] ?? 'basic') !== 'pro') {
            if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
                $this->json(['error' => 'This feature requires a WorkShift Pro plan.'], 403);
            } else {
                Session::flash('upgrade_prompt', 'Upgrade to WorkShift Pro (₱499/mo) to unlock this feature.');
                $this->redirect('/settings?upgrade=1');
            }
            return false;
        }
        return true;
    }

    protected function currentUser(): ?array
    {
        return Auth::user();
    }

    protected function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}
