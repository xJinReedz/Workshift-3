<?php
/**
 * Time Tracker Controller
 */

namespace WorkShift\Controllers;

use WorkShift\Core\Controller;
use WorkShift\Core\Request;
use WorkShift\Helpers\Auth;
use WorkShift\Models\TimeEntry;
use WorkShift\Models\Task;

class TimeController extends Controller
{
    private TimeEntry $timeModel;
    private Task $taskModel;

    public function __construct()
    {
        parent::__construct();
        $this->timeModel = new TimeEntry();
        $this->taskModel = new Task();
    }

    public function start(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $taskId = (int)$request->input('task_id');
        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Task not found or access denied.'], 404);
            return;
        }

        $notes = $request->input('notes');
        $isPrivate = (bool)$request->input('is_private', false);

        $entryId = $this->timeModel->startTimer($taskId, $userId, (int)$task['client_id'], $notes, $isPrivate);

        $this->json([
            'success' => true,
            'entry_id' => $entryId,
            'task_title' => $task['title'],
            'client_name' => $task['client_name'],
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function stop(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $entryId = (int)$request->input('entry_id');
        if (!$entryId) {
            $running = $this->timeModel->getRunningTimer($userId);
            if ($running) {
                $entryId = (int)$running['id'];
            }
        }

        if (!$entryId) {
            $this->json(['error' => 'No active timer found.'], 404);
            return;
        }

        $this->timeModel->stopTimer($entryId);
        $this->json(['success' => true]);
    }

    public function logManual(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();

        $taskId = (int)$request->input('task_id');
        $task = $this->taskModel->findById($taskId);
        if (!$task || (int)$task['user_id'] !== $userId) {
            $this->json(['error' => 'Task not found.'], 404);
            return;
        }

        $durationMinutes = (int)$request->input('duration_minutes', 0);
        $hours = (float)$request->input('hours', 0);
        if ($hours > 0 && $durationMinutes === 0) {
            $durationMinutes = (int)round($hours * 60);
        }

        if ($durationMinutes <= 0) {
            $this->json(['error' => 'Please enter a valid duration.'], 422);
            return;
        }

        $entryId = $this->timeModel->logManual([
            'task_id' => $taskId,
            'user_id' => $userId,
            'client_id' => (int)$task['client_id'],
            'entry_date' => $request->input('entry_date', date('Y-m-d')),
            'duration_minutes' => $durationMinutes,
            'notes' => $request->input('notes'),
            'is_private' => (bool)$request->input('is_private', false),
        ]);

        $this->json([
            'success' => true,
            'entry_id' => $entryId,
            'duration_formatted' => format_duration($durationMinutes),
        ]);
    }

    public function active(Request $request): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $running = $this->timeModel->getRunningTimer($userId);

        if (!$running) {
            $this->json(['running' => false]);
            return;
        }

        $elapsedSeconds = max(0, time() - strtotime($running['timer_started_at']));

        $this->json([
            'running' => true,
            'entry_id' => $running['id'],
            'task_id' => $running['task_id'],
            'task_title' => $running['task_title'],
            'client_name' => $running['client_name'],
            'elapsed_seconds' => $elapsedSeconds,
            'started_at' => $running['timer_started_at'],
        ]);
    }

    public function togglePrivacy(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $this->timeModel->togglePrivacy((int)$id, $userId);
        $this->json(['success' => true]);
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $userId = Auth::id();
        $this->timeModel->delete((int)$id, $userId);
        $this->json(['success' => true]);
    }
}
