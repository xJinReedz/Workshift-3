<?php
/**
 * TimeEntry Model (Built-in Time Tracker)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TimeEntry extends Model
{
    public function getByTaskId(int $taskId, bool $includePrivate = true): array
    {
        $sql = "
            SELECT te.*, u.name AS user_name
            FROM time_entries te
            JOIN users u ON u.id = te.user_id
            WHERE te.task_id = :task_id
        ";
        if (!$includePrivate) {
            $sql .= " AND te.is_private = 0";
        }
        $sql .= " ORDER BY te.entry_date DESC, te.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function getByClientId(int $clientId, bool $includePrivate = true): array
    {
        $sql = "
            SELECT te.*, t.title AS task_title
            FROM time_entries te
            JOIN tasks t ON t.id = te.task_id
            WHERE te.client_id = :client_id
        ";
        if (!$includePrivate) {
            $sql .= " AND te.is_private = 0";
        }
        $sql .= " ORDER BY te.entry_date DESC, te.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getHoursThisWeek(int $userId): float
    {
        // Monday of current week
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(duration_minutes), 0)
            FROM time_entries
            WHERE user_id = :user_id
              AND entry_date >= DATE_SUB(CURRENT_DATE, INTERVAL WEEKDAY(CURRENT_DATE) DAY)
        ");
        $stmt->execute(['user_id' => $userId]);
        $totalMinutes = (int)$stmt->fetchColumn();
        return round($totalMinutes / 60, 2);
    }

    public function getRunningTimer(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT te.*, t.title AS task_title, c.name AS client_name, c.company AS client_company
            FROM time_entries te
            JOIN tasks t ON t.id = te.task_id
            JOIN clients c ON c.id = te.client_id
            WHERE te.user_id = :user_id AND te.is_running = 1
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function startTimer(int $taskId, int $userId, int $clientId, ?string $notes = null, bool $isPrivate = false): int
    {
        // Stop any running timers for this user first
        $running = $this->getRunningTimer($userId);
        if ($running) {
            $this->stopTimer($running['id']);
        }

        $stmt = $this->db->prepare("
            INSERT INTO time_entries (task_id, user_id, client_id, entry_date, duration_minutes, notes, is_private, is_running, timer_started_at, created_at)
            VALUES (:task_id, :user_id, :client_id, CURRENT_DATE, 0, :notes, :is_private, 1, NOW(), NOW())
        ");
        $stmt->execute([
            'task_id' => $taskId,
            'user_id' => $userId,
            'client_id' => $clientId,
            'notes' => $notes,
            'is_private' => $isPrivate ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function stopTimer(int $entryId): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM time_entries WHERE id = :id AND is_running = 1 LIMIT 1");
        $stmt->execute(['id' => $entryId]);
        $entry = $stmt->fetch();
        if (!$entry || empty($entry['timer_started_at'])) {
            return false;
        }

        $started = strtotime($entry['timer_started_at']);
        $now = time();
        $elapsedMinutes = max(1, (int)round(($now - $started) / 60));

        $updateStmt = $this->db->prepare("
            UPDATE time_entries SET
                duration_minutes = duration_minutes + :elapsed,
                is_running = 0,
                timer_started_at = NULL
            WHERE id = :id
        ");
        return $updateStmt->execute([
            'elapsed' => $elapsedMinutes,
            'id' => $entryId,
        ]);
    }

    public function logManual(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO time_entries (task_id, user_id, client_id, entry_date, duration_minutes, notes, is_private, is_running, created_at)
            VALUES (:task_id, :user_id, :client_id, :entry_date, :duration_minutes, :notes, :is_private, 0, NOW())
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'user_id' => $data['user_id'],
            'client_id' => $data['client_id'],
            'entry_date' => $data['entry_date'] ?: date('Y-m-d'),
            'duration_minutes' => (int)$data['duration_minutes'],
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'is_private' => !empty($data['is_private']) ? 1 : 0,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM time_entries WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function togglePrivacy(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE time_entries
            SET is_private = CASE WHEN is_private = 1 THEN 0 ELSE 1 END
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
