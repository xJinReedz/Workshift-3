<?php
/**
 * Notification Controller
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
        $userId = Auth::id();

        $notifications = $this->notifModel->getByUserId($userId, 50);

        if ($request->isAjax()) {
            $this->json(['notifications' => $notifications]);
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
        $userId = Auth::id();
        $this->notifModel->markAsRead((int)$id, $userId);

        if ($request->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        $this->redirect('/notifications');
    }

    public function markAllRead(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $this->notifModel->markAllAsRead($userId);

        if ($request->isAjax()) {
            $this->json(['success' => true]);
            return;
        }

        $this->flash('success', 'All notifications marked as read.');
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
}
