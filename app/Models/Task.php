<?php
/**
 * Task Model
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Task extends Model
{
    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   s.title AS stage_title, s.client_review, s.is_done_stage,
                   b.title AS board_title, b.client_id, b.owner_id,
                   c.name AS client_name, c.company_name AS client_company, c.email AS client_email,
                   tb.id AS blocker_id, tb.type AS blocker_type, tb.reason AS blocker_reason,
                   tb.waiting_on AS blocker_waiting_on, tb.created_at AS blocker_waiting_since
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

    public function create(array $data): string
    {
        // Get max position in this stage
        $posStmt = $this->db->prepare("SELECT COALESCE(MAX(position), -1) + 1 FROM tasks WHERE stage_id = :stage_id");
        $posStmt->execute(['stage_id' => $data['stage_id']]);
        $nextPos = (int)$posStmt->fetchColumn();

        $stmt = $this->db->prepare("
            INSERT INTO tasks (board_id, stage_id, title, description, due_date, position, review_status, created_at)
            VALUES (:board_id, :stage_id, :title, :description, :due_date, :position, 'pending', CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'board_id' => $data['board_id'],
            'stage_id' => $data['stage_id'],
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
            'position' => $nextPos,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tasks SET
                title = :title,
                description = :description,
                due_date = :due_date,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'description' => !empty($data['description']) ? trim($data['description']) : null,
            'due_date' => !empty($data['due_date']) ? $data['due_date'] : null,
        ]);
    }

    public function updatePositionAndStage(string $id, string $stageId, int $position): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tasks SET
                stage_id = :stage_id,
                position = :position,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'stage_id' => $stageId,
            'position' => $position,
        ]);
    }

    public function updateReviewStatus(string $id, string $status, ?string $notes = null): bool
    {
        if (!in_array($status, ['pending', 'approved', 'changes_requested'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE tasks SET
                review_status = :status,
                review_notes = :notes,
                reviewed_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'notes' => $notes,
        ]);
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getBlockedTasksSummary(string $ownerId): array
    {
        $stmt = $this->db->prepare("
            SELECT t.id, t.title, t.board_id,
                   c.id AS client_id, c.name AS client_name, c.company_name AS client_company,
                   tb.type, tb.reason, tb.waiting_on, tb.created_at AS waiting_since
            FROM tasks t
            JOIN task_blockers tb ON tb.task_id = t.id AND tb.resolved_at IS NULL
            JOIN boards b ON b.id = t.board_id
            JOIN clients c ON c.id = b.client_id
            WHERE b.owner_id = :owner_id
            ORDER BY tb.created_at ASC
        ");
        $stmt->execute(['owner_id' => $ownerId]);
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
