<?php
/**
 * Task Model
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Task extends Model
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   s.title AS stage_title, s.is_review_stage, s.is_done_stage,
                   b.title AS board_title, b.client_id,
                   c.name AS client_name, c.company AS client_company, c.billing_type, c.rate AS client_rate,
                   c.user_id AS client_user_id,
                   tb.id AS blocker_id, tb.type AS blocker_type, tb.reason AS blocker_reason,
                   tb.waiting_on AS blocker_waiting_on, tb.waiting_since AS blocker_waiting_since
            FROM tasks t
            JOIN stages s ON s.id = t.stage_id
            JOIN boards b ON b.id = t.board_id
            JOIN clients c ON c.id = b.client_id
            LEFT JOIN task_blockers tb ON tb.task_id = t.id AND tb.resolved_at IS NULL
            WHERE t.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        // Get max position in this stage
        $posStmt = $this->db->prepare("SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE stage_id = :stage_id");
        $posStmt->execute(['stage_id' => $data['stage_id']]);
        $nextPos = (int)$posStmt->fetchColumn();

        $stmt = $this->db->prepare("
            INSERT INTO tasks (board_id, stage_id, user_id, title, description, due_date, position, review_status, created_at)
            VALUES (:board_id, :stage_id, :user_id, :title, :description, :due_date, :position, 'pending', NOW())
        ");
        $stmt->execute([
            'board_id' => $data['board_id'],
            'stage_id' => $data['stage_id'],
            'user_id' => $data['user_id'],
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            'position' => $nextPos,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tasks SET
                title = :title,
                description = :description,
                due_date = :due_date,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
        ]);
    }

    public function updatePositionAndStage(int $id, int $stageId, int $position): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tasks SET
                stage_id = :stage_id,
                position = :position,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'stage_id' => $stageId,
            'position' => $position,
        ]);
    }

    public function updateReviewStatus(int $id, string $status, ?string $notes = null): bool
    {
        if (!in_array($status, ['pending', 'approved', 'changes_requested'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE tasks SET
                review_status = :status,
                review_notes = :notes,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getBlockedTasksSummary(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT t.id, t.title, t.board_id,
                   c.id AS client_id, c.name AS client_name, c.company AS client_company,
                   tb.type, tb.reason, tb.waiting_on, tb.waiting_since
            FROM tasks t
            JOIN task_blockers tb ON tb.task_id = t.id AND tb.resolved_at IS NULL
            JOIN boards b ON b.id = t.board_id
            JOIN clients c ON c.id = b.client_id
            WHERE t.user_id = :user_id
            ORDER BY tb.waiting_since ASC
        ");
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $waitingOnClient = [];
        $waitingOnYou = [];

        foreach ($rows as $r) {
            if ($r['waiting_on'] === 'client') {
                $waitingOnClient[] = $r;
            } else {
                $waitingOnYou[] = $r;
            }
        }

        return [
            'waitingOnClient' => $waitingOnClient,
            'waitingOnYou' => $waitingOnYou,
            'totalBlocked' => count($rows),
        ];
    }
}
