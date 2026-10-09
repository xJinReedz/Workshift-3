<?php
/**
 * TaskFile Model (File attachments)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TaskFile extends Model
{
    public function getByTaskId(string $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, p.full_name AS uploader_name
            FROM task_files f
            LEFT JOIN profiles p ON p.id = f.uploaded_by_profile_id
            WHERE f.task_id = :task_id
            ORDER BY f.created_at DESC
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT f.*, t.board_id, b.client_id, b.owner_id
            FROM task_files f
            JOIN tasks t ON t.id = f.task_id
            JOIN boards b ON b.id = t.board_id
            WHERE f.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO task_files (task_id, uploaded_by_profile_id, filename, original_name, mime_type, file_size, storage_path, is_client_upload, created_at)
            VALUES (:task_id, :profile_id, :filename, :original_name, :mime_type, :file_size, :storage_path, :is_client, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'profile_id' => $data['uploaded_by_profile_id'] ?? $data['user_id'] ?? null,
            'filename' => $data['stored_filename'] ?? $data['filename'],
            'original_name' => $data['original_name'],
            'mime_type' => $data['mime_type'],
            'file_size' => (int)$data['file_size'],
            'storage_path' => $data['storage_path'] ?? ($data['stored_filename'] ?? $data['filename']),
            'is_client' => !empty($data['uploaded_by_client']) || !empty($data['is_client_upload']) ? 1 : 0,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function delete(string $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM task_files WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
