<?php
/**
 * Notification Model
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Notification extends Model
{
    public function getByUserId(string $recipientProfileId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT *, link AS link_url FROM notifications
            WHERE recipient_profile_id = :recipient_profile_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':recipient_profile_id', $recipientProfileId, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUnreadCount(string $recipientProfileId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notifications
            WHERE recipient_profile_id = :recipient_profile_id AND is_read = false
        ");
        $stmt->execute(['recipient_profile_id' => $recipientProfileId]);
        return (int)$stmt->fetchColumn();
    }

    public function markAsRead(string $id, string $recipientProfileId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = true
            WHERE id = :id AND recipient_profile_id = :recipient_profile_id
        ");
        return $stmt->execute(['id' => $id, 'recipient_profile_id' => $recipientProfileId]);
    }

    public function markAllAsRead(string $recipientProfileId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = true
            WHERE recipient_profile_id = :recipient_profile_id AND is_read = false
        ");
        return $stmt->execute(['recipient_profile_id' => $recipientProfileId]);
    }

    public function create(string $recipientProfileId, string $type, string $title, string $message, ?string $linkUrl = null): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (recipient_profile_id, type, title, message, link, is_read, created_at)
            VALUES (:recipient_profile_id, :type, :title, :message, :link, false, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'recipient_profile_id' => $recipientProfileId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $linkUrl,
        ]);
        return (string)$stmt->fetchColumn();
    }
}
