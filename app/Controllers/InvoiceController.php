<?php
/**
 * Invoice Controller
 * PostgreSQL + Supabase Compatibility
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
        $ownerId = Auth::id();

        $status = $request->input('status');
        $invoices = $this->invoiceModel->getByUserId($ownerId, $status);
        $stats = $this->invoiceModel->getStats($ownerId);

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
        $ownerId = Auth::id();

        $clients = $this->clientModel->getByUserId($ownerId);
        $selectedClientId = $request->input('client_id');
        $selectedClient = null;

        if ($selectedClientId) {
            $selectedClient = $this->clientModel->findById((string)$selectedClientId, $ownerId);
        }

        $nextInvoiceNumber = $this->invoiceModel->generateNextInvoiceNumber($ownerId);

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
        $ownerId = Auth::id();

        $clientId = (string)$request->input('client_id');
        $client = $this->clientModel->findById($clientId, $ownerId);
        if (!$client) {
            $this->flash('error', 'Please select a valid client.');
            $this->redirect('/invoices/create');
            return;
        }

        $invoiceNumber = trim($request->input('invoice_number', ''));
        if (empty($invoiceNumber)) {
            $invoiceNumber = $this->invoiceModel->generateNextInvoiceNumber($ownerId);
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
                $unit = (float)($unitPrices[$i] ?? 0);
                $lineTotal = round($qty * $unit, 2);
                $subtotal += $lineTotal;
                $items[] = [
                    'description' => trim($desc),
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $lineTotal,
                ];
            }
        }

        if (empty($items)) {
            $this->flash('error', 'Please add at least one item to the invoice.');
            $this->redirect('/invoices/create');
            return;
        }

        $taxRate = (float)$request->input('tax_rate', 0);
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $totalAmount = $subtotal + $taxAmount;

        $invoiceId = $this->invoiceModel->createWithItems([
            'owner_id' => $ownerId,
            'client_id' => $clientId,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => $request->input('currency', 'PHP'),
            'status' => $request->input('status', 'sent'),
            'notes' => $request->input('notes'),
        ], $items);

        $this->flash('success', "Invoice \"{$invoiceNumber}\" created!");
        $this->redirect("/invoices/{$invoiceId}");
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $invoice = $this->invoiceModel->findById($id, $ownerId);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect('/invoices');
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $appUrl = rtrim($config['app']['url'] ?? 'http://localhost:8000', '/');
        $portalPayUrl = "{$appUrl}/portal/{$invoice['portal_token']}/pay/{$id}";

        $this->view('invoices.show', [
            'pageTitle' => "Invoice {$invoice['invoice_number']} — WorkShift",
            'invoice' => $invoice,
            'portalPayUrl' => $portalPayUrl,
        ], 'main');
    }

    public function print(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $invoice = $this->invoiceModel->findById($id, $ownerId);
        if (!$invoice) {
            $this->flash('error', 'Invoice not found.');
            $this->redirect('/invoices');
            return;
        }

        $this->view('invoices.print', [
            'pageTitle' => "Invoice {$invoice['invoice_number']}",
            'invoice' => $invoice,
        ], null);
    }

    public function updateStatus(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $invoice = $this->invoiceModel->findById($id, $ownerId);
        if (!$invoice) {
            if ($request->isAjax()) {
                json_error('Invoice not found.', 'NOT_FOUND', 404);
                return;
            }
            $this->flash('error', 'Invoice not found.');
            $this->redirect('/invoices');
            return;
        }

        $status = $request->input('status');
        $this->invoiceModel->updateStatus($id, $status);

        if ($request->isAjax()) {
            json_success(['status' => $status]);
            return;
        }

        $this->flash('success', "Invoice status updated to " . ucfirst($status) . ".");
        $this->redirect("/invoices/{$id}");
    }
}
