<?php
/**
 * Invoice Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Invoice;
use WorkShift\Models\Client;
use WorkShift\Models\TimeEntry;

class InvoiceController extends Controller
{
    private Invoice $invoiceModel;
    private Client $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->invoiceModel = new Invoice();
        $this->clientModel = new Client();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $status = $request->input('status');
        $invoices = $this->invoiceModel->getByUserId($userId, $status);
        $stats = $this->invoiceModel->getStats($userId);

        $this->view('invoices.index', [
            'pageTitle' => 'Invoices & Billing — WorkShift',
            'invoices' => $invoices,
            'stats' => $stats,
            'currentStatus' => $status,
        ], 'main');
    }

    public function showCreate(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $clients = $this->clientModel->getByUserId($userId);
        $selectedClientId = (int)$request->input('client_id');
        $selectedClient = null;
        $unbilledHours = 0;

        if ($selectedClientId) {
            $selectedClient = $this->clientModel->findById($selectedClientId, $userId);
        }

        $nextInvoiceNumber = $this->invoiceModel->generateNextInvoiceNumber($userId);

        $this->view('invoices.create', [
            'pageTitle' => 'Create New Invoice — WorkShift',
            'clients' => $clients,
            'selectedClient' => $selectedClient,
            'selectedClientId' => $selectedClientId,
            'nextInvoiceNumber' => $nextInvoiceNumber,
        ], 'main');
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $clientId = (int)$request->input('client_id');
        $client = $this->clientModel->findById($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Please select a valid client.');
            $this->redirect('/invoices/create');
            return;
        }

        $invoiceNumber = trim($request->input('invoice_number', ''));
        if (empty($invoiceNumber)) {
            $invoiceNumber = $this->invoiceModel->generateNextInvoiceNumber($userId);
        }

        $issueDate = $request->input('issue_date', date('Y-m-d'));
        $dueDate = $request->input('due_date', date('Y-m-d', strtotime('+14 days')));

        $descriptions = $request->input('item_description', []);
        $quantities = $request->input('item_quantity', []);
        $unitPrices = $request->input('item_unit_price', []);

        $items = [];
        $subtotal = 0.0;

        if (is_array($descriptions)) {
            foreach ($descriptions as $i => $desc) {
                if (empty(trim($desc))) continue;
                $qty = (float)($quantities[$i] ?? 1);
                $rate = (float)($unitPrices[$i] ?? 0);
                $total = round($qty * $rate, 2);
                $subtotal += $total;

                $items[] = [
                    'description' => $desc,
                    'quantity' => $qty,
                    'unit_price' => $rate,
                    'total' => $total,
                ];
            }
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one line item to the invoice.');
            $this->redirect('/invoices/create?client_id=' . $clientId);
            return;
        }

        $taxRate = (float)$request->input('tax_rate', 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = $subtotal + $taxAmount;

        $status = $request->input('status', 'draft');
        if (!in_array($status, ['draft', 'sent'])) {
            $status = 'draft';
        }

        $invoiceId = $this->invoiceModel->createWithItems([
            'user_id' => $userId,
            'client_id' => $clientId,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => 'PHP',
            'status' => $status,
            'notes' => $request->input('notes'),
        ], $items);

        $this->flash('success', "Invoice {$invoiceNumber} created successfully.");
        $this->redirect("/invoices/{$invoiceId}");
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $invoiceId = (int)$id;

        $invoice = $this->invoiceModel->findById($invoiceId, $userId);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect('/invoices');
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $appUrl = rtrim($config['app']['url'] ?? 'http://localhost:8000', '/');
        $portalPayUrl = "{$appUrl}/portal/{$invoice['portal_token']}/pay/{$invoice['id']}";

        $this->view('invoices.view', [
            'pageTitle' => "Invoice {$invoice['invoice_number']} — WorkShift",
            'invoice' => $invoice,
            'portalPayUrl' => $portalPayUrl,
        ], 'main');
    }

    public function print(Request $request, string $id): void
    {
        $userId = Auth::id();
        $invoiceId = (int)$id;

        // Freelancer viewing print
        $invoice = $this->invoiceModel->findById($invoiceId, $userId);

        // Or client viewing print via portal token
        if (!$invoice && $request->input('portal_token')) {
            $token = $request->input('portal_token');
            $clientModel = new Client();
            $client = $clientModel->findByPortalToken($token);
            if ($client) {
                $invoice = $this->invoiceModel->findById($invoiceId);
                if ($invoice && (int)$invoice['client_id'] !== (int)$client['id']) {
                    $invoice = null;
                }
            }
        }

        if (!$invoice) {
            http_response_code(404);
            echo "Invoice not found.";
            exit;
        }

        $this->view('invoices.print', [
            'pageTitle' => "Print {$invoice['invoice_number']}",
            'invoice' => $invoice,
        ], null); // Render without layout for clean print
    }

    public function updateStatus(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $invoiceId = (int)$id;

        $invoice = $this->invoiceModel->findById($invoiceId, $userId);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect('/invoices');
            return;
        }

        $status = $request->input('status');
        $this->invoiceModel->updateStatus($invoiceId, $status);

        $this->flash('success', "Invoice marked as {$status}.");
        $this->redirect("/invoices/{$invoiceId}");
    }
}
