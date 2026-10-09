<?php
/**
 * Time Tracker Controller
 * PostgreSQL + Supabase Compatibility
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
        $ownerId = Auth::id();

        $taskId = (string)$request->input('task_id');
        $task = $this->taskModel->findById($taskId);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Task not found or access denied.', 'NOT_FOUND', 404);
            return;
        }

        $notes = $request->input('notes');
        $isPrivate = (bool)$request->input('is_private', false);

        $entryId = $this->timeModel->startTimer($taskId, $ownerId, (string)$task['client_id'], $notes, $isPrivate);

        json_success([
            'entry_id' => $entryId,
            'task_title' => $task['title'],
            'client_name' => $task['client_name'],
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function stop(Request $request): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $entryId = $request->input('entry_id');
        if (!$entryId) {
            $running = $this->timeModel->getRunningTimer($ownerId);
            if ($running) {
                $entryId = (string)$running['id'];
            }
        }

        if (!$entryId) {
            json_error('No active timer found.', 'NOT_FOUND', 404);
            return;
        }

        $this->timeModel->stopTimer((string)$entryId);
        json_success(['message' => 'Timer stopped']);
    }

    public function logManual(Request $request): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();

        $taskId = (string)$request->input('task_id');
        $task = $this->taskModel->findById($taskId);
        if (!$task || (string)$task['owner_id'] !== (string)$ownerId) {
            json_error('Task not found.', 'NOT_FOUND', 404);
            return;
        }

        $durationMinutes = (int)$request->input('duration_minutes', 0);
        $hours = (float)$request->input('hours', 0);
        if ($hours > 0 && $durationMinutes === 0) {
            $durationMinutes = (int)round($hours * 60);
        }

        if ($durationMinutes <= 0) {
            json_error('Please enter a valid duration.', 'VALIDATION_FAILED', 422);
            return;
        }

        $entryId = $this->timeModel->logManual([
            'task_id' => $taskId,
            'owner_id' => $ownerId,
            'client_id' => (string)$task['client_id'],
            'entry_date' => $request->input('entry_date', date('Y-m-d')),
            'duration_minutes' => $durationMinutes,
            'notes' => $request->input('notes'),
            'is_private' => (bool)$request->input('is_private', false),
        ]);

        json_success([
            'entry_id' => $entryId,
            'duration_formatted' => format_duration($durationMinutes),
        ]);
    }

    public function active(Request $request): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();
        $running = $this->timeModel->getRunningTimer($ownerId);

        if (!$running) {
            json_success(['running' => false]);
            return;
        }

        $elapsedSeconds = max(0, time() - strtotime($running['timer_started_at']));

        json_success([
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
        $ownerId = Auth::id();
        $this->timeModel->togglePrivacy($id, $ownerId);
        json_success(['message' => 'Privacy setting updated']);
    }

    public function delete(Request $request, string $id): void
    {
        $this->requireAuth();
        $ownerId = Auth::id();
        $this->timeModel->delete($id, $ownerId);
        json_success(['message' => 'Time entry deleted']);
    }
}
