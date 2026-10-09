<?php
/**
 * TimeEntry Model (Built-in Time Tracker)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class TimeEntry extends Model
{
    public function getByTaskId(string $taskId, bool $includePrivate = true): array
    {
        $sql = "
            SELECT te.*, p.full_name AS user_name, te.description AS notes, te.start_time AS entry_date
            FROM time_entries te
            JOIN profiles p ON p.id = te.owner_id
            WHERE te.task_id = :task_id
        ";
        if (!$includePrivate) {
            $sql .= " AND te.is_private = false";
        }
        $sql .= " ORDER BY te.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    public function getByClientId(string $clientId, bool $includePrivate = true): array
    {
        $sql = "
            SELECT te.*, t.title AS task_title, te.description AS notes, te.start_time AS entry_date
            FROM time_entries te
            LEFT JOIN tasks t ON t.id = te.task_id
            WHERE te.client_id = :client_id
        ";
        if (!$includePrivate) {
            $sql .= " AND te.is_private = false";
        }
        $sql .= " ORDER BY te.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function getHoursThisWeek(string $ownerId): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(duration_minutes), 0)
            FROM time_entries
            WHERE owner_id = :owner_id
              AND start_time >= DATE_TRUNC('week', CURRENT_TIMESTAMP)
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        $totalMinutes = (int)$stmt->fetchColumn();
        return round($totalMinutes / 60, 2);
    }

    public function getRunningTimer(string $ownerId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT te.*, t.title AS task_title, c.name AS client_name, c.company_name AS client_company,
                   te.description AS notes, te.start_time AS timer_started_at
            FROM time_entries te
            LEFT JOIN tasks t ON t.id = te.task_id
            LEFT JOIN clients c ON c.id = te.client_id
            WHERE te.owner_id = :owner_id AND te.end_time IS NULL
            LIMIT 1
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function startTimer(string $taskId, string $ownerId, string $clientId, ?string $notes = null, bool $isPrivate = false): string
    {
        // Stop any running timers for this user first
        $running = $this->getRunningTimer($ownerId);
        if ($running) {
            $this->stopTimer($running['id']);
        }

        $stmt = $this->db->prepare("
            INSERT INTO time_entries (task_id, owner_id, client_id, description, start_time, duration_minutes, is_private, created_at)
            VALUES (:task_id, :owner_id, :client_id, :notes, CURRENT_TIMESTAMP, 0, :is_private, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'task_id' => $taskId,
            'owner_id' => $ownerId,
            'client_id' => $clientId,
            'notes' => $notes,
            'is_private' => $isPrivate ? 1 : 0,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function stopTimer(string $entryId): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM time_entries WHERE id = :id AND end_time IS NULL LIMIT 1");
        $stmt->execute(['id' => $entryId]);
        $entry = $stmt->fetch();
        if (!$entry || empty($entry['start_time'])) {
            return false;
        }

        $started = strtotime($entry['start_time']);
        $now = time();
        $elapsedMinutes = max(1, (int)round(($now - $started) / 60));

        $updateStmt = $this->db->prepare("
            UPDATE time_entries SET
                duration_minutes = COALESCE(duration_minutes, 0) + :elapsed,
                end_time = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $updateStmt->execute([
            'elapsed' => $elapsedMinutes,
            'id' => $entryId,
        ]);
    }

    public function logManual(array $data): string
    {
        $dateStr = !empty($data['entry_date']) ? $data['entry_date'] . ' 09:00:00' : date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            INSERT INTO time_entries (task_id, owner_id, client_id, description, start_time, end_time, duration_minutes, is_private, created_at)
            VALUES (:task_id, :owner_id, :client_id, :description, :start_time, CURRENT_TIMESTAMP, :duration_minutes, :is_private, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'task_id' => $data['task_id'],
            'owner_id' => $data['user_id'] ?? $data['owner_id'],
            'client_id' => $data['client_id'],
            'description' => !empty($data['notes']) ? trim($data['notes']) : (!empty($data['description']) ? trim($data['description']) : null),
            'start_time' => $dateStr,
            'duration_minutes' => (int)$data['duration_minutes'],
            'is_private' => !empty($data['is_private']) ? 1 : 0,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function delete(string $id, string $ownerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM time_entries WHERE id = :id AND owner_id = :owner_id");
        return $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);
    }

    public function togglePrivacy(string $id, string $ownerId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE time_entries
            SET is_private = NOT is_private
            WHERE id = :id AND owner_id = :owner_id
        ");
        return $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);
    }
}
