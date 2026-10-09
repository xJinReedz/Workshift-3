<?php
/**
 * Client Model (CRM Records + Client Accounts)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Client extends Model
{
    public function getByUserId(string $ownerId, ?string $status = null, ?string $search = null): array
    {
        $sql = "
            SELECT c.*,
                   c.company_name AS company,
                   c.status AS pipeline_stage,
                   c.hourly_rate AS rate,
                   b.id AS board_id,
                   c.portal_token,
                   (SELECT COUNT(*) FROM tasks t WHERE t.board_id = b.id) AS total_tasks,
                   (SELECT COUNT(*) FROM tasks t
                    JOIN task_blockers tb ON tb.task_id = t.id
                    WHERE t.board_id = b.id AND tb.waiting_on = 'client' AND tb.resolved_at IS NULL
                   ) AS blocked_on_client_count
            FROM clients c
            LEFT JOIN boards b ON b.client_id = c.id
            WHERE c.owner_id = :owner_id
        ";
        $params = ['owner_id' => $ownerId];

        if (!empty($status) && in_array($status, ['active', 'archived', 'lead'])) {
            $sql .= " AND c.status = :status";
            $params['status'] = $status;
        }

        if (!empty($search)) {
            $sql .= " AND (c.name ILIKE :search OR c.company_name ILIKE :search OR c.email ILIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(string $id, string $ownerId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, c.company_name AS company, c.status AS pipeline_stage, c.hourly_rate AS rate,
                   b.id AS board_id
            FROM clients c
            LEFT JOIN boards b ON b.client_id = c.id
            WHERE c.id = :id AND c.owner_id = :owner_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByPortalToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, c.company_name AS company, c.status AS pipeline_stage, c.hourly_rate AS rate,
                   b.id AS board_id,
                   p.full_name AS freelancer_name, p.studio_name AS freelancer_company,
                   p.email AS freelancer_email, p.plan AS freelancer_plan, p.scheduling_link AS freelancer_scheduling_link
            FROM clients c
            JOIN profiles p ON p.id = c.owner_id
            LEFT JOIN boards b ON b.client_id = c.id
            WHERE c.portal_token = :token
            LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByProfileId(string $profileId): array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, c.company_name AS company, c.status AS pipeline_stage,
                   b.id AS board_id,
                   p.full_name AS freelancer_name, p.studio_name AS freelancer_company
            FROM clients c
            JOIN profiles p ON p.id = c.owner_id
            LEFT JOIN boards b ON b.client_id = c.id
            WHERE c.profile_id = :profile_id OR LOWER(c.email) = (SELECT LOWER(email) FROM profiles WHERE id = :profile_id)
        ");
        $stmt->execute(['profile_id' => $profileId]);
        return $stmt->fetchAll();
    }

    public function countActiveByUserId(string $ownerId): int
    {
        if (!is_valid_uuid($ownerId)) {
            return 0;
        }
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM clients
            WHERE owner_id = :owner_id AND status = 'active'
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        return (int)$stmt->fetchColumn();
    }

    public function create(array $data): string
    {
        $portalToken = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("
            INSERT INTO clients (owner_id, name, company_name, email, phone, notes, hourly_rate, currency, status, portal_token, created_at)
            VALUES (:owner_id, :name, :company_name, :email, :phone, :notes, :hourly_rate, :currency, :status, :portal_token, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        $stmt->execute([
            'owner_id' => $data['user_id'] ?? $data['owner_id'],
            'name' => trim($data['name']),
            'company_name' => !empty($data['company']) ? trim($data['company']) : (!empty($data['company_name']) ? trim($data['company_name']) : null),
            'email' => trim($data['email']),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'hourly_rate' => (float)($data['rate'] ?? $data['hourly_rate'] ?? 0),
            'currency' => $data['currency'] ?? 'PHP',
            'status' => in_array($data['pipeline_stage'] ?? $data['status'] ?? '', ['active', 'archived', 'lead']) ? ($data['pipeline_stage'] ?? $data['status']) : 'active',
            'portal_token' => $portalToken,
        ]);
        $clientId = (string)$stmt->fetchColumn();

        // Create dedicated board with default stages
        $boardModel = new Board();
        $boardTitle = (!empty($data['company']) ? $data['company'] : (!empty($data['company_name']) ? $data['company_name'] : $data['name'])) . " Workspace";
        $boardModel->createDefaultBoard($clientId, (string)($data['user_id'] ?? $data['owner_id']), $boardTitle);

        return $clientId;
    }

    public function update(string $id, string $ownerId, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE clients SET
                name = :name,
                company_name = :company_name,
                email = :email,
                phone = :phone,
                notes = :notes,
                hourly_rate = :hourly_rate,
                status = :status,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND owner_id = :owner_id
        ");
        return $stmt->execute([
            'id' => $id,
            'owner_id' => $ownerId,
            'name' => trim($data['name']),
            'company_name' => !empty($data['company']) ? trim($data['company']) : (!empty($data['company_name']) ? trim($data['company_name']) : null),
            'email' => trim($data['email']),
            'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
            'notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            'hourly_rate' => (float)($data['rate'] ?? $data['hourly_rate'] ?? 0),
            'status' => in_array($data['pipeline_stage'] ?? $data['status'] ?? '', ['active', 'archived', 'lead']) ? ($data['pipeline_stage'] ?? $data['status']) : 'active',
        ]);
    }

    public function updatePipelineStage(string $id, string $ownerId, string $status): bool
    {
        if (!in_array($status, ['active', 'archived', 'lead'])) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE clients SET status = :status, updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND owner_id = :owner_id
        ");
        return $stmt->execute(['id' => $id, 'owner_id' => $ownerId, 'status' => $status]);
    }

    public function linkProfile(string $id, string $profileId): bool
    {
        $stmt = $this->db->prepare("UPDATE clients SET profile_id = :profile_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id, 'profile_id' => $profileId]);
    }

    public function delete(string $id, string $ownerId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = :id AND owner_id = :owner_id");
        return $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);
    }

    public function getPipelineCounts(string $ownerId): array
    {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) as count
            FROM clients
            WHERE owner_id = :owner_id
            GROUP BY status
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        $rows = $stmt->fetchAll();

        $counts = ['active' => 0, 'lead' => 0, 'archived' => 0, 'inquiry' => 0, 'completed' => 0];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int)$r['count'];
        }
        return $counts;
    }
}
