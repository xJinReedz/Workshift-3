<?php
/**
 * TaskFile Model (File attachments and proof of work)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TaskFile extends Model
{
    public function getByTaskId(int $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, u.name AS uploader_name
            FROM files f
            LEFT JOIN users u ON u.id = f.uploaded_by_user_id
            WHERE f.task_id = :task_id
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, t.board_id, b.client_id, b.user_id
            FROM files f
            JOIN tasks t ON t.id = f.task_id
            JOIN boards b ON b.id = t.board_id
            WHERE f.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO files (task_id, uploaded_by_user_id, uploaded_by_client, original_name, stored_filename, mime_type, file_size, created_at)
            VALUES (:task_id, :user_id, :by_client, :orig_name, :stored_name, :mime, :size, NOW())
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'user_id' => $data['uploaded_by_user_id'] ?? null,
            'by_client' => !empty($data['uploaded_by_client']) ? 1 : 0,
            'orig_name' => $data['original_name'],
            'stored_name' => $data['stored_filename'],
            'mime' => $data['mime_type'],
            'size' => $data['file_size'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM files WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
