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
     * Grava os lançamentos de um período (uma linha por marketplace) e registra em
     * entry_history todo lançamento cujo valor ou nº de pedidos realmente mudou
     * (linhas não tocadas, que permanecem em 0/0, não geram ruído no histórico).
     *
     * @param array<int, array{value_cents:int, orders_count:int}> $rows chave = marketplace_id
     */
    public static function saveBatch(int $periodId, int $clientId, array $rows, int $changedBy): void
    {
        $pdo = Database::connection();

        $before = self::forPeriod($periodId);

        // ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id) força o MySQL a devolver o id
        // existente em lastInsertId() mesmo quando a linha já existia (update, não insert).
        $upsert = $pdo->prepare(
            'INSERT INTO entries (period_id, marketplace_id, value_cents, orders_count)
             VALUES (:period_id, :marketplace_id, :value_cents, :orders_count)
             ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                value_cents = VALUES(value_cents),
                orders_count = VALUES(orders_count)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($rows as $marketplaceId => $row) {
                $marketplaceId = (int) $marketplaceId;
                $newValueCents = (int) $row['value_cents'];
                $newOrdersCount = (int) $row['orders_count'];

                $existing = $before[$marketplaceId] ?? null;
                $oldValueCents = $existing['value_cents'] ?? 0;
                $oldOrdersCount = $existing['orders_count'] ?? 0;

                $upsert->execute([
                    'period_id' => $periodId,
                    'marketplace_id' => $marketplaceId,
                    'value_cents' => $newValueCents,
                    'orders_count' => $newOrdersCount,
                ]);

                $changed = $oldValueCents !== $newValueCents || $oldOrdersCount !== $newOrdersCount;
                if (!$changed) {
                    continue;
                }

                $entryId = (int) $pdo->lastInsertId();

                EntryHistory::record($pdo, [
                    'entry_id' => $entryId,
                    'period_id' => $periodId,
                    'client_id' => $clientId,
                    'marketplace_id' => $marketplaceId,
                    'action' => $existing === null ? 'create' : 'update',
                    'old_value_cents' => $existing === null ? null : $oldValueCents,
                    'new_value_cents' => $newValueCents,
                    'old_orders_count' => $existing === null ? null : $oldOrdersCount,
                    'new_orders_count' => $newOrdersCount,
                    'changed_by' => $changedBy,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
