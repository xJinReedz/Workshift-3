<?php
/**
 * Stage Model (Board Columns - Independent of Client Approval)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use WorkShift\Core\Database;
use PDO;

class Stage extends Model
{
    public function getByBoardId(string $boardId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM stages WHERE board_id = :board_id ORDER BY position ASC, created_at ASC");
        $stmt->execute(['board_id' => $boardId]);
        return $stmt->fetchAll();
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM stages WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO stages (board_id, title, position, color, client_review, is_done_stage, created_at)
            VALUES (:board_id, :title, :position, :color, :client_review, :is_done, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'board_id' => $data['board_id'],
            'title' => trim($data['title'] ?? $data['name']),
            'position' => (int)($data['position'] ?? 0),
            'color' => $data['color'] ?? '#4C9AFF',
            'client_review' => !empty($data['client_review']) || !empty($data['is_review_stage']) ? 1 : 0,
            'is_done' => !empty($data['is_done_stage']) || !empty($data['is_done']) ? 1 : 0,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE stages SET
                title = :title,
                color = :color,
                client_review = :client_review,
                is_done_stage = :is_done,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title'] ?? $data['name']),
            'color' => $data['color'] ?? '#4C9AFF',
            'client_review' => !empty($data['client_review']) || !empty($data['is_review_stage']) ? 1 : 0,
            'is_done' => !empty($data['is_done_stage']) || !empty($data['is_done']) ? 1 : 0,
        ]);
    }

    public function deleteWithTaskMove(string $stageId, ?string $destinationStageId = null): bool
    {
        Database::beginTransaction();
        try {
            if ($destinationStageId && $destinationStageId !== $stageId) {
                $stmt = $this->db->prepare("UPDATE tasks SET stage_id = :dest WHERE stage_id = :source");
                $stmt->execute(['dest' => $destinationStageId, 'source' => $stageId]);
            }

            $stmt = $this->db->prepare("DELETE FROM stages WHERE id = :id");
            $stmt->execute(['id' => $stageId]);

            Database::commit();
            return true;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function reorder(string $boardId, array $stageIds): void
    {
        Database::beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE stages SET position = :pos WHERE id = :id AND board_id = :board_id");
            foreach ($stageIds as $pos => $id) {
                $stmt->execute(['pos' => $pos, 'id' => (string)$id, 'board_id' => $boardId]);
            }
            Database::commit();
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
