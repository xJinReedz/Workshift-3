<?php
/**
 * Invoice Model
 */

namespace WorkShift\Models;

use WorkShift\Core\Model;
use PDO;

class Invoice extends Model
{
    public function getByUserId(int $userId, ?string $status = null): array
    {
        $this->autoMarkOverdue($userId);

        $sql = "
            SELECT i.*, c.name AS client_name, c.company AS client_company, c.email AS client_email,
                   pt.token AS portal_token
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE i.user_id = :user_id
        ";
        $params = ['user_id' => $userId];

        if (!empty($status) && in_array($status, ['draft', 'sent', 'paid', 'overdue'])) {
            $sql .= " AND i.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getByClientId(int $clientId): array
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

    public function findById(int $id, ?int $userId = null): ?array
    {
        $sql = "
            SELECT i.*, c.name AS client_name, c.company AS client_company, c.email AS client_email,
                   c.phone AS client_phone, c.notes AS client_notes,
                   u.name AS freelancer_name, u.company_name AS freelancer_company, u.email AS freelancer_email,
                   u.plan AS freelancer_plan, pt.token AS portal_token
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            JOIN users u ON u.id = i.user_id
            LEFT JOIN portal_tokens pt ON pt.client_id = c.id AND pt.is_active = 1
            WHERE i.id = :id
        ";
        $params = ['id' => $id];
        if ($userId !== null) {
            $sql .= " AND i.user_id = :user_id";
            $params['user_id'] = $userId;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return null;
        }

        // Fetch items
        $itemStmt = $this->db->prepare("SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY id ASC");
        $itemStmt->execute(['id' => $id]);
        $invoice['items'] = $itemStmt->fetchAll();

        return $invoice;
    }

    public function generateNextInvoiceNumber(int $userId): string
    {
        $year = date('Y');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM invoices
            WHERE user_id = :user_id AND invoice_number LIKE :prefix
        ");
        $stmt->execute([
            'user_id' => $userId,
            'prefix' => "INV-{$year}-%",
        ]);
        $count = (int)$stmt->fetchColumn() + 1;
        return sprintf("INV-%s-%03d", $year, $count);
    }

    public function createWithItems(array $invoiceData, array $items): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO invoices (
                    user_id, client_id, invoice_number, issue_date, due_date,
                    subtotal, tax_rate, tax_amount, total_amount, currency, status, notes, created_at
                ) VALUES (
                    :user_id, :client_id, :invoice_number, :issue_date, :due_date,
                    :subtotal, :tax_rate, :tax_amount, :total_amount, :currency, :status, :notes, NOW()
                )
            ");
            $stmt->execute([
                'user_id' => $invoiceData['user_id'],
                'client_id' => $invoiceData['client_id'],
                'invoice_number' => $invoiceData['invoice_number'],
                'issue_date' => $invoiceData['issue_date'],
                'due_date' => $invoiceData['due_date'],
                'subtotal' => $invoiceData['subtotal'],
                'tax_rate' => $invoiceData['tax_rate'] ?? 0.00,
                'tax_amount' => $invoiceData['tax_amount'] ?? 0.00,
                'total_amount' => $invoiceData['total_amount'],
                'currency' => $invoiceData['currency'] ?? 'PHP',
                'status' => $invoiceData['status'] ?? 'draft',
                'notes' => $invoiceData['notes'] ?? null,
            ]);
            $invoiceId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, total)
                VALUES (:invoice_id, :description, :quantity, :unit_price, :total)
            ");

            foreach ($items as $item) {
                if (empty(trim($item['description']))) continue;
                $qty = (float)($item['quantity'] ?? 1);
                $unit = (float)($item['unit_price'] ?? 0);
                $total = round($qty * $unit, 2);
                $itemStmt->execute([
                    'invoice_id' => $invoiceId,
                    'description' => trim($item['description']),
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'total' => $total,
                ]);
            }

            $this->db->commit();
            return $invoiceId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status, ?string $paidAt = null): bool
    {
        $allowed = ['draft', 'sent', 'paid', 'overdue'];
        if (!in_array($status, $allowed)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE invoices SET
                status = :status,
                paid_at = :paid_at,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'status' => $status,
            'paid_at' => ($status === 'paid') ? ($paidAt ?: date('Y-m-d H:i:s')) : null,
        ]);
    }

    public function autoMarkOverdue(?int $userId = null): void
    {
        $sql = "
            UPDATE invoices
            SET status = 'overdue', updated_at = NOW()
            WHERE due_date < CURRENT_DATE
              AND status IN ('draft', 'sent')
        ";
        if ($userId !== null) {
            $sql .= " AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
        } else {
            $this->db->exec($sql);
        }
    }

    public function getStats(int $userId): array
    {
        $this->autoMarkOverdue($userId);

        $stmt = $this->db->prepare("
            SELECT
                SUM(CASE WHEN status IN ('draft', 'sent', 'overdue') THEN total_amount ELSE 0 END) AS unpaid_total,
                SUM(CASE WHEN status = 'overdue' THEN total_amount ELSE 0 END) AS overdue_total,
                SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) AS paid_total,
                COUNT(CASE WHEN status IN ('sent', 'overdue') THEN 1 ELSE NULL END) AS unpaid_count,
                COUNT(CASE WHEN status = 'overdue' THEN 1 ELSE NULL END) AS overdue_count
            FROM invoices
            WHERE user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
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
