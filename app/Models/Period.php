<?php

namespace App\Models;

use App\Core\Database;

class Period
{
    public static function allForClient(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.label, p.start_date, p.end_date, p.reference_month, p.created_at,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM periods p
             LEFT JOIN entries e ON e.period_id = p.id
             WHERE p.client_id = :client_id
             GROUP BY p.id
             ORDER BY p.start_date DESC, p.id DESC'
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM periods WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $period = $stmt->fetch();

        return $period ?: null;
    }

    public static function create(int $clientId, ?string $label, string $startDate, string $endDate, string $referenceMonth, int $createdBy): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO periods (client_id, label, start_date, end_date, reference_month, created_by)
             VALUES (:client_id, :label, :start_date, :end_date, :reference_month, :created_by)'
        );
        $stmt->execute([
            'client_id' => $clientId,
            'label' => $label ?: null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reference_month' => $referenceMonth,
            'created_by' => $createdBy,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, ?string $label, string $startDate, string $endDate, string $referenceMonth): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE periods SET label = :label, start_date = :start_date, end_date = :end_date, reference_month = :reference_month
             WHERE id = :id'
        );
        $stmt->execute([
            'label' => $label ?: null,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reference_month' => $referenceMonth,
            'id' => $id,
        ]);
    }

    public static function suggestReferenceMonth(string $startDate): string
    {
        return substr($startDate, 0, 7);
    }
}
