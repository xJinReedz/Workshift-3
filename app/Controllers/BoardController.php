<?php
/**
 * Board Controller (Client Board Workspace)
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Board;
use WorkShift\Models\Stage;
use WorkShift\Models\Client;

class BoardController extends Controller
{
    private Board $boardModel;
    private Stage $stageModel;
    private Client $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->boardModel = new Board();
        $this->stageModel = new Stage();
        $this->clientModel = new Client();
    }

    public function show(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $boardId = (int)$id;

        $board = $this->boardModel->findById($boardId);
        if (!$board || (int)$board['user_id'] !== $userId) {
            $this->flash('error', 'Board not found or access denied.');
            $this->redirect('/clients');
            return;
        }

        $filterWaitingOnClient = (bool)$request->input('waiting_client');

        $data = $this->boardModel->getFullBoardData($boardId, $filterWaitingOnClient);

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $appUrl = rtrim($config['app']['url'] ?? 'http://localhost:8000', '/');
        $portalUrl = "{$appUrl}/portal/{$board['portal_token']}";

        $this->view('boards.show', [
            'pageTitle' => "{$board['title']} — WorkShift",
            'board' => $data['board'],
            'stages' => $data['stages'],
            'tasksByStage' => $data['tasksByStage'],
            'waitingOnClientCount' => $data['waitingOnClientCount'],
            'waitingOnYouCount' => $data['waitingOnYouCount'],
            'filterWaitingOnClient' => $filterWaitingOnClient,
            'portalUrl' => $portalUrl,
            'isClientPortal' => false,
        ], 'main');
    }

    public function addStage(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $boardId = (int)$id;

        $board = $this->boardModel->findById($boardId);
        if (!$board || (int)$board['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $title = trim($request->input('title', ''));
        if (empty($title)) {
            $this->json(['error' => 'Stage title is required'], 422);
            return;
        }

        $stageId = $this->stageModel->create([
            'board_id' => $boardId,
            'title' => $title,
            'position' => 999,
            'is_review_stage' => (int)$request->input('is_review_stage', 0),
            'is_done_stage' => (int)$request->input('is_done_stage', 0),
        ]);

        $this->json(['success' => true, 'stage_id' => $stageId, 'title' => $title]);
    }

    public function updateStage(Request $request, string $id, string $stageId): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $boardId = (int)$id;

        $board = $this->boardModel->findById($boardId);
        if (!$board || (int)$board['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $title = trim($request->input('title', ''));
        if (empty($title)) {
            $this->json(['error' => 'Title required'], 422);
            return;
        }

        $this->stageModel->update((int)$stageId, [
            'title' => $title,
            'is_review_stage' => (int)$request->input('is_review_stage', 0),
            'is_done_stage' => (int)$request->input('is_done_stage', 0),
        ]);

        $this->json(['success' => true]);
    }

    public function deleteStage(Request $request, string $id, string $stageId): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $boardId = (int)$id;

        $board = $this->boardModel->findById($boardId);
        if (!$board || (int)$board['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $this->stageModel->delete((int)$stageId);
        $this->json(['success' => true]);
    }
}
