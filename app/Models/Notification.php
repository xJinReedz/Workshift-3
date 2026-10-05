<?php
/**
 * Notification Model (In-app notifications for freelancer)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Notification extends Model
{
    public function getByUserId(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = 1
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function markAllAsRead(int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = 1
            WHERE user_id = :user_id AND is_read = 0
        ");
        return $stmt->execute(['user_id' => $userId]);
    }

    public function create(int $userId, string $type, string $title, string $message, ?string $linkUrl = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link_url, is_read, created_at)
            VALUES (:user_id, :type, :title, :message, :link_url, 0, NOW())
        ");
        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link_url' => $linkUrl,
        ]);
        return (int)$this->db->lastInsertId();
    }
}
