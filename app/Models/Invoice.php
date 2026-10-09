<?php
/**
 * Invoice Model
 * PostgreSQL + Supabase Compatibility
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use WorkShift\Core\Database;
use PDO;

class Invoice extends Model
{
    public function getByUserId(string $ownerId, ?string $status = null): array
    {
        $this->autoMarkOverdue($ownerId);

        $sql = "
            SELECT i.*, c.name AS client_name, c.company_name AS client_company, c.email AS client_email,
                   c.portal_token
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            WHERE i.owner_id = :owner_id
        ";
        $params = ['owner_id' => $ownerId];

        if (!empty($status) && in_array($status, ['draft', 'sent', 'paid', 'overdue', 'cancelled'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByClientId(string $clientId): array
    {
        $sql = "
            SELECT i.*
            FROM invoices i
            WHERE i.client_id = :client_id AND i.status != 'draft'
            ORDER BY i.created_at DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['client_id' => $clientId]);
        return $stmt->fetchAll();
    }

    public function findById(string $id, ?string $ownerId = null): ?array
    {
        $sql = "
            SELECT i.*, c.name AS client_name, c.company_name AS client_company, c.email AS client_email,
                   c.phone AS client_phone, c.notes AS client_notes, c.portal_token,
                   p.full_name AS freelancer_name, p.studio_name AS freelancer_company, p.email AS freelancer_email,
                   p.plan AS freelancer_plan
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            JOIN profiles p ON p.id = i.owner_id
            WHERE i.id = :id
        ";
        $params = ['id' => $id];
        if ($ownerId !== null) {
            $sql .= " AND i.owner_id = :owner_id";
            $params['owner_id'] = $ownerId;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }

        // Fetch items
        $itemStmt = $this->db->prepare("SELECT *, total AS amount FROM invoice_items WHERE invoice_id = :id ORDER BY id ASC");
        $itemStmt->execute(['id' => $id]);
        $invoice['items'] = $itemStmt->fetchAll();

        return $invoice;
    }

    public function generateNextInvoiceNumber(string $ownerId): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM invoices
            WHERE owner_id = :owner_id AND invoice_number ILIKE :prefix
        ");
        $stmt->execute([
            'owner_id' => $ownerId,
            'prefix' => "INV-{$year}-%",
        ]);
        $count = (int)$stmt->fetchColumn() + 1;
        return sprintf("INV-%s-%03d", $year, $count);
    }

    public function createWithItems(array $invoiceData, array $items): string
    {
        Database::beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoices (
                    owner_id, client_id, invoice_number, issue_date, due_date,
                    subtotal, tax_amount, total_amount, currency, status, notes, created_at
                ) VALUES (
                    :owner_id, :client_id, :invoice_number, :issue_date, :due_date,
                    :subtotal, :tax_amount, :total_amount, :currency, :status, :notes, CURRENT_TIMESTAMP
                )
                RETURNING id
            ");
            $stmt->execute([
                'owner_id' => $invoiceData['user_id'] ?? $invoiceData['owner_id'],
                'client_id' => $invoiceData['client_id'],
                'invoice_number' => $invoiceData['invoice_number'],
                'issue_date' => $invoiceData['issue_date'],
                'due_date' => $invoiceData['due_date'],
                'subtotal' => $invoiceData['subtotal'],
                'tax_amount' => $invoiceData['tax_amount'] ?? 0.00,
                'total_amount' => $invoiceData['total_amount'],
                'currency' => $invoiceData['currency'] ?? 'PHP',
                'status' => $invoiceData['status'] ?? 'draft',
                'notes' => $invoiceData['notes'] ?? null,
            ]);
            $invoiceId = (string)$stmt->fetchColumn();

            $itemStmt = $this->db->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, amount)
                VALUES (:invoice_id, :description, :quantity, :unit_price, :amount)
            ");

            foreach ($items as $item) {
                if (empty(trim($item['description'] ?? ''))) continue;
                $qty = (float)($item['quantity'] ?? 1);
                $unit = (float)($item['unit_price'] ?? 0);
                $amount = round($qty * $unit, 2);
                $itemStmt->execute([
                    'invoice_id' => $invoiceId,
                    'description' => trim($item['description']),
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amount,
                ]);
            }

            Database::commit();
            return $invoiceId;
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public function updateStatus(string $id, string $status, ?string $paidAt = null): bool
    {
        $allowed = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE invoices SET
                status = :status,
                paid_at = :paid_at,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'paid_at' => ($status === 'paid') ? ($paidAt ?: date('Y-m-d H:i:s')) : null,
        ]);
    }

    public function autoMarkOverdue(?string $ownerId = null): void
    {
        $sql = "
            UPDATE invoices
            SET status = 'overdue', updated_at = CURRENT_TIMESTAMP
            WHERE due_date < CURRENT_DATE
              AND status IN ('draft', 'sent')
        ";
        if ($ownerId !== null) {
            $sql .= " AND owner_id = :owner_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['owner_id' => $ownerId]);
        } else {
            $this->db->exec($sql);
        }
    }

    public function getStats(string $ownerId): array
    {
        $this->autoMarkOverdue($ownerId);

        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN status IN ('draft', 'sent', 'overdue') THEN total_amount ELSE 0 END) AS unpaid_total,
                SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END) AS overdue_total,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) AS paid_total,
                COUNT(CASE WHEN status IN ('sent', 'overdue') THEN 1 ELSE NULL END) AS unpaid_count,
                COUNT(CASE WHEN status = 'overdue' THEN 1 ELSE NULL END) AS overdue_count
            FROM invoices
            WHERE owner_id = :owner_id
        ");
        $stmt->execute(['owner_id' => $ownerId]);
        $row = $stmt->fetch();

        return [
            'unpaid_total' => (float)($row['unpaid_total'] ?? 0),
            'overdue_total' => (float)($row['overdue_total'] ?? 0),
            'paid_total' => (float)($row['paid_total'] ?? 0),
            'unpaid_count' => (int)($row['unpaid_count'] ?? 0),
            'overdue_count' => (int)($row['overdue_count'] ?? 0),
        ];
    }
}
