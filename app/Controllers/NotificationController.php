<?php
/**
 * Notification Controller
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Notification;

class NotificationController extends Controller
{
    private Notification $notifModel;

    public function __construct()
    {
        parent::__construct();
        $this->notifModel = new Notification();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $notifications = $this->notifModel->getByUserId($ownerId, 50);

        if ($request->isAjax()) {
            json_success(['notifications' => $notifications]);
            return;
        }

        $this->view('notifications.index', [
            'pageTitle' => 'Notifications — WorkShift',
            'notifications' => $notifications,
        ], 'main');
    }

    public function markRead(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();
        $this->notifModel->markAsRead($id, $ownerId);

        if ($request->isAjax()) {
            json_success(['message' => 'Notification marked as read']);
            return;
        }

        $this->redirect('/notifications');
    }

    public function markAllRead(Request $request): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();
        $this->notifModel->markAllAsRead($ownerId);

        if ($request->isAjax()) {
            json_success(['message' => 'All notifications marked as read']);
            return;
        }

        $this->flash('success', 'All notifications marked as read.');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
}
