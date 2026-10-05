<?php
/**
 * Client Portal Controller
 * Strict Client Isolation: All data queries scoped strictly by token-resolved client_id
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Models\Client;
use WorkShift\Models\Board;
use WorkShift\Models\Task;
use WorkShift\Models\TimeEntry;
use WorkShift\Models\Invoice;
use WorkShift\Models\PortalToken;
use WorkShift\Models\Notification;
use WorkShift\Services\Payment\MockPaymentProvider;
use WorkShift\Services\Payment\MayaPaymentProvider;
use WorkShift\Services\PlanLimitService;

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

        $boardId = (int)$client['board_id'];
        $boardData = $this->boardModel->getFullBoardData($boardId, false, true);

        // Fetch visible time entries for this client only (exclude private entries)
        $timeEntries = $this->timeModel->getByClientId((int)$client['id'], false);

        // Fetch unpaid or recent invoices for this client only (exclude drafts)
        $invoices = $this->invoiceModel->getByClientId((int)$client['id']);

        $canPayOnline = ($client['freelancer_plan'] === 'pro');

        $this->view('portal.board', [
            'pageTitle' => "Client Portal — {$client['name']} & {$client['freelancer_company']}",
            'client' => $client,
            'token' => $token,
            'board' => $boardData['board'],
            'stages' => $boardData['stages'],
            'tasksByStage' => $boardData['tasksByStage'],
            'waitingOnClientCount' => $boardData['waitingOnClientCount'],
            'waitingOnYouCount' => $boardData['waitingOnYouCount'],
            'timeEntries' => $timeEntries,
            'invoices' => $invoices,
            'canPayOnline' => $canPayOnline,
            'schedulingLink' => $client['freelancer_scheduling_link'],
        ], 'portal');
    }

    public function poll(Request $request, string $token): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            $this->json(['error' => 'Invalid portal token'], 403);
            return;
        }

        $boardId = (int)$client['board_id'];
        $boardData = $this->boardModel->getFullBoardData($boardId, false, true);
        $timeEntries = $this->timeModel->getByClientId((int)$client['id'], false);

        $this->json([
            'success' => true,
            'tasksByStage' => $boardData['tasksByStage'],
            'waitingOnClientCount' => $boardData['waitingOnClientCount'],
            'waitingOnYouCount' => $boardData['waitingOnYouCount'],
            'timeEntriesCount' => count($timeEntries),
        ]);
    }

    public function reviewTask(Request $request, string $token, string $taskId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $task = $this->taskModel->findById((int)$taskId);
        if (!$task || (int)$task['client_id'] !== (int)$client['id']) {
            $this->json(['error' => 'Task not found or access denied.'], 404);
            return;
        }

        $action = $request->input('action'); // 'approve' or 'request_changes'
        $notes = trim($request->input('notes', ''));

        $notif = new Notification();

        if ($action === 'approve') {
            $this->taskModel->updateReviewStatus((int)$taskId, 'approved', $notes);

            // Notify freelancer
            $notif->create(
                (int)$task['user_id'],
                'client_approved',
                "Work Approved by {$client['name']}",
                "{$client['name']} approved \"{$task['title']}\"!",
                "/boards/{$task['board_id']}"
            );

            $this->json(['success' => true, 'status' => 'approved']);
            return;
        }

        if ($action === 'request_changes') {
            if (empty($notes)) {
                $this->json(['error' => 'Please provide feedback explaining what changes are needed.'], 422);
                return;
            }

            $this->taskModel->updateReviewStatus((int)$taskId, 'changes_requested', $notes);

            // Automatically set blocker on task so freelancer sees it immediately
            $blockerModel = new \WorkShift\Models\TaskBlocker();
            $blockerModel->setBlocker((int)$taskId, 'feedback', "Client requested revisions: {$notes}", 'freelancer');

            // Notify freelancer
            $notif->create(
                (int)$task['user_id'],
                'client_changes',
                "Changes Requested by {$client['name']}",
                "{$client['name']} requested revisions on \"{$task['title']}\": \"{$notes}\"",
                "/boards/{$task['board_id']}"
            );

            $this->json(['success' => true, 'status' => 'changes_requested']);
            return;
        }

        $this->json(['error' => 'Invalid action.'], 400);
    }

    public function showCheckout(Request $request, string $token, string $invoiceId): void
    {
        $client = $this->resolveClient($token);
        if (!$client) {
            $this->flash('error', 'Invalid portal link.');
            $this->redirect('/');
            return;
        }

        $invoice = $this->invoiceModel->findById((int)$invoiceId);
        if (!$invoice || (int)$invoice['client_id'] !== (int)$client['id']) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect("/portal/{$token}");
            return;
        }

        // Pro plan check for in-app payments
        if ($client['freelancer_plan'] !== 'pro') {
            $this->flash('error', 'Online payment is not enabled for this freelancer. Please contact them for payment instructions.');
            $this->redirect("/portal/{$token}");
            return;
        }

        $ref = $request->input('ref') ?: 'MOCK-PAY-' . strtoupper(bin2hex(random_bytes(6)));

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
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $invoice = $this->invoiceModel->findById((int)$invoiceId);
        if (!$invoice || (int)$invoice['client_id'] !== (int)$client['id']) {
            $this->json(['error' => 'Invoice not found.'], 404);
            return;
        }

        $ref = $request->input('reference') ?: 'PAY-' . strtoupper(bin2hex(random_bytes(6)));

        // Record payment in database
        $db = \WorkShift\Core\Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO payments (invoice_id, provider, reference_number, amount, status, payload, created_at)
            VALUES (:invoice_id, 'mock', :ref, :amount, 'completed', :payload, NOW())
        ");
        $stmt->execute([
            'invoice_id' => $invoice['id'],
            'ref' => $ref,
            'amount' => $invoice['total_amount'],
            'payload' => json_encode(['paid_by' => $client['name'], 'client_id' => $client['id']]),
        ]);

        // Mark invoice as paid
        $this->invoiceModel->updateStatus((int)$invoice['id'], 'paid');

        // Notify freelancer
        $notif = new Notification();
        $notif->create(
            (int)$invoice['user_id'],
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

        $invoice = $this->invoiceModel->findById((int)$invoiceId);
        $ref = $request->input('ref', 'COMPLETED');

        $this->view('portal.pay_success', [
            'pageTitle' => "Payment Confirmed — {$client['freelancer_company']}",
            'client' => $client,
            'token' => $token,
            'invoice' => $invoice,
            'referenceNumber' => $ref,
        ], 'portal');
    }

    public function mayaWebhook(Request $request): void
    {
        // Webhook handler stub for Maya Business integration
        $signature = $_SERVER['HTTP_X_MAYA_SIGNATURE'] ?? '';
        $payload = $request->json();

        $maya = new MayaPaymentProvider();
        $result = $maya->handleWebhook($payload, $signature);

        if ($result['success'] && $result['invoice_id'] > 0) {
            $this->invoiceModel->updateStatus($result['invoice_id'], 'paid');
        }

        $this->json(['status' => 'received']);
    }
}
