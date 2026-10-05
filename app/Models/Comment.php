<?php
/**
 * Comment Model
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Comment extends Model
{
    public function getByTaskId(int $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*
            FROM comments c
            WHERE c.task_id = :task_id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO comments (task_id, user_id, client_id, author_name, is_client, content, created_at)
            VALUES (:task_id, :user_id, :client_id, :author_name, :is_client, :content, NOW())
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'user_id' => $data['user_id'] ?? null,
            'client_id' => $data['client_id'] ?? null,
            'author_name' => trim($data['author_name']),
            'is_client' => !empty($data['is_client']) ? 1 : 0,
            'content' => trim($data['content']),
        ]);
        return (int)$this->db->lastInsertId();
    }
}
