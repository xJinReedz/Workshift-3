<?php
/**
 * Task Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\Task;
use WorkShift\Models\TaskBlocker;
use WorkShift\Models\TaskFile;
use WorkShift\Models\Comment;
use WorkShift\Models\TimeEntry;
use WorkShift\Models\Notification;
use WorkShift\Services\FileStorageService;
use Exception;

class TaskController extends Controller
{
    private Task $taskModel;
    private TaskBlocker $blockerModel;
    private TaskFile $fileModel;
    private Comment $commentModel;
    private TimeEntry $timeModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskModel = new Task();
        $this->blockerModel = new TaskBlocker();
        $this->fileModel = new TaskFile();
        $this->commentModel = new Comment();
        $this->timeModel = new TimeEntry();
    }

    public function store(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $title = trim($request->input('title', ''));
        $boardId = (int)$request->input('board_id');
        $stageId = (int)$request->input('stage_id');

        if (empty($title) || !$boardId || !$stageId) {
            if ($request->isAjax()) {
                $this->json(['error' => 'Title and stage are required.'], 422);
            }
            $this->flash('error', 'Task title is required.');
            $this->redirect("/boards/{$boardId}");
            return;
        }

        $taskId = $this->taskModel->create([
            'board_id' => $boardId,
            'stage_id' => $stageId,
            'user_id' => $userId,
            'title' => $title,
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
        ]);

        if ($request->isAjax()) {
            $task = $this->taskModel->findById($taskId);
            $this->json(['success' => true, 'task' => $task]);
            return;
        }

        $this->flash('success', 'Task created.');
        $this->redirect("/boards/{$boardId}");
    }

    public function modal(Request $request, string $id): void
    {
        $taskId = (int)$id;
        $task = $this->taskModel->findById($taskId);

        if (!$task) {
            http_response_code(404);
            echo '<div class="p-4 text-center text-muted">Task not found.</div>';
            return;
        }

        // Authorization: must be owner freelancer or authenticated client portal token
        $isClient = false;
        if (!Auth::check()) {
            $portalToken = $request->input('portal_token');
            if ($portalToken) {
                $clientModel = new \WorkShift\Models\Client();
                $client = $clientModel->findByPortalToken($portalToken);
                if (!$client || (int)$client['id'] !== (int)$task['client_id']) {
                    http_response_code(403);
                    echo '<div class="p-4 text-center text-danger">Access denied.</div>';
                    return;
                }
                $isClient = true;
            } else {
                http_response_code(403);
                echo '<div class="p-4 text-center text-danger">Access denied.</div>';
                return;
            }
        } else {
            if ((int)$task['user_id'] !== Auth::id()) {
                http_response_code(403);
                echo '<div class="p-4 text-center text-danger">Access denied.</div>';
                return;
            }
        }

        $files = $this->fileModel->getByTaskId($taskId);
        $comments = $this->commentModel->getByTaskId($taskId);
        $timeEntries = $this->timeModel->getByTaskId($taskId, !$isClient);

        // Render modal partial without layout
        echo $this->viewEngine->render('tasks.modal_content', [
            'task' => $task,
            'files' => $files,
            'comments' => $comments,
            'timeEntries' => $timeEntries,
            'isClient' => $isClient,
            'portalToken' => $request->input('portal_token', ''),
        ], null);
    }

    public function update(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $taskId = (int)$id;

        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $title = trim($request->input('title', ''));
        if (empty($title)) {
            $this->json(['error' => 'Title cannot be empty.'], 422);
            return;
        }

        $this->taskModel->update($taskId, [
            'title' => $title,
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
        ]);

        $this->json(['success' => true]);
    }

    public function move(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $taskId = (int)$id;

        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $stageId = (int)$request->input('stage_id');
        $position = (int)$request->input('position', 0);

        $this->taskModel->updatePositionAndStage($taskId, $stageId, $position);

        $this->json(['success' => true]);
    }

    public function setBlocker(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $taskId = (int)$id;

        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $type = $request->input('type');
        $reason = trim($request->input('reason', ''));
        $waitingOn = $request->input('waiting_on', 'client');

        if (empty($type) || $type === 'none') {
            $this->blockerModel->removeBlocker($taskId);
            $this->json(['success' => true, 'action' => 'removed']);
            return;
        }

        if (empty($reason)) {
            $this->json(['error' => 'Please provide a short reason for what is blocking this task.'], 422);
            return;
        }

        $this->blockerModel->setBlocker($taskId, $type, $reason, $waitingOn);

        $badgeHtml = blocker_badge([
            'type' => $type,
            'reason' => $reason,
            'waiting_on' => $waitingOn,
            'waiting_since' => date('Y-m-d H:i:s'),
        ]);

        $this->json([
            'success' => true,
            'action' => 'set',
            'badge_html' => $badgeHtml,
            'type' => $type,
            'reason' => $reason,
            'waiting_on' => $waitingOn,
        ]);
    }

    public function removeBlocker(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $taskId = (int)$id;

        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $this->blockerModel->removeBlocker($taskId);
        $this->json(['success' => true]);
    }

    public function addComment(Request $request, string $id): void
    {
        $taskId = (int)$id;
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $content = trim($request->input('content', ''));
        if (empty($content)) {
            $this->json(['error' => 'Comment cannot be blank.'], 422);
            return;
        }

        $isClient = false;
        $authorName = '';
        $userId = null;
        $clientId = null;

        if (Auth::check()) {
            $user = Auth::user();
            $authorName = $user['name'];
            $userId = (int)$user['id'];
        } else {
            // Check portal token
            $token = $request->input('portal_token');
            $clientModel = new \WorkShift\Models\Client();
            $client = $clientModel->findByPortalToken($token ?: '');
            if (!$client || (int)$client['id'] !== (int)$task['client_id']) {
                $this->json(['error' => 'Unauthorized'], 403);
                return;
            }
            $isClient = true;
            $authorName = $client['name'];
            $clientId = (int)$client['id'];

            // Notify freelancer
            $notif = new Notification();
            $notif->create(
                (int)$task['user_id'],
                'client_comment',
                "New comment from {$authorName}",
                "{$authorName} commented on \"{$task['title']}\": \"" . substr($content, 0, 60) . "...\"",
                "/boards/{$task['board_id']}"
            );
        }

        $commentId = $this->commentModel->create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'client_id' => $clientId,
            'author_name' => $authorName,
            'is_client' => $isClient ? 1 : 0,
            'content' => $content,
        ]);

        $this->json([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'author_name' => $authorName,
                'content' => nl2br(e($content)),
                'is_client' => $isClient,
                'created_at' => date('M j, Y g:ia'),
            ]
        ]);
    }

    public function uploadFile(Request $request, string $id): void
    {
        $taskId = (int)$id;
        $task = $this->taskModel->findById($taskId);
        if (!$task) {
            $this->json(['error' => 'Task not found'], 404);
            return;
        }

        $isClient = false;
        $uploadedByUserId = null;

        if (Auth::check()) {
            $uploadedByUserId = Auth::id();
        } else {
            $token = $request->input('portal_token');
            $clientModel = new \WorkShift\Models\Client();
            $client = $clientModel->findByPortalToken($token ?: '');
            if (!$client || (int)$client['id'] !== (int)$task['client_id']) {
                $this->json(['error' => 'Unauthorized'], 403);
                return;
            }
            $isClient = true;
            $uploadedByUserId = (int)$task['user_id']; // Files count against project owner's quota
        }

        $file = $request->file('file');
        if (!$file) {
            $this->json(['error' => 'No file uploaded.'], 400);
            return;
        }

        try {
            $storageService = new FileStorageService();
            $uploaded = $storageService->upload($file, $uploadedByUserId);

            $fileId = $this->fileModel->create([
                'task_id' => $taskId,
                'uploaded_by_user_id' => $isClient ? null : Auth::id(),
                'uploaded_by_client' => $isClient ? 1 : 0,
                'original_name' => $uploaded['original_name'],
                'stored_filename' => $uploaded['stored_filename'],
                'mime_type' => $uploaded['mime_type'],
                'file_size' => $uploaded['file_size'],
            ]);

            if ($isClient) {
                $notif = new Notification();
                $notif->create(
                    (int)$task['user_id'],
                    'client_upload',
                    "Client uploaded a deliverable/file",
                    "{$task['client_name']} uploaded \"{$uploaded['original_name']}\" on task \"{$task['title']}\"",
                    "/boards/{$task['board_id']}"
                );
            }

            $this->json([
                'success' => true,
                'file' => [
                    'id' => $fileId,
                    'name' => $uploaded['original_name'],
                    'size' => format_bytes($uploaded['file_size']),
                    'url' => "/files/{$fileId}/download" . ($isClient ? "?portal_token=" . urlencode($request->input('portal_token')) : ""),
                ]
            ]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $taskId = (int)$id;

        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Unauthorized'], 403);
            return;
        }

        $this->taskModel->delete($taskId);
        $this->json(['success' => true]);
    }
}
