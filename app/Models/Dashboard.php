<?php

namespace App\Models;

use App\Core\Database;
use DateTime;

/**
 * Consultas agregadas para o dashboard de apresentação (PRD 5.6).
 * Todo método recebe client_id explicitamente — o chamador é responsável por
 * resolvê-lo sempre a partir da sessão (nunca de input do usuário).
 */
class Dashboard
{
    public static function referenceMonths(int $clientId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT DISTINCT reference_month FROM periods WHERE client_id = :client_id ORDER BY reference_month DESC'
        );
        $stmt->execute(['client_id' => $clientId]);

        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    public static function hasDataForMonth(int $clientId, string $month): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM periods WHERE client_id = :client_id AND reference_month = :month'
        );
        $stmt->execute(['client_id' => $clientId, 'month' => $month]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public static function previousMonth(string $month): string
    {
        $dt = DateTime::createFromFormat('Y-m-d', $month . '-01');
        $dt->modify('-1 month');

        return $dt->format('Y-m');
    }

    /** @return array<int, array{reference_month:string, total_value_cents:int, total_orders:int}> ordenado asc */
    public static function monthlyTotals(int $clientId, ?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::dateRangeWhere($clientId, $from, $to);

        $stmt = Database::connection()->prepare(
            "SELECT p.reference_month,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM periods p
             LEFT JOIN entries e ON e.period_id = p.id
             {$where}
             GROUP BY p.reference_month
             ORDER BY p.reference_month ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /** @return array<int, array{id:int, name:string, color:?string, total_value_cents:int, total_orders:int}> desc por valor */
    public static function marketplaceTotalsForMonth(int $clientId, string $month): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.id, m.name, m.color,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM client_marketplaces cm
             INNER JOIN marketplaces m ON m.id = cm.marketplace_id
             LEFT JOIN periods p ON p.client_id = cm.client_id AND p.reference_month = :month
             LEFT JOIN entries e ON e.period_id = p.id AND e.marketplace_id = m.id
             WHERE cm.client_id = :client_id
             GROUP BY m.id
             ORDER BY total_value_cents DESC'
        );
        $stmt->execute(['client_id' => $clientId, 'month' => $month]);

        return $stmt->fetchAll();
    }

    /** @return array<int, array{reference_month:string, marketplace_id:int, name:string, color:?string, total_value_cents:int}> */
    public static function marketplaceMonthlyMatrix(int $clientId, ?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::dateRangeWhere($clientId, $from, $to);

        $stmt = Database::connection()->prepare(
            "SELECT p.reference_month, m.id AS marketplace_id, m.name, m.color,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents
             FROM periods p
             INNER JOIN entries e ON e.period_id = p.id
             INNER JOIN marketplaces m ON m.id = e.marketplace_id
             {$where}
             GROUP BY p.reference_month, m.id
             ORDER BY p.reference_month ASC, m.name ASC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Períodos com seus lançamentos aninhados, para a tabela detalhada (agrupável por mês na view).
     * @return array<int, array{id:int, label:?string, start_date:string, end_date:string, reference_month:string, entries:array}>
     */
    public static function periodsWithEntries(int $clientId, ?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::dateRangeWhere($clientId, $from, $to);

        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.label, p.start_date, p.end_date, p.reference_month
             FROM periods p
             {$where}
             ORDER BY p.start_date ASC"
        );
        $stmt->execute($params);
        $periods = $stmt->fetchAll();

        if (empty($periods)) {
            return [];
        }

        $periodIds = array_column($periods, 'id');
        $placeholders = implode(',', array_fill(0, count($periodIds), '?'));

        $entriesStmt = Database::connection()->prepare(
            "SELECT e.period_id, m.name AS marketplace_name, m.color, e.value_cents, e.orders_count
             FROM entries e
             INNER JOIN marketplaces m ON m.id = e.marketplace_id
             WHERE e.period_id IN ({$placeholders})
             ORDER BY m.name ASC"
        );
        $entriesStmt->execute($periodIds);

        $entriesByPeriod = [];
        foreach ($entriesStmt->fetchAll() as $row) {
            $entriesByPeriod[(int) $row['period_id']][] = $row;
        }

        foreach ($periods as &$period) {
            $period['entries'] = $entriesByPeriod[(int) $period['id']] ?? [];
        }

        return $periods;
    }

    /**
     * KPIs do mês selecionado: faturamento total, variação vs mês anterior,
     * melhor/pior desempenho por marketplace e ticket médio (geral e por canal).
     */
    public static function kpis(int $clientId, ?string $month): array
    {
        if ($month === null) {
            return [
                'total_value_cents' => 0,
                'total_orders' => 0,
                'ticket_medio_cents' => null,
                'variation_pct' => null,
                'best_marketplace' => null,
                'worst_marketplace' => null,
                'marketplace_breakdown' => [],
            ];
        }

        $current = self::marketplaceTotalsForMonth($clientId, $month);
        $totalValueCents = array_sum(array_column($current, 'total_value_cents'));
        $totalOrders = array_sum(array_column($current, 'total_orders'));

        $previousMonth = self::previousMonth($month);
        $hasPrevious = self::hasDataForMonth($clientId, $previousMonth);
        $previous = $hasPrevious ? self::marketplaceTotalsForMonth($clientId, $previousMonth) : [];
        $previousByMarketplace = [];
        foreach ($previous as $row) {
            $previousByMarketplace[(int) $row['id']] = (int) $row['total_value_cents'];
        }

        $prevTotalValueCents = array_sum($previousByMarketplace);
        $variationPct = ($hasPrevious && $prevTotalValueCents > 0)
            ? round((($totalValueCents - $prevTotalValueCents) / $prevTotalValueCents) * 100, 1)
            : null;

        $bestMarketplace = null;
        $worstMarketplace = null;
        $worstDelta = null;

        foreach ($current as $row) {
            $value = (int) $row['total_value_cents'];
            if ($value > 0 && ($bestMarketplace === null || $value > $bestMarketplace['total_value_cents'])) {
                $bestMarketplace = $row;
            }

            if ($hasPrevious && isset($previousByMarketplace[(int) $row['id']])) {
                $delta = $value - $previousByMarketplace[(int) $row['id']];
                if ($delta < 0 && ($worstDelta === null || $delta < $worstDelta)) {
                    $worstDelta = $delta;
                    $worstMarketplace = $row;
                }
            }
        }

        $breakdown = array_map(function ($row) {
            $orders = (int) $row['total_orders'];
            $value = (int) $row['total_value_cents'];
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
                'total_value_cents' => $value,
                'total_orders' => $orders,
                'ticket_medio_cents' => $orders > 0 ? (int) round($value / $orders) : null,
            ];
        }, $current);

        return [
            'total_value_cents' => $totalValueCents,
            'total_orders' => $totalOrders,
            'ticket_medio_cents' => $totalOrders > 0 ? (int) round($totalValueCents / $totalOrders) : null,
            'variation_pct' => $variationPct,
            'best_marketplace' => $bestMarketplace,
            'worst_marketplace' => $worstMarketplace,
            'marketplace_breakdown' => $breakdown,
        ];
    }

    private static function dateRangeWhere(int $clientId, ?string $from, ?string $to): array
    {
        $conditions = ['p.client_id = :client_id'];
        $params = ['client_id' => $clientId];

        if (!empty($from)) {
            $conditions[] = 'p.start_date >= :from';
            $params['from'] = $from;
        }
        if (!empty($to)) {
            $conditions[] = 'p.end_date <= :to';
            $params['to'] = $to;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}
