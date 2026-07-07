<?php

namespace App\Models;

use App\Core\Database;

class Entry
{
    /** @return array<int, array{value_cents:int, orders_count:int}> chave = marketplace_id */
    public static function forPeriod(int $periodId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT marketplace_id, value_cents, orders_count FROM entries WHERE period_id = :period_id'
        );
        $stmt->execute(['period_id' => $periodId]);

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            $entries[(int) $row['marketplace_id']] = [
                'value_cents' => (int) $row['value_cents'],
                'orders_count' => (int) $row['orders_count'],
            ];
        }

        return $entries;
    }

    /**
     * Grava os lançamentos de um período (uma linha por marketplace).
     * @param array<int, array{value_cents:int, orders_count:int}> $rows chave = marketplace_id
     */
    public static function saveBatch(int $periodId, array $rows): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO entries (period_id, marketplace_id, value_cents, orders_count)
             VALUES (:period_id, :marketplace_id, :value_cents, :orders_count)
             ON DUPLICATE KEY UPDATE value_cents = VALUES(value_cents), orders_count = VALUES(orders_count)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($rows as $marketplaceId => $row) {
                $stmt->execute([
                    'period_id' => $periodId,
                    'marketplace_id' => (int) $marketplaceId,
                    'value_cents' => (int) $row['value_cents'],
                    'orders_count' => (int) $row['orders_count'],
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
