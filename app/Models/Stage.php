<?php
/**
 * Stage Model (Board Columns)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Stage extends Model
{
    public function getByBoardId(int $boardId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM stages WHERE board_id = :board_id ORDER BY position ASC, id ASC");
        $stmt->execute(['board_id' => $boardId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM stages WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO stages (board_id, title, position, is_review_stage, is_done_stage, created_at)
            VALUES (:board_id, :title, :position, :is_review, :is_done, NOW())
        ");
        $stmt->execute([
            'board_id' => $data['board_id'],
            'title' => trim($data['title']),
            'position' => (int)($data['position'] ?? 0),
            'is_review' => !empty($data['is_review_stage']) ? 1 : 0,
            'is_done' => !empty($data['is_done_stage']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE stages SET
                title = :title,
                is_review_stage = :is_review,
                is_done_stage = :is_done
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'is_review' => !empty($data['is_review_stage']) ? 1 : 0,
            'is_done' => !empty($data['is_done_stage']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM stages WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function reorder(int $boardId, array $stageIds): void
    {
        $stmt = $this->db->prepare("UPDATE stages SET position = :pos WHERE id = :id AND board_id = :board_id");
        foreach ($stageIds as $pos => $id) {
            $stmt->execute(['pos' => $pos, 'id' => (int)$id, 'board_id' => $boardId]);
        }
    }
}
