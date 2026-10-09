<?php
/**
 * PortalToken Model (Unguessable Client Invite Links)
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class PortalToken extends Model
{
    public function getOrCreateForClient(string $clientId): string
    {
        $stmt = $this->db->prepare("SELECT portal_token FROM clients WHERE id = :client_id LIMIT 1");
        $stmt->execute(['client_id' => $clientId]);
        $token = $stmt->fetchColumn();

        if ($token) {
            return (string)$token;
        }

        $newToken = 'ws_' . bin2hex(random_bytes(24));
        $update = $this->db->prepare("UPDATE clients SET portal_token = :token WHERE id = :client_id");
        $update->execute(['client_id' => $clientId, 'token' => $newToken]);

        return $newToken;
    }

    public function regenerate(string $clientId): string
    {
        $newToken = 'ws_' . bin2hex(random_bytes(24));
        $stmt = $this->db->prepare("UPDATE clients SET portal_token = :token, updated_at = CURRENT_TIMESTAMP WHERE id = :client_id");
        $stmt->execute(['client_id' => $clientId, 'token' => $newToken]);
        return $newToken;
    }

    public function recordAccess(string $token): void
    {
        // No-op or log activity
    }
}
