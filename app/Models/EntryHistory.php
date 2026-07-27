<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class EntryHistory
{
    public static function record(PDO $pdo, array $data): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO entry_history
                (entry_id, period_id, client_id, client_marketplace_account_id, marketplace_id, action, old_value_cents, new_value_cents, old_orders_count, new_orders_count, changed_by)
             VALUES
                (:entry_id, :period_id, :client_id, :client_marketplace_account_id, :marketplace_id, :action, :old_value_cents, :new_value_cents, :old_orders_count, :new_orders_count, :changed_by)'
        );
        $stmt->execute([
            'entry_id' => $data['entry_id'],
            'period_id' => $data['period_id'],
            'client_id' => $data['client_id'],
            'client_marketplace_account_id' => $data['client_marketplace_account_id'] ?? null,
            'marketplace_id' => $data['marketplace_id'],
            'action' => $data['action'],
            'old_value_cents' => $data['old_value_cents'],
            'new_value_cents' => $data['new_value_cents'],
            'old_orders_count' => $data['old_orders_count'],
            'new_orders_count' => $data['new_orders_count'],
            'changed_by' => $data['changed_by'],
        ]);
    }

    /**
     * @param array{client_id?:int, marketplace_id?:int, reference_month?:string, from?:string, to?:string} $filters
     */
    public static function list(array $filters = [], int $limit = 500): array
    {
        [$where, $params] = self::buildWhere($filters);

        $stmt = Database::connection()->prepare(
            "SELECT eh.*, c.name AS client_name, m.name AS marketplace_name, cma.account_name, u.name AS changed_by_name,
                    p.reference_month, p.label AS period_label
             FROM entry_history eh
             INNER JOIN clients c ON c.id = eh.client_id
             INNER JOIN marketplaces m ON m.id = eh.marketplace_id
             LEFT JOIN client_marketplace_accounts cma ON cma.id = eh.client_marketplace_account_id
             INNER JOIN users u ON u.id = eh.changed_by
             LEFT JOIN periods p ON p.id = eh.period_id
             {$where}
             ORDER BY eh.changed_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private static function buildWhere(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['client_id'])) {
            $conditions[] = 'eh.client_id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }
        if (!empty($filters['marketplace_id'])) {
            $conditions[] = 'eh.marketplace_id = :marketplace_id';
            $params['marketplace_id'] = (int) $filters['marketplace_id'];
        }
        if (!empty($filters['reference_month'])) {
            $conditions[] = 'p.reference_month = :reference_month';
            $params['reference_month'] = $filters['reference_month'];
        }
        if (!empty($filters['from'])) {
            $conditions[] = 'eh.changed_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $conditions[] = 'eh.changed_at <= :to';
            $params['to'] = $filters['to'] . ' 23:59:59';
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    public static function clearForClient(int $clientId): int
    {
        $stmt = Database::connection()->prepare('DELETE FROM entry_history WHERE client_id = :client_id');
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->rowCount();
    }

    public static function clearAll(): int
    {
        $stmt = Database::connection()->query('DELETE FROM entry_history');

        return $stmt->rowCount();
    }
}
