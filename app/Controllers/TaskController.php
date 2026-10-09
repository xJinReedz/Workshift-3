<?php
/**
 * Task Controller (Tasks, Blockers, Comments, Files)
 * Product: WorkShift
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
use WorkShift\Models\Client;
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
        $ownerId = Auth::id();

        $title = trim($request->input('title', ''));
        $boardId = (string)$request->input('board_id');
        $stageId = (string)$request->input('stage_id');

        if (empty($title) || empty($boardId) || empty($stageId)) {
            json_error('Title, board, and stage are required.', 'VALIDATION_FAILED', 422);
            return;
        }

        $taskId = $this->taskModel->create([
            'board_id' => $boardId,
            'stage_id' => $stageId,
            'title' => $title,
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
        ]);

        $task = $this->taskModel->findById($taskId);
        json_success(['task' => $task, 'id' => $taskId]);
    }

    public function modal(Request $request, string $id): void
    {
        $task = $this->taskModel->findById($id);

        if (!$task) {
            http_response_code(404);
            echo '<div class="p-4 text-center text-muted">Task not found.</div>';
            return;
        }

        $isClient = false;
        if (!Auth::check()) {
            $portalToken = $request->input('portal_token');
            if ($portalToken) {
                $clientModel = new Client();
                $client = $clientModel->findByPortalToken($portalToken);
                if (!$client || (string)$client['id'] !== (string)$task['client_id']) {
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
            if ((string)$task['owner_id'] !== (string)Auth::id() && !Auth::isClient()) {
                http_response_code(403);
                echo '<div class="p-4 text-center text-danger">Access denied.</div>';
                return;
            }
        }

        $files = $this->fileModel->getByTaskId($id);
        $comments = $this->commentModel->getByTaskId($id);
        $timeEntries = $this->timeModel->getByTaskId($id, !$isClient);

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
        $ownerId = Auth::id();

        $task = $this->taskModel->findById($id);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized task update', 'UNAUTHORIZED', 403);
            return;
        }

        $title = trim($request->input('title', ''));
        if (empty($title)) {
            json_error('Title cannot be empty.', 'VALIDATION_FAILED', 422);
            return;
        }

        $this->taskModel->update($id, [
            'title' => $title,
            'description' => $request->input('description'),
            'due_date' => $request->input('due_date'),
        ]);

        json_success(['message' => 'Task updated successfully']);
    }

    public function move(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $task = $this->taskModel->findById($id);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized task move', 'UNAUTHORIZED', 403);
            return;
        }

        $stageId = (string)$request->input('stage_id');
        $position = (int)$request->input('position', 0);

        $this->taskModel->updatePositionAndStage($id, $stageId, $position);

        json_success(['message' => 'Task moved successfully']);
    }

    public function setBlocker(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $task = $this->taskModel->findById($id);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized task blocker update', 'UNAUTHORIZED', 403);
            return;
        }

        $type = $request->input('type');
        $reason = trim($request->input('reason', ''));
        $waitingOn = $request->input('waiting_on', 'client');

        if (empty($type) || $type === 'none') {
            $this->blockerModel->removeBlocker($id);
            json_success(['action' => 'removed']);
            return;
        }

        if (empty($reason)) {
            json_error('Please provide a short reason for what is blocking this task.', 'VALIDATION_FAILED', 422);
            return;
        }

        $this->blockerModel->setBlocker($id, $type, $reason, $waitingOn);

        $badgeHtml = blocker_badge([
            'type' => $type,
            'reason' => $reason,
            'waiting_on' => $waitingOn,
            'waiting_since' => date('Y-m-d H:i:s'),
        ]);

        json_success([
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
        $ownerId = Auth::id();

        $task = $this->taskModel->findById($id);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized task blocker removal', 'UNAUTHORIZED', 403);
            return;
        }

        $this->blockerModel->removeBlocker($id);
        json_success(['action' => 'removed']);
    }

    public function addComment(Request $request, string $id): void
    {
        $task = $this->taskModel->findById($id);
        if (!$task) {
            json_error('Task not found', 'NOT_FOUND', 404);
            return;
        }

        $content = trim($request->input('content', ''));
        if (empty($content)) {
            json_error('Comment content cannot be blank.', 'VALIDATION_FAILED', 422);
            return;
        }

        $isClient = false;
        $authorName = '';
        $profileId = null;

        if (Auth::check()) {
            $user = Auth::user();
            $authorName = $user['name'] ?? $user['full_name'] ?? 'User';
            $profileId = (string)$user['id'];
            $isClient = Auth::isClient();
        } else {
            $token = $request->input('portal_token');
            $clientModel = new Client();
            $client = $clientModel->findByPortalToken($token ?: '');
            if (!$client || (string)$client['id'] !== (string)$task['client_id']) {
                json_error('Unauthorized comment post', 'UNAUTHORIZED', 403);
                return;
            }
            $isClient = true;
            $authorName = $client['name'];

            // Notify freelancer
            $notif = new Notification();
            $notif->create(
                (string)$task['owner_id'],
                'client_comment',
                "New comment from {$authorName}",
                "{$authorName} commented on \"{$task['title']}\": \"" . substr($content, 0, 60) . "...\"",
                "/boards/{$task['board_id']}"
            );
        }

        $commentId = $this->commentModel->create([
            'task_id' => $id,
            'author_profile_id' => $profileId,
            'author_name' => $authorName,
            'is_client' => $isClient ? 1 : 0,
            'content' => $content,
        ]);

        json_success([
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
        $task = $this->taskModel->findById($id);
        if (!$task) {
            json_error('Task not found', 'NOT_FOUND', 404);
            return;
        }

        $isClient = false;
        $ownerId = (string)$task['owner_id'];

        if (Auth::check()) {
            $isClient = Auth::isClient();
        } else {
            $token = $request->input('portal_token');
            $clientModel = new Client();
            $client = $clientModel->findByPortalToken($token ?: '');
            if (!$client || (string)$client['id'] !== (string)$task['client_id']) {
                json_error('Unauthorized file upload', 'UNAUTHORIZED', 403);
                return;
            }
            $isClient = true;
        }

        $file = $request->file('file');
        if (!$file) {
            json_error('No file uploaded.', 'VALIDATION_FAILED', 422);
            return;
        }

        try {
            $storageService = new FileStorageService();
            $uploaded = $storageService->upload($file, $ownerId);

            $fileId = $this->fileModel->create([
                'task_id' => $id,
                'uploaded_by_profile_id' => Auth::check() ? Auth::id() : null,
                'uploaded_by_client' => $isClient ? 1 : 0,
                'original_name' => $uploaded['original_name'],
                'stored_filename' => $uploaded['stored_filename'],
                'mime_type' => $uploaded['mime_type'],
                'file_size' => $uploaded['file_size'],
                'storage_path' => $uploaded['stored_filename'],
            ]);

            if ($isClient) {
                $notif = new Notification();
                $notif->create(
                    (string)$task['owner_id'],
                    'client_upload',
                    "Client uploaded a file",
                    "{$task['client_name']} uploaded \"{$uploaded['original_name']}\" on task \"{$task['title']}\"",
                    "/boards/{$task['board_id']}"
                );
            }

            json_success([
                'file' => [
                    'id' => $fileId,
                    'name' => $uploaded['original_name'],
                    'size' => format_bytes($uploaded['file_size']),
                    'url' => "/files/{$fileId}/download" . ($isClient ? "?portal_token=" . urlencode($request->input('portal_token', '')) : ""),
                ]
            ]);
        } catch (Exception $e) {
            json_error($e->getMessage(), 'UPLOAD_FAILED', 400);
        }
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $task = $this->taskModel->findById($id);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Unauthorized task deletion', 'UNAUTHORIZED', 403);
            return;
        }

        $this->taskModel->delete($id);
        json_success(['message' => 'Task deleted successfully']);
    }
}
