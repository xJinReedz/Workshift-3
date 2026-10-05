<?php
/**
 * User Model
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class User extends Model
{
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => trim($email)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, password_hash, company_name, plan, scheduling_link, hourly_rate, currency, created_at)
            VALUES (:name, :email, :password_hash, :company_name, :plan, :scheduling_link, :hourly_rate, :currency, NOW())
        ");
        $stmt->execute([
            'name' => $data['name'],
            'email' => strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'company_name' => $data['company_name'] ?? 'Freelance Studio',
            'plan' => $data['plan'] ?? 'basic',
            'scheduling_link' => $data['scheduling_link'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? 500.00,
            'currency' => $data['currency'] ?? 'PHP',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE users SET
                name = :name,
                company_name = :company_name,
                scheduling_link = :scheduling_link,
                hourly_rate = :hourly_rate,
                currency = :currency,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? '',
            'scheduling_link' => $data['scheduling_link'] ?? null,
            'hourly_rate' => $data['hourly_rate'] ?? 500.00,
            'currency' => $data['currency'] ?? 'PHP',
        ]);
    }

    public function updatePlan(int $id, string $plan): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET plan = :plan, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id, 'plan' => $plan]);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id, 'hash' => $hash]);
    }

    public function adjustStorage(int $userId, int $deltaBytes): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
            SET storage_used_bytes = GREATEST(0, CAST(storage_used_bytes AS SIGNED) + :delta)
            WHERE id = :id
        ");
        $stmt->execute(['id' => $userId, 'delta' => $deltaBytes]);
    }

    public function isRateLimited(string $ip, string $email, int $maxAttempts = 5, int $decaySeconds = 900): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM login_attempts
            WHERE (ip_address = :ip OR LOWER(email) = LOWER(:email))
            AND attempted_at > DATE_SUB(NOW(), INTERVAL :decay SECOND)
        ");
        $stmt->execute([
            'ip' => $ip,
            'email' => trim($email),
            'decay' => $decaySeconds,
        ]);
        return (int)$stmt->fetchColumn() >= $maxAttempts;
    }

    public function recordLoginAttempt(string $ip, string $email): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, email, attempted_at)
            VALUES (:ip, :email, NOW())
        ");
        $stmt->execute(['ip' => $ip, 'email' => trim($email)]);
    }

    public function clearLoginAttempts(string $ip, string $email): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM login_attempts
            WHERE ip_address = :ip OR LOWER(email) = LOWER(:email)
        ");
        $stmt->execute(['ip' => $ip, 'email' => trim($email)]);
    }

    public function createPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("
            INSERT INTO password_resets (email, token, expires_at, created_at)
            VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW())
        ");
        $stmt->execute(['email' => strtolower(trim($email)), 'token' => $token]);
        return $token;
    }

    public function verifyPasswordResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
            WHERE token = :token AND expires_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function deletePasswordResetToken(string $token): void
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }
}
