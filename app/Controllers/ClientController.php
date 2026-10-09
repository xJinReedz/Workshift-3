<?php
/**
 * Client CRM Controller
 * PostgreSQL + Supabase Compatibility
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
        $ownerId = Auth::id();

        $stage = $request->input('stage');
        $search = $request->input('search');

        $clients = $this->clientModel->getByUserId($ownerId, $stage, $search);

        // Ensure every client has a dedicated workspace board
        $boardModel = new \WorkShift\Models\Board();
        foreach ($clients as &$c) {
            if (empty($c['board_id'])) {
                $boardTitle = (!empty($c['company']) ? $c['company'] : $c['name']) . " Workspace";
                $c['board_id'] = $boardModel->createDefaultBoard($c['id'], $ownerId, $boardTitle);
            }
        }
        unset($c);
        $pipelineCounts = $this->clientModel->getPipelineCounts($ownerId);
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
        $ownerId = Auth::id();

        $allClients = $this->clientModel->getByUserId($ownerId);

        $pipeline = [
            'inquiry' => [],
            'active' => [],
            'completed' => [],
            'archived' => [],
        ];

        foreach ($allClients as $c) {
            $stage = $c['status'] ?? 'active';
            if (isset($pipeline[$stage])) {
                $pipeline[$stage][] = $c;
            } else {
                $pipeline['active'][] = $c;
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
            'defaultRate' => $user['default_hourly_rate'] ?? 600.00,
        ], 'main');
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $user = Auth::user();
        $ownerId = Auth::id();

        $pipelineStage = $request->input('pipeline_stage', $request->input('status', 'active'));

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
            'owner_id' => $ownerId,
            'name' => $name,
            'company' => $request->input('company', $request->input('company_name')),
            'email' => $email,
            'phone' => $request->input('phone'),
            'notes' => $request->input('notes'),
            'rate' => (float)$request->input('rate', $request->input('hourly_rate', 0)),
            'pipeline_stage' => $pipelineStage,
        ]);

        $this->flash('success', "Client \"{$name}\" added! Their dedicated board has been initialized.");
        $this->redirect("/clients/{$clientId}");
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $client = $this->clientModel->findById($id, $ownerId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        if (empty($client['board_id'])) {
            $boardModel = new \WorkShift\Models\Board();
            $boardTitle = (!empty($client['company']) ? $client['company'] : $client['name']) . " Workspace";
            $client['board_id'] = $boardModel->createDefaultBoard($id, $ownerId, $boardTitle);
        }

        $invoiceModel = new Invoice();
        $invoices = $invoiceModel->getByClientId($id);

        $timeModel = new TimeEntry();
        $timeEntries = $timeModel->getByClientId($id);

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
        $ownerId = Auth::id();

        $client = $this->clientModel->findById($id, $ownerId);
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
        $ownerId = Auth::id();

        $client = $this->clientModel->findById($id, $ownerId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $name = trim($request->input('name', ''));
        $email = trim($request->input('email', ''));

        if (empty($name) || empty($email)) {
            $this->flash('error', 'Client name and email address are required.');
            $this->redirect("/clients/{$id}/edit");
            return;
        }

        $this->clientModel->update($id, $ownerId, [
            'name' => $name,
            'company' => $request->input('company', $request->input('company_name')),
            'email' => $email,
            'phone' => $request->input('phone'),
            'notes' => $request->input('notes'),
            'rate' => (float)$request->input('rate', $request->input('hourly_rate', 0)),
            'pipeline_stage' => $request->input('pipeline_stage', $request->input('status', 'active')),
        ]);

        $this->flash('success', "Client details updated.");
        $this->redirect("/clients/{$id}");
    }

    public function updateStage(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();
        $stage = $request->input('stage', $request->input('status'));

        if ($stage === 'active') {
            $user = Auth::user();
            if (!PlanLimitService::canCreateActiveClient($user)) {
                if ($request->isAjax()) {
                    json_error('Active client limit reached on Basic plan.', 'LIMIT_REACHED', 403);
                    return;
                }
                $this->flash('error', 'Active client limit reached on Basic plan.');
                $this->redirect('/clients');
                return;
            }
        }

        $success = $this->clientModel->updatePipelineStage($id, $ownerId, $stage);

        if ($request->isAjax()) {
            json_success(['message' => 'Pipeline stage updated.']);
            return;
        }

        $this->flash('success', 'Pipeline stage updated.');
        $this->redirect('/clients/pipeline');
    }

    public function regeneratePortalToken(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $client = $this->clientModel->findById($id, $ownerId);
        if (!$client) {
            $this->flash('error', 'Client not found.');
            $this->redirect('/clients');
            return;
        }

        $portalTokenModel = new PortalToken();
        $portalTokenModel->regenerate($id);

        $this->flash('success', 'Client portal invite link has been regenerated. Previous link is now invalid.');
        $this->redirect("/clients/{$id}");
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $this->clientModel->delete($id, $ownerId);
        $this->flash('info', 'Client and associated board removed.');
        $this->redirect('/clients');
    }
}
