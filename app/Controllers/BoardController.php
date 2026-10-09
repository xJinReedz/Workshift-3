<?php
/**
 * Board Controller (Client Board Workspace & Stage Management)
 * Product: WorkShift
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
        $ownerId = Auth::id();

        $board = $this->boardModel->findById($id);
        if (!$board || (string)$board['owner_id'] !== (string)$ownerId) {
            $this->flash('error', 'Board not found or access denied.');
            $this->redirect('/clients');
            return;
        }

        $filterWaitingOnClient = (bool)$request->input('waiting_client');
        $data = $this->boardModel->getFullBoardData($id, $filterWaitingOnClient);

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
        $ownerId = Auth::id();

        $board = $this->boardModel->findById($id);
        if (!$board || (string)$board['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized board access', 'UNAUTHORIZED', 403);
            return;
        }

        $title = trim($request->input('title', $request->input('name', '')));
        if (empty($title)) {
            json_error('Stage title is required', 'VALIDATION_FAILED', 422, ['title' => 'Stage title is required']);
            return;
        }

        $color = $request->input('color', '#4C9AFF');
        $clientReview = (bool)$request->input('client_review', $request->input('is_review_stage', 0));
        $isDone = (bool)$request->input('is_done_stage', $request->input('is_done', 0));

        $stageId = $this->stageModel->create([
            'board_id' => $id,
            'title' => $title,
            'position' => 999,
            'color' => $color,
            'client_review' => $clientReview,
            'is_done_stage' => $isDone,
        ]);

        json_success([
            'stage_id' => $stageId,
            'id' => $stageId,
            'title' => $title,
            'client_review' => $clientReview,
            'is_done_stage' => $isDone,
        ]);
    }

    public function updateStage(Request $request, string $id, string $stageId): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $board = $this->boardModel->findById($id);
        if (!$board || (string)$board['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized board access', 'UNAUTHORIZED', 403);
            return;
        }

        $stage = $this->stageModel->findById($stageId);
        if (!$stage || (string)$stage['board_id'] !== (string)$id) {
            json_error('Stage not found', 'NOT_FOUND', 404);
            return;
        }

        $title = trim($request->input('title', $request->input('name', $stage['title'])));
        if (empty($title)) {
            json_error('Stage title is required', 'VALIDATION_FAILED', 422);
            return;
        }

        $color = $request->input('color', $stage['color'] ?? '#4C9AFF');
        $clientReview = (bool)$request->input('client_review', $request->input('is_review_stage', $stage['client_review']));
        $isDone = (bool)$request->input('is_done_stage', $request->input('is_done', $stage['is_done_stage']));

        $this->stageModel->update($stageId, [
            'title' => $title,
            'color' => $color,
            'client_review' => $clientReview,
            'is_done_stage' => $isDone,
        ]);

        json_success([
            'stage_id' => $stageId,
            'title' => $title,
            'client_review' => $clientReview,
            'is_done_stage' => $isDone,
        ]);
    }

    public function deleteStage(Request $request, string $id, string $stageId): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $board = $this->boardModel->findById($id);
        if (!$board || (string)$board['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized board access', 'UNAUTHORIZED', 403);
            return;
        }

        $stage = $this->stageModel->findById($stageId);
        if (!$stage || (string)$stage['board_id'] !== (string)$id) {
            json_error('Stage not found', 'NOT_FOUND', 404);
            return;
        }

        $destinationStageId = $request->input('destination_stage_id', null);
        $this->stageModel->deleteWithTaskMove($stageId, $destinationStageId);

        json_success(['message' => 'Stage deleted successfully']);
    }
}
