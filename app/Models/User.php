<?php
/**
 * User / Profile Model (PostgreSQL + Supabase Profiles Table)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class User extends Model
{
    public function findById(string $id): ?array
    {
        if (!is_valid_uuid($id)) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT *, full_name as name, studio_name as company_name FROM profiles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT *, full_name as name, studio_name as company_name FROM profiles WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertProfile(array $data): string
    {
        $stmt = $this->db->prepare("
            INSERT INTO profiles (id, email, full_name, studio_name, default_hourly_rate, scheduling_link, plan, is_freelancer, avatar_url, updated_at)
            VALUES (:id, :email, :full_name, :studio_name, :default_hourly_rate, :scheduling_link, :plan, :is_freelancer, :avatar_url, CURRENT_TIMESTAMP)
            ON CONFLICT (id) DO UPDATE SET
                email = EXCLUDED.email,
                full_name = EXCLUDED.full_name,
                studio_name = COALESCE(EXCLUDED.studio_name, profiles.studio_name),
                default_hourly_rate = COALESCE(EXCLUDED.default_hourly_rate, profiles.default_hourly_rate),
                scheduling_link = COALESCE(EXCLUDED.scheduling_link, profiles.scheduling_link),
                updated_at = CURRENT_TIMESTAMP
            RETURNING id
        ");
        $stmt->execute([
            'id' => $data['id'],
            'email' => strtolower(trim($data['email'])),
            'full_name' => $data['full_name'] ?? $data['name'] ?? strtolower(trim($data['email'])),
            'studio_name' => $data['studio_name'] ?? $data['company_name'] ?? null,
            'default_hourly_rate' => $data['default_hourly_rate'] ?? $data['hourly_rate'] ?? 600.00,
            'scheduling_link' => $data['scheduling_link'] ?? null,
            'plan' => $data['plan'] ?? 'basic',
            'is_freelancer' => isset($data['is_freelancer']) ? (bool)$data['is_freelancer'] : true,
            'avatar_url' => $data['avatar_url'] ?? null,
        ]);
        return (string)$stmt->fetchColumn();
    }

    public function update(string $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE profiles SET
                full_name = :full_name,
                studio_name = :studio_name,
                scheduling_link = :scheduling_link,
                default_hourly_rate = :default_hourly_rate,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'full_name' => $data['name'] ?? $data['full_name'],
            'studio_name' => $data['company_name'] ?? $data['studio_name'] ?? '',
            'scheduling_link' => $data['scheduling_link'] ?? null,
            'default_hourly_rate' => $data['hourly_rate'] ?? $data['default_hourly_rate'] ?? 600.00,
        ]);
    }

    public function updatePlan(string $id, string $plan): bool
    {
        $stmt = $this->db->prepare("UPDATE profiles SET plan = :plan, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
        return $stmt->execute(['id' => $id, 'plan' => $plan]);
    }
}
