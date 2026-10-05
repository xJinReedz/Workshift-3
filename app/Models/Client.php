<?php
/**
 * Client Model (CRM Records)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Client extends Model
{
    public function getByUserId(int $userId, ?string $stage = null, ?string $search = null): array
    {
        $sql = "
            SELECT c.*,
                   b.id AS board_id,
                   pt.token AS portal_token,
                   (SELECT COUNT(*) FROM tasks t WHERE t.board_id = b.id) AS total_tasks,
                   (SELECT COUNT(*) FROM tasks t
                    JOIN task_blockers tb ON tb.task_id = t.id
                    WHERE t.board_id = b.id AND tb.waiting_on = 'client' AND tb.resolved_at IS NULL
                   ) AS blocked_on_client_count
            FROM clients c
            LEFT JOIN boards b ON b.client_id = c.id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE c.user_id = :user_id
        ";
        $params = ['user_id' => $userId];

        if (!empty($stage) && in_array($stage, ['inquiry', 'active', 'completed', 'archived'])) {
            $sql .= " AND c.pipeline_stage = :stage";
            $params['stage'] = $stage;
        }

        if (!empty($search)) {
            $sql .= " AND (c.name LIKE :search OR c.company LIKE :search OR c.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, b.id AS board_id, pt.token AS portal_token
            FROM clients c
            LEFT JOIN boards b ON b.client_id = c.id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE c.id = :id AND c.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPortalToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, b.id AS board_id, u.name AS freelancer_name, u.company_name AS freelancer_company,
                   u.email AS freelancer_email, u.plan AS freelancer_plan, u.scheduling_link AS freelancer_scheduling_link,
                   u.currency AS user_currency
            FROM portal_tokens pt
            JOIN clients c ON c.id = pt.client_id
            JOIN users u ON u.id = c.user_id
            LEFT JOIN boards b ON b.client_id = c.id
            WHERE pt.token = :token AND pt.is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countActiveByUserId(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM clients
            WHERE user_id = :user_id AND pipeline_stage = 'active'
        ");
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO clients (user_id, name, company, email, phone, notes, billing_type, rate, pipeline_stage, created_at)
            VALUES (:user_id, :name, :company, :email, :phone, :notes, :billing_type, :rate, :pipeline_stage, NOW())
        ");
        $stmt->execute([
            'user_id' => $data['user_id'],
            'name' => trim($data['name']),
            'company' => !empty($data['company']) ? trim($data['company']) : null,
            'email' => trim($data['email']),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'billing_type' => in_array($data['billing_type'] ?? '', ['hourly', 'fixed']) ? $data['billing_type'] : 'hourly',
            'rate' => (float)($data['rate'] ?? 0),
            'pipeline_stage' => in_array($data['pipeline_stage'] ?? '', ['inquiry', 'active', 'completed', 'archived']) ? $data['pipeline_stage'] : 'active',
        ]);
        $clientId = (int)$this->db->lastInsertId();

        // 1. Create Portal Token
        $portalTokenModel = new PortalToken();
        $portalTokenModel->getOrCreateForClient($clientId);

        // 2. Automatically create dedicated board with default stages
        $boardModel = new Board();
        $boardTitle = (!empty($data['company']) ? $data['company'] : $data['name']) . " Workspace";
        $boardModel->createDefaultBoard($clientId, (int)$data['user_id'], $boardTitle);

        return $clientId;
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients SET
                name = :name,
                company = :company,
                email = :email,
                phone = :phone,
                notes = :notes,
                billing_type = :billing_type,
                rate = :rate,
                pipeline_stage = :pipeline_stage,
                updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'name' => trim($data['name']),
            'company' => !empty($data['company']) ? trim($data['company']) : null,
            'email' => trim($data['email']),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'billing_type' => in_array($data['billing_type'] ?? '', ['hourly', 'fixed']) ? $data['billing_type'] : 'hourly',
            'rate' => (float)($data['rate'] ?? 0),
            'pipeline_stage' => in_array($data['pipeline_stage'] ?? '', ['inquiry', 'active', 'completed', 'archived']) ? $data['pipeline_stage'] : 'active',
        ]);
    }

    public function updatePipelineStage(int $id, int $userId, string $stage): bool
    {
        if (!in_array($stage, ['inquiry', 'active', 'completed', 'archived'])) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE clients SET pipeline_stage = :stage, updated_at = NOW()
            WHERE id = :id AND user_id = :user_id
        ");
        return $stmt->execute(['id' => $id, 'user_id' => $userId, 'stage' => $stage]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id AND user_id = :user_id");
        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function getPipelineCounts(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT pipeline_stage, COUNT(*) as count
            FROM clients
            WHERE user_id = :user_id
            GROUP BY pipeline_stage
        ");
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();

        $counts = ['inquiry' => 0, 'active' => 0, 'completed' => 0, 'archived' => 0];
        foreach ($rows as $r) {
            $counts[$r['pipeline_stage']] = (int)$r['count'];
        }
        return $counts;
    }
}
