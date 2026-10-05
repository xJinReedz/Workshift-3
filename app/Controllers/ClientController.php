<?php
/**
 * Client CRM Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Core\Session;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Client;
use WorkShift\Models\Invoice;
use WorkShift\Models\TimeEntry;
use WorkShift\Models\PortalToken;
use WorkShift\Services\PlanLimitService;

class ClientController extends Controller
{
    private Client $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->clientModel = new Client();
    }

    public function index(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $stage = $request->input('stage');
        $search = $request->input('search');

        $clients = $this->clientModel->getByUserId($userId, $stage, $search);
        $pipelineCounts = $this->clientModel->getPipelineCounts($userId);
        $user = Auth::user();
        $canAdd = PlanLimitService::canCreateActiveClient($user);

        $this->view('clients.index', [
            'pageTitle' => 'Clients CRM — WorkShift',
            'clients' => $clients,
            'currentStage' => $stage,
            'search' => $search,
            'pipelineCounts' => $pipelineCounts,
            'canAdd' => $canAdd,
        ], 'main');
    }

    public function pipeline(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $allClients = $this->clientModel->getByUserId($userId);

        $pipeline = [
            'inquiry' => [],
            'active' => [],
            'completed' => [],
            'archived' => [],
        ];

        foreach ($allClients as $c) {
            $stage = $c['pipeline_stage'];
            if (isset($pipeline[$stage])) {
                $pipeline[$stage][] = $c;
            }
        }

        $user = Auth::user();
        $canAdd = PlanLimitService::canCreateActiveClient($user);

        $this->view('clients.pipeline', [
            'pageTitle' => 'Client Pipeline — WorkShift',
            'pipeline' => $pipeline,
            'canAdd' => $canAdd,
        ], 'main');
    }

    public function showCreate(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();

        if (!PlanLimitService::canCreateActiveClient($user)) {
            $this->flash('upgrade_prompt', 'You have reached the 3 active clients limit on the Basic plan. Upgrade to WorkShift Pro (₱499/mo) for unlimited clients!');
            $this->redirect('/settings?upgrade=1');
            return;
        }

        $this->view('clients.form', [
            'pageTitle' => 'Add New Client — WorkShift',
            'client' => null,
            'isEdit' => false,
            'defaultRate' => $user['hourly_rate'] ?? 600.00,
        ], 'main');
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $userId = (int)$user['id'];

        $pipelineStage = $request->input('pipeline_stage', 'active');

        if ($pipelineStage === 'active' && !PlanLimitService::canCreateActiveClient($user)) {
            $this->flash('error', 'You have reached your 3 active clients limit on the Basic plan. Upgrade to Pro for unlimited clients.');
            $this->redirect('/clients');
            return;
        }

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));

        if (empty($name) || empty($email)) {
            $this->flash('error', 'Client name and email address are required.');
            $this->redirect('/clients/create');
            return;
        }

        $clientId = $this->clientModel->create([
            'user_id' => $userId,
            'name' => $name,
            'company' => $request->input('company'),
            'email' => $email,
            'phone' => $request->input('phone'),
            'notes' => $request->input('notes'),
            'billing_type' => $request->input('billing_type', 'hourly'),
            'rate' => (float)$request->input('rate', 0),
            'pipeline_stage' => $pipelineStage,
        ]);

        $this->flash('success', "Client \"{$name}\" added! Their dedicated board has been initialized.");
        $this->redirect("/clients/{$clientId}");
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;

        $client = $this->clientModel->findById($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $invoiceModel = new Invoice();
        $invoices = $invoiceModel->getByClientId($clientId);

        $timeModel = new TimeEntry();
        $timeEntries = $timeModel->getByClientId($clientId);

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $appUrl = rtrim($config['app']['url'] ?? 'http://localhost:8000', '/');
        $portalUrl = "{$appUrl}/portal/{$client['portal_token']}";

        $this->view('clients.view', [
            'pageTitle' => "Client: {$client['name']} — WorkShift",
            'client' => $client,
            'invoices' => $invoices,
            'timeEntries' => $timeEntries,
            'portalUrl' => $portalUrl,
        ], 'main');
    }

    public function showEdit(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;

        $client = $this->clientModel->findById($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $this->view('clients.form', [
            'pageTitle' => "Edit Client: {$client['name']} — WorkShift",
            'client' => $client,
            'isEdit' => true,
        ], 'main');
    }

    public function update(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;

        $client = $this->clientModel->findById($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));

        if (empty($name) || empty($email)) {
            $this->flash('error', 'Client name and email address are required.');
            $this->redirect("/clients/{$clientId}/edit");
            return;
        }

        $this->clientModel->update($clientId, $userId, [
            'name' => $name,
            'company' => $request->input('company'),
            'email' => $email,
            'phone' => $request->input('phone'),
            'notes' => $request->input('notes'),
            'billing_type' => $request->input('billing_type', 'hourly'),
            'rate' => (float)$request->input('rate', 0),
            'pipeline_stage' => $request->input('pipeline_stage', 'active'),
        ]);

        $this->flash('success', "Client details updated.");
        $this->redirect("/clients/{$clientId}");
    }

    public function updateStage(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;
        $stage = $request->input('stage');

        if ($stage === 'active') {
            $user = Auth::user();
            if (!PlanLimitService::canCreateActiveClient($user)) {
                if ($request->isAjax()) {
                    $this->json(['error' => 'Active client limit reached on Basic plan.'], 403);
                }
                $this->flash('error', 'Active client limit reached on Basic plan.');
                $this->redirect('/clients');
                return;
            }
        }

        $success = $this->clientModel->updatePipelineStage($clientId, $userId, $stage);

        if ($request->isAjax()) {
            $this->json(['success' => $success]);
            return;
        }

        $this->flash('success', 'Pipeline stage updated.');
        $this->redirect('/clients/pipeline');
    }

    public function regeneratePortalToken(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;

        $client = $this->clientModel->findById($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $portalTokenModel = new PortalToken();
        $portalTokenModel->regenerate($clientId);

        $this->flash('success', 'Client portal invite link has been regenerated. Previous link is now invalid.');
        $this->redirect("/clients/{$clientId}");
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $clientId = (int)$id;

        $this->clientModel->delete($clientId, $userId);
        $this->flash('info', 'Client and associated board removed.');
        $this->redirect('/clients');
    }
}
