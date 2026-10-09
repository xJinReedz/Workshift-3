<?php
/**
 * Comment Model
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Comment extends Model
{
    public function getByTaskId(string $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM comments
            WHERE task_id = :task_id
            ORDER BY created_at ASC
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO comments (task_id, author_profile_id, author_name, is_client, content, created_at)
            VALUES (:task_id, :author_profile_id, :author_name, :is_client, :content, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'author_profile_id' => $data['author_profile_id'] ?? $data['user_id'] ?? null,
            'author_name' => trim($data['author_name']),
            'is_client' => !empty($data['is_client']) ? 1 : 0,
            'content' => trim($data['content']),
        ]);
        return (string)$stmt->fetchColumn();
    }
}
