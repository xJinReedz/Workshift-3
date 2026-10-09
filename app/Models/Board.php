<?php
/**
 * Board Model (Client Board Workspace)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Board extends Model
{
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT b.*, c.name AS client_name, c.company_name AS client_company,
                   c.email AS client_email, c.portal_token
            FROM boards b
            JOIN clients c ON c.id = b.client_id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByClientId(string $clientId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM boards WHERE client_id = :client_id LIMIT 1");
        $stmt->execute(['client_id' => $clientId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createDefaultBoard(string $clientId, string $ownerId, string $title): string
    {
        $portalToken = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("
            INSERT INTO boards (client_id, owner_id, title, portal_token, created_at)
            VALUES (:client_id, :owner_id, :title, :portal_token, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'client_id' => $clientId,
            'owner_id' => $ownerId,
            'title' => $title,
            'portal_token' => $portalToken,
        ]);
        $boardId = (string)$stmt->fetchColumn();

        // Default stages: To Do, In Progress, In Review, Done
        $defaultStages = [
            ['title' => 'To Do', 'position' => 0, 'color' => '#4C9AFF', 'client_review' => false, 'is_done' => false],
            ['title' => 'In Progress', 'position' => 1, 'color' => '#F5CD47', 'client_review' => false, 'is_done' => false],
            ['title' => 'In Review', 'position' => 2, 'color' => '#9F8FEF', 'client_review' => false, 'is_done' => false],
            ['title' => 'Done', 'position' => 3, 'color' => '#57D9A3', 'client_review' => false, 'is_done' => true],
        ];

        $stageStmt = $this->db->prepare("
            INSERT INTO stages (board_id, title, position, color, client_review, is_done_stage, created_at)
            VALUES (:board_id, :title, :position, :color, :client_review, :is_done, CURRENT_TIMESTAMP)
        ");

        foreach ($defaultStages as $s) {
            $stageStmt->execute([
                'board_id' => $boardId,
                'title' => $s['title'],
                'position' => $s['position'],
                'color' => $s['color'],
                'client_review' => $s['client_review'] ? 1 : 0,
                'is_done' => $s['is_done'] ? 1 : 0,
            ]);
        }

        return $boardId;
    }

    public function getFullBoardData(string $boardId, bool $onlyWaitingOnClient = false, bool $isClientPortal = false): array
    {
        $board = $this->findById($boardId);
        if (!$board) {
            return [];
        }

        // Fetch stages
        $stageStmt = $this->db->prepare("
            SELECT * FROM stages
            WHERE board_id = :board_id
            ORDER BY position ASC, created_at ASC
        ");
        $stageStmt->execute(['board_id' => $boardId]);
        $stages = $stageStmt->fetchAll();

        // Fetch tasks
        $taskSql = "
            SELECT t.*,
                   tb.type AS blocker_type,
                   tb.reason AS blocker_reason,
                   tb.waiting_on AS blocker_waiting_on,
                   tb.created_at AS blocker_waiting_since,
                   (SELECT COUNT(*) FROM task_files f WHERE f.task_id = t.id) AS file_count,
                   (SELECT COUNT(*) FROM comments c WHERE c.task_id = t.id) AS comment_count,
                   (SELECT COALESCE(SUM(duration_minutes), 0) FROM time_entries te
                    WHERE te.task_id = t.id " . ($isClientPortal ? "AND te.is_private = false" : "") . ") AS total_time_minutes
            FROM tasks t
            LEFT JOIN task_blockers tb ON tb.task_id = t.id AND tb.resolved_at IS NULL
            WHERE t.board_id = :board_id
        ";

        if ($onlyWaitingOnClient) {
            $taskSql .= " AND tb.waiting_on = 'client' AND tb.id IS NOT NULL";
        }

        $taskSql .= " ORDER BY t.position ASC, t.created_at ASC";

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
            if (isset($tasksByStage[$task['stage_id']])) {
                $tasksByStage[$task['stage_id']][] = $task;
            }
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
