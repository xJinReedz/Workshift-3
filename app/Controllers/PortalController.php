<?php
/**
 * Client Portal Controller
 * Strict Client Isolation: All queries scoped strictly by token-resolved client_id / profile
 * Product: WorkShift
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Client;
use WorkShift\Models\Board;
use WorkShift\Models\Task;
use WorkShift\Models\TimeEntry;
use WorkShift\Models\Invoice;
use WorkShift\Models\PortalToken;
use WorkShift\Models\Notification;
use WorkShift\Models\TaskBlocker;

class PortalController extends Controller
{
    private Client $clientModel;
    private Board $boardModel;
    private Task $taskModel;
    private TimeEntry $timeModel;
    private Invoice $invoiceModel;
    private PortalToken $tokenModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
        $this->boardModel = new Board();
        $this->taskModel = new Task();
        $this->timeModel = new TimeEntry();
        $this->invoiceModel = new Invoice();
        $this->tokenModel = new PortalToken();
    }

    private function resolveClient(string $token): ?array
    {
        $client = $this->clientModel->findByPortalToken($token);
        if ($client) {
            $this->tokenModel->recordAccess($token);
        }
        return $client;
    }

    public function show(Request $request, string $token): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            http_response_code(404);
            $this->view('errors.404', ['message' => 'This client portal link is invalid, expired, or has been revoked by the freelancer.'], 'public');
            return;
        }

        $boardId = (string)$client['board_id'];
        $boardData = $this->boardModel->getFullBoardData($boardId, false, true);
        $timeEntries = $this->timeModel->getByClientId((string)$client['id'], false);
        $invoices = $this->invoiceModel->getByClientId((string)$client['id']);
        $canPayOnline = (($client['freelancer_plan'] ?? 'basic') === 'pro');

        $this->view('portal.board', [
            'pageTitle' => "Client Portal — {$client['name']} & {$client['freelancer_company']}",
            'client' => $client,
            'token' => $token,
            'board' => $boardData['board'] ?? null,
            'stages' => $boardData['stages'] ?? [],
            'tasksByStage' => $boardData['tasksByStage'] ?? [],
            'waitingOnClientCount' => $boardData['waitingOnClientCount'] ?? 0,
            'waitingOnYouCount' => $boardData['waitingOnYouCount'] ?? 0,
            'timeEntries' => $timeEntries,
            'invoices' => $invoices,
            'canPayOnline' => $canPayOnline,
            'schedulingLink' => $client['freelancer_scheduling_link'] ?? '',
        ], 'portal');
    }

    public function poll(Request $request, string $token): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            json_error('Invalid portal token', 'UNAUTHORIZED', 403);
            return;
        }

        $boardId = (string)$client['board_id'];
        $boardData = $this->boardModel->getFullBoardData($boardId, false, true);
        $timeEntries = $this->timeModel->getByClientId((string)$client['id'], false);

        json_success([
            'tasksByStage' => $boardData['tasksByStage'] ?? [],
            'waitingOnClientCount' => $boardData['waitingOnClientCount'] ?? 0,
            'waitingOnYouCount' => $boardData['waitingOnYouCount'] ?? 0,
            'timeEntriesCount' => count($timeEntries),
        ]);
    }

    public function reviewTask(Request $request, string $token, string $taskId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            json_error('Unauthorized portal access', 'UNAUTHORIZED', 403);
            return;
        }

        $task = $this->taskModel->findById($taskId);
        if (!$task || (string)$task['client_id'] !== (string)$client['id']) {
            json_error('Task not found or access denied.', 'NOT_FOUND', 404);
            return;
        }

        $action = $request->input('action'); // 'approve' or 'request_changes'
        $notes = trim($request->input('notes', ''));

        $notif = new Notification();

        if ($action === 'approve') {
            $this->taskModel->updateReviewStatus($taskId, 'approved', $notes);

            $notif->create(
                (string)$task['owner_id'],
                'client_approved',
                "Work Approved by {$client['name']}",
                "{$client['name']} approved \"{$task['title']}\"!",
                "/boards/{$task['board_id']}"
            );

            json_success(['status' => 'approved', 'message' => 'Task approved successfully!']);
            return;
        }

        if ($action === 'request_changes') {
            if (empty($notes)) {
                json_error('Please provide feedback explaining what changes are needed.', 'VALIDATION_FAILED', 422);
                return;
            }

            $this->taskModel->updateReviewStatus($taskId, 'changes_requested', $notes);

            // Automatically set blocker on task so freelancer sees it immediately
            $blockerModel = new TaskBlocker();
            $blockerModel->setBlocker($taskId, 'feedback', "Client requested revisions: {$notes}", 'freelancer');

            // Notify freelancer
            $notif->create(
                (string)$task['owner_id'],
                'client_changes',
                "Changes Requested by {$client['name']}",
                "{$client['name']} requested revisions on \"{$task['title']}\": \"{$notes}\"",
                "/boards/{$task['board_id']}"
            );

            json_success(['status' => 'changes_requested', 'message' => 'Revision request submitted to freelancer.']);
            return;
        }

        json_error('Invalid review action.', 'BAD_REQUEST', 400);
    }

    public function showCheckout(Request $request, string $token, string $invoiceId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            $this->flash('error', 'Invalid portal link.');
            $this->redirect('/');
            return;
        }

        $invoice = $this->invoiceModel->findById($invoiceId);
        if (!$invoice || (string)$invoice['client_id'] !== (string)$client['id']) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect("/portal/{$token}");
            return;
        }

        if (($client['freelancer_plan'] ?? 'basic') !== 'pro') {
            $this->flash('error', 'Online payment is not enabled for this freelancer. Please contact them for payment instructions.');
            $this->redirect("/portal/{$token}");
            return;
        }

        $ref = $request->input('ref') ?: 'PAY-' . strtoupper(bin2hex(random_bytes(6)));

        $this->view('portal.invoice_pay', [
            'pageTitle' => "Pay Invoice {$invoice['invoice_number']} — {$client['freelancer_company']}",
            'client' => $client,
            'token' => $token,
            'invoice' => $invoice,
            'referenceNumber' => $ref,
        ], 'portal');
    }

    public function processPayment(Request $request, string $token, string $invoiceId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            json_error('Unauthorized portal access', 'UNAUTHORIZED', 403);
            return;
        }

        $invoice = $this->invoiceModel->findById($invoiceId);
        if (!$invoice || (string)$invoice['client_id'] !== (string)$client['id']) {
            json_error('Invoice not found.', 'NOT_FOUND', 404);
            return;
        }

        $ref = $request->input('reference') ?: 'PAY-' . strtoupper(bin2hex(random_bytes(6)));

        // Mark invoice as paid
        $this->invoiceModel->updateStatus($invoiceId, 'paid');

        // Notify freelancer
        $notif = new Notification();
        $notif->create(
            (string)$invoice['owner_id'],
            'client_payment',
            "Payment Received: " . format_currency((float)$invoice['total_amount']),
            "{$client['name']} has paid Invoice {$invoice['invoice_number']} (" . format_currency((float)$invoice['total_amount']) . "). Ref: {$ref}",
            "/invoices/{$invoice['id']}"
        );

        $this->flash('success', 'Payment successful! Thank you.');
        $this->redirect("/portal/{$token}/pay/{$invoiceId}/success?ref={$ref}");
    }

    public function paymentSuccess(Request $request, string $token, string $invoiceId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            $this->redirect('/');
            return;
        }

        $invoice = $this->invoiceModel->findById($invoiceId);
        $ref = $request->input('ref', 'COMPLETED');

        $this->view('portal.pay_success', [
            'pageTitle' => "Payment Confirmed — {$client['freelancer_company']}",
            'client' => $client,
            'token' => $token,
            'invoice' => $invoice,
            'referenceNumber' => $ref,
        ], 'portal');
    }
}
