<?php
/**
 * Board Model
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Board extends Model
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, c.name AS client_name, c.company AS client_company, c.billing_type, c.rate AS client_rate,
                   c.email AS client_email, pt.token AS portal_token
            FROM boards b
            JOIN clients c ON c.id = b.client_id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByClientId(int $clientId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM boards WHERE client_id = :client_id LIMIT 1");
        $stmt->execute(['client_id' => $clientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createDefaultBoard(int $clientId, int $userId, string $title): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO boards (client_id, user_id, title, created_at)
            VALUES (:client_id, :user_id, :title, NOW())
        ");
        $stmt->execute([
            'client_id' => $clientId,
            'user_id' => $userId,
            'title' => $title,
        ]);
        $boardId = (int)$this->db->lastInsertId();

        // Create default stages: To Do, In Progress, In Review, Done
        $defaultStages = [
            ['title' => 'To Do', 'position' => 0, 'is_review' => 0, 'is_done' => 0],
            ['title' => 'In Progress', 'position' => 1, 'is_review' => 0, 'is_done' => 0],
            ['title' => 'In Review', 'position' => 2, 'is_review' => 1, 'is_done' => 0],
            ['title' => 'Done', 'position' => 3, 'is_review' => 0, 'is_done' => 1],
        ];

        $stageStmt = $this->db->prepare("
            INSERT INTO stages (board_id, title, position, is_review_stage, is_done_stage, created_at)
            VALUES (:board_id, :title, :position, :is_review, :is_done, NOW())
        ");

        foreach ($defaultStages as $s) {
            $stageStmt->execute([
                'board_id' => $boardId,
                'title' => $s['title'],
                'position' => $s['position'],
                'is_review' => $s['is_review'],
                'is_done' => $s['is_done'],
            ]);
        }

        return $boardId;
    }

    public function getFullBoardData(int $boardId, bool $onlyWaitingOnClient = false, bool $isClientPortal = false): array
    {
        $board = $this->findById($boardId);
        if (!$board) {
            return [];
        }

        // Fetch stages
        $stageStmt = $this->db->prepare("
            SELECT * FROM stages
            WHERE board_id = :board_id
            ORDER BY position ASC, id ASC
        ");
        $stageStmt->execute(['board_id' => $boardId]);
        $stages = $stageStmt->fetchAll();

        // Fetch tasks
        $taskSql = "
            SELECT t.*,
                   tb.type AS blocker_type,
                   tb.reason AS blocker_reason,
                   tb.waiting_on AS blocker_waiting_on,
                   tb.waiting_since AS blocker_waiting_since,
                   (SELECT COUNT(*) FROM files f WHERE f.task_id = t.id) AS file_count,
                   (SELECT COUNT(*) FROM comments c WHERE c.task_id = t.id) AS comment_count,
                   (SELECT COALESCE(SUM(duration_minutes), 0) FROM time_entries te
                    WHERE te.task_id = t.id " . ($isClientPortal ? "AND te.is_private = 0" : "") . ") AS total_time_minutes
            FROM tasks t
            LEFT JOIN task_blockers tb ON tb.task_id = t.id AND tb.resolved_at IS NULL
            WHERE t.board_id = :board_id
        ";

        if ($onlyWaitingOnClient) {
            $taskSql .= " AND tb.waiting_on = 'client' AND tb.id IS NOT NULL";
        }

        $taskSql .= " ORDER BY t.position ASC, t.id ASC";

        $taskStmt = $this->db->prepare($taskSql);
        $taskStmt->execute(['board_id' => $boardId]);
        $tasks = $taskStmt->fetchAll();

        // Group tasks by stage
        $tasksByStage = [];
        $waitingOnClientCount = 0;
        $waitingOnYouCount = 0;

        foreach ($stages as $s) {
            $tasksByStage[$s['id']] = [];
        }

        foreach ($tasks as $task) {
            if (!empty($task['blocker_type'])) {
                if ($task['blocker_waiting_on'] === 'client') {
                    $waitingOnClientCount++;
                } else {
                    $waitingOnYouCount++;
                }
            }
            $tasksByStage[$task['stage_id']][] = $task;
        }

        return [
            'board' => $board,
            'stages' => $stages,
            'tasksByStage' => $tasksByStage,
            'waitingOnClientCount' => $waitingOnClientCount,
            'waitingOnYouCount' => $waitingOnYouCount,
        ];
    }
}
