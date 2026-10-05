<?php
/**
 * PortalToken Model (Unguessable Client Invite Links)
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class PortalToken extends Model
{
    public function getOrCreateForClient(int $clientId): string
    {
        $stmt = $this->db->prepare("SELECT token FROM portal_tokens WHERE client_id = :client_id AND is_active = 1 LIMIT 1");
        $stmt->execute(['client_id' => $clientId]);
        $token = $stmt->fetchColumn();

        if ($token) {
            return $token;
        }

        $newToken = 'ws_' . bin2hex(random_bytes(24));
        $insert = $this->db->prepare("
            INSERT INTO portal_tokens (client_id, token, is_active, created_at)
            VALUES (:client_id, :token, 1, NOW())
            ON DUPLICATE KEY UPDATE token = VALUES(token), is_active = 1
        ");
        $insert->execute(['client_id' => $clientId, 'token' => $newToken]);

        return $newToken;
    }

    public function regenerate(int $clientId): string
    {
        $newToken = 'ws_' . bin2hex(random_bytes(24));
        $stmt = $this->db->prepare("
            UPDATE portal_tokens SET
                token = :token,
                is_active = 1,
                last_accessed_at = NULL
            WHERE client_id = :client_id
        ");
        $stmt->execute(['client_id' => $clientId, 'token' => $newToken]);
        return $newToken;
    }

    public function recordAccess(string $token): void
    {
        $stmt = $this->db->prepare("UPDATE portal_tokens SET last_accessed_at = NOW() WHERE token = :token");
        $stmt->execute(['token' => $token]);
    }
}
