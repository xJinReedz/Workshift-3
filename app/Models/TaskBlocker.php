<?php
/**
 * TaskBlocker Model
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TaskBlocker extends Model
{
    public function getByTaskId(string $taskId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *, created_at AS waiting_since FROM task_blockers
            WHERE task_id = :task_id AND resolved_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['task_id' => $taskId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setBlocker(string $taskId, string $type, string $reason, string $waitingOn = 'client'): bool
    {
        $allowed = ['feedback', 'content', 'payment', 'scheduling'];
        if (!in_array(strtolower($type), $allowed)) {
            return false;
        }

        $waitingOn = in_array($waitingOn, ['client', 'freelancer']) ? $waitingOn : 'client';

        // Delete any existing active blocker for this task first
        $this->removeBlocker($taskId);

        $stmt = $this->db->prepare("
            INSERT INTO task_blockers (task_id, type, reason, waiting_on, created_at)
            VALUES (:task_id, :type, :reason, :waiting_on, CURRENT_TIMESTAMP)
        ");
        return $stmt->execute([
            'task_id' => $taskId,
            'type' => strtolower($type),
            'reason' => trim($reason),
            'waiting_on' => $waitingOn,
        ]);
    }

    public function removeBlocker(string $taskId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM task_blockers WHERE task_id = :task_id");
        return $stmt->execute(['task_id' => $taskId]);
    }

    public function getStalledBlockers(int $daysThreshold = 3): array
    {
        $stmt = $this->db->prepare("
            SELECT tb.*, tb.created_at AS waiting_since,
                   t.title AS task_title, t.board_id,
                   c.name AS client_name, c.email AS client_email, c.company_name AS client_company,
                   c.portal_token,
                   p.full_name AS freelancer_name, p.email AS freelancer_email, p.studio_name AS freelancer_company,
                   p.plan AS freelancer_plan
            FROM task_blockers tb
            JOIN tasks t ON t.id = tb.task_id
            JOIN boards b ON b.id = t.board_id
            JOIN clients c ON c.id = b.client_id
            JOIN profiles p ON p.id = c.owner_id
            WHERE tb.waiting_on = 'client'
              AND tb.resolved_at IS NULL
              AND tb.created_at <= (CURRENT_TIMESTAMP - (:days || ' days')::interval)
              AND p.plan = 'pro'
            ORDER BY tb.created_at ASC
        ");
        $stmt->execute(['days' => $daysThreshold]);
        return $stmt->fetchAll();
    }
}
