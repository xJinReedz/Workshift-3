<?php
/**
 * TaskBlocker Model (WorkShift's Signature Feature)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TaskBlocker extends Model
{
    public function getByTaskId(int $taskId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM task_blockers
            WHERE task_id = :task_id AND resolved_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['task_id' => $taskId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setBlocker(int $taskId, string $type, string $reason, string $waitingOn = 'client'): bool
    {
        $allowed = ['feedback', 'content', 'payment', 'scheduling'];
        if (!in_array(strtolower($type), $allowed)) {
            return false;
        }

        $waitingOn = in_array($waitingOn, ['client', 'freelancer']) ? $waitingOn : 'client';

        // Check if blocker already exists for this task
        $existing = $this->getByTaskId($taskId);
        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE task_blockers SET
                    type = :type,
                    reason = :reason,
                    waiting_on = :waiting_on,
                    waiting_since = NOW(),
                    resolved_at = NULL
                WHERE task_id = :task_id
            ");
            return $stmt->execute([
                'task_id' => $taskId,
                'type' => strtolower($type),
                'reason' => trim($reason),
                'waiting_on' => $waitingOn,
            ]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO task_blockers (task_id, type, reason, waiting_on, waiting_since, created_at)
            VALUES (:task_id, :type, :reason, :waiting_on, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                type = VALUES(type),
                reason = VALUES(reason),
                waiting_on = VALUES(waiting_on),
                waiting_since = VALUES(waiting_since),
                resolved_at = NULL
        ");
        return $stmt->execute([
            'task_id' => $taskId,
            'type' => strtolower($type),
            'reason' => trim($reason),
            'waiting_on' => $waitingOn,
        ]);
    }

    public function removeBlocker(int $taskId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM task_blockers WHERE task_id = :task_id");
        return $stmt->execute(['task_id' => $taskId]);
    }

    public function getStalledBlockers(int $daysThreshold = 3): array
    {
        $stmt = $this->db->prepare("
            SELECT tb.*,
                   t.title AS task_title, t.board_id,
                   c.name AS client_name, c.email AS client_email, c.company AS client_company,
                   pt.token AS portal_token,
                   u.name AS freelancer_name, u.email AS freelancer_email, u.company_name AS freelancer_company,
                   u.plan AS freelancer_plan
            FROM task_blockers tb
            JOIN tasks t ON t.id = tb.task_id
            JOIN boards b ON b.id = t.board_id
            JOIN clients c ON c.id = b.client_id
            JOIN users u ON u.id = c.user_id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE tb.waiting_on = 'client'
              AND tb.resolved_at IS NULL
              AND tb.waiting_since <= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND u.plan = 'pro'
            ORDER BY tb.waiting_since ASC
        ");
        $stmt->execute(['days' => $daysThreshold]);
        return $stmt->fetchAll();
    }
}
