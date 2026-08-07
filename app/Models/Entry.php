<?php

namespace App\Models;

use App\Core\Database;

class Entry
{
    private static ?bool $adsSpendSupported = null;

    public static function supportsAdsSpend(): bool
    {
        if (self::$adsSpendSupported !== null) {
            return self::$adsSpendSupported;
        }

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
        );
        $stmt->execute(['table_name' => 'entries', 'column_name' => 'ad_spend_cents']);

        return self::$adsSpendSupported = (int) $stmt->fetchColumn() > 0;
    }

    /** @return array<int, array{value_cents:int, ad_spend_cents:int, orders_count:int}> chave = client_marketplace_account_id */
    public static function forPeriod(int $periodId): array
    {
        $adSpendColumn = self::supportsAdsSpend() ? 'ad_spend_cents' : '0 AS ad_spend_cents';
        $stmt = Database::connection()->prepare(
            'SELECT COALESCE(client_marketplace_account_id, marketplace_id) AS row_id, value_cents, ' . $adSpendColumn . ', orders_count
             FROM entries
             WHERE period_id = :period_id'
        );
        $stmt->execute(['period_id' => $periodId]);

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            $entries[(int) $row['row_id']] = [
                'value_cents' => (int) $row['value_cents'],
                'ad_spend_cents' => (int) $row['ad_spend_cents'],
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
     * @param array<int, array{value_cents:int, orders_count:int, marketplace_id?:int}> $rows chave = client_marketplace_account_id
     */
    public static function saveBatch(int $periodId, int $clientId, array $rows, int $changedBy): void
    {
        $pdo = Database::connection();
        $adsSpendSupported = self::supportsAdsSpend();

        $before = self::forPeriod($periodId);

        // ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id) força o MySQL a devolver o id
        // existente em lastInsertId() mesmo quando a linha já existia (update, não insert).
        $upsert = $pdo->prepare($adsSpendSupported
            ? 'INSERT INTO entries (period_id, client_marketplace_account_id, marketplace_id, value_cents, ad_spend_cents, orders_count)
               VALUES (:period_id, :client_marketplace_account_id, :marketplace_id, :value_cents, :ad_spend_cents, :orders_count)
               ON DUPLICATE KEY UPDATE
                  id = LAST_INSERT_ID(id),
                  marketplace_id = VALUES(marketplace_id),
                  value_cents = VALUES(value_cents),
                  ad_spend_cents = VALUES(ad_spend_cents),
                  orders_count = VALUES(orders_count)'
            : 'INSERT INTO entries (period_id, client_marketplace_account_id, marketplace_id, value_cents, orders_count)
               VALUES (:period_id, :client_marketplace_account_id, :marketplace_id, :value_cents, :orders_count)
               ON DUPLICATE KEY UPDATE
                  id = LAST_INSERT_ID(id),
                  marketplace_id = VALUES(marketplace_id),
                  value_cents = VALUES(value_cents),
                  orders_count = VALUES(orders_count)'
        );

        $pdo->beginTransaction();
        try {
            foreach ($rows as $accountId => $row) {
                $accountId = (int) $accountId;
                $marketplaceId = (int) ($row['marketplace_id'] ?? 0);
                $newValueCents = (int) $row['value_cents'];
                $newAdSpendCents = max(0, (int) ($row['ad_spend_cents'] ?? 0));
                $newOrdersCount = (int) $row['orders_count'];

                $existing = $before[$accountId] ?? null;
                $oldValueCents = $existing['value_cents'] ?? 0;
                $oldAdSpendCents = $existing['ad_spend_cents'] ?? 0;
                $oldOrdersCount = $existing['orders_count'] ?? 0;

                $params = [
                    'period_id' => $periodId,
                    'client_marketplace_account_id' => $accountId,
                    'marketplace_id' => $marketplaceId,
                    'value_cents' => $newValueCents,
                    'orders_count' => $newOrdersCount,
                ];
                if ($adsSpendSupported) {
                    $params['ad_spend_cents'] = $newAdSpendCents;
                }
                $upsert->execute($params);

                $changed = $oldValueCents !== $newValueCents
                    || $oldOrdersCount !== $newOrdersCount
                    || ($adsSpendSupported && $oldAdSpendCents !== $newAdSpendCents);
                if (!$changed) {
                    continue;
                }

                $entryId = (int) $pdo->lastInsertId();

                $historyData = [
                    'entry_id' => $entryId,
                    'period_id' => $periodId,
                    'client_id' => $clientId,
                    'client_marketplace_account_id' => $accountId,
                    'marketplace_id' => $marketplaceId,
                    'action' => $existing === null ? 'create' : 'update',
                    'old_value_cents' => $existing === null ? null : $oldValueCents,
                    'new_value_cents' => $newValueCents,
                    'old_orders_count' => $existing === null ? null : $oldOrdersCount,
                    'new_orders_count' => $newOrdersCount,
                    'changed_by' => $changedBy,
                ];
                if ($adsSpendSupported) {
                    $historyData['old_ad_spend_cents'] = $existing === null ? null : $oldAdSpendCents;
                    $historyData['new_ad_spend_cents'] = $newAdSpendCents;
                }
                EntryHistory::record($pdo, $historyData);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
