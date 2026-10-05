<?php
/**
 * Dashboard Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Client;
use WorkShift\Models\Task;
use WorkShift\Models\Invoice;
use WorkShift\Models\TimeEntry;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $userId = (int)$user['id'];

        $clientModel = new Client();
        $taskModel = new Task();
        $invoiceModel = new Invoice();
        $timeModel = new TimeEntry();

        // 1. Active Clients
        $activeClientsCount = $clientModel->countActiveByUserId($userId);
        $recentClients = $clientModel->getByUserId($userId, null, null);
        $recentClients = array_slice($recentClients, 0, 5);

        // 2. Blocked Tasks summary (Waiting on Client vs Waiting on You)
        $blockedSummary = $taskModel->getBlockedTasksSummary($userId);

        // 3. Invoices summary
        $invoiceStats = $invoiceModel->getStats($userId);
        $recentInvoices = $invoiceModel->getByUserId($userId);
        $recentInvoices = array_slice($recentInvoices, 0, 4);

        // 4. Hours logged this week
        $hoursThisWeek = $timeModel->getHoursThisWeek($userId);

        // 5. Active running timer if any
        $runningTimer = $timeModel->getRunningTimer($userId);

        $this->view('dashboard.index', [
            'pageTitle' => 'Freelance Dashboard — WorkShift',
            'user' => $user,
            'activeClientsCount' => $activeClientsCount,
            'recentClients' => $recentClients,
            'blockedSummary' => $blockedSummary,
            'invoiceStats' => $invoiceStats,
            'recentInvoices' => $recentInvoices,
            'hoursThisWeek' => $hoursThisWeek,
            'runningTimer' => $runningTimer,
        ], 'main');
    }
}
