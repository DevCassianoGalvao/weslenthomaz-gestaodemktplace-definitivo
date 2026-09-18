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

    /**
     * Projeta o total do mês anterior para o mesmo número de dias já decorridos no mês
     * selecionado, evitando que um mês em andamento pareça "em queda" só por estar
     * incompleto frente a um mês anterior já fechado (feedback do cliente).
     * Fora do mês corrente (competência já fechada), retorna o total anterior sem alteração.
     */
    private static function proratedPreviousTotal(string $month, string $previousMonth, int $rawPreviousTotalCents): int
    {
        $today = new DateTime('today');
        if ($today->format('Y-m') !== $month) {
            return $rawPreviousTotalCents;
        }

        $dayOfMonth = (int) $today->format('j');
        $prevMonthDate = DateTime::createFromFormat('Y-m-d', $previousMonth . '-01');
        $daysInPreviousMonth = (int) $prevMonthDate->format('t');
        $elapsedDays = min($dayOfMonth, $daysInPreviousMonth);

        if ($elapsedDays >= $daysInPreviousMonth) {
            return $rawPreviousTotalCents;
        }

        return (int) round($rawPreviousTotalCents * ($elapsedDays / $daysInPreviousMonth));
    }

    private static function isMonthInProgress(string $month): bool
    {
        return (new DateTime('today'))->format('Y-m') === $month;
    }

    /**
     * @param int[]|null $allowedMarketplaceIds restringe a marketplaces específicos (permissão
     *        de colaborador por canal); null = sem restrição.
     * @return array<int, array{reference_month:string, total_value_cents:int, total_orders:int}> ordenado asc
     */
    public static function monthlyTotals(int $clientId, ?string $from = null, ?string $to = null, ?array $allowedMarketplaceIds = null): array
    {
        [$where, $params] = self::dateRangeWhere($clientId, $from, $to);
        $adSpendSum = self::adSpendSumSql();
        [$mpJoinFilter, $mpParams] = self::marketplaceFilterSql($allowedMarketplaceIds, 'e.marketplace_id');

        $stmt = Database::connection()->prepare(
            "SELECT p.reference_month,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    {$adSpendSum} AS total_ad_spend_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM periods p
             LEFT JOIN entries e ON e.period_id = p.id{$mpJoinFilter}
             {$where}
             GROUP BY p.reference_month
             ORDER BY p.reference_month ASC"
        );
        $stmt->execute($params + $mpParams);

        return $stmt->fetchAll();
    }

    /**
     * @param int[]|null $allowedMarketplaceIds
     * @return array<int, array{id:int, name:string, color:?string, total_value_cents:int, total_orders:int}> desc por valor
     */
    public static function marketplaceTotalsForMonth(int $clientId, string $month, ?array $allowedMarketplaceIds = null): array
    {
        $adSpendSum = self::adSpendSumSql();
        [$mpWhereFilter, $mpParams] = self::marketplaceFilterSql($allowedMarketplaceIds, 'cm.marketplace_id');
        $stmt = Database::connection()->prepare(
            'SELECT m.id, m.name, m.color,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    ' . $adSpendSum . ' AS total_ad_spend_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM client_marketplaces cm
             INNER JOIN marketplaces m ON m.id = cm.marketplace_id
             LEFT JOIN periods p ON p.client_id = cm.client_id AND p.reference_month = :month
             LEFT JOIN entries e ON e.period_id = p.id AND e.marketplace_id = m.id
             WHERE cm.client_id = :client_id' . $mpWhereFilter . '
             GROUP BY m.id
             ORDER BY total_value_cents DESC'
        );
        $stmt->execute(['client_id' => $clientId, 'month' => $month] + $mpParams);

        return $stmt->fetchAll();
    }

    /**
     * @param int[]|null $allowedMarketplaceIds
     * @return array<int, array{reference_month:string, marketplace_id:int, name:string, color:?string, total_value_cents:int}>
     */
    public static function marketplaceMonthlyMatrix(int $clientId, ?string $from = null, ?string $to = null, ?array $allowedMarketplaceIds = null): array
    {
        [$where, $params] = self::dateRangeWhere($clientId, $from, $to);
        [$mpFilter, $mpParams] = self::marketplaceFilterSql($allowedMarketplaceIds, 'e.marketplace_id');

        $stmt = Database::connection()->prepare(
            "SELECT p.reference_month, m.id AS marketplace_id, m.name, m.color,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents
             FROM periods p
             INNER JOIN entries e ON e.period_id = p.id
             INNER JOIN marketplaces m ON m.id = e.marketplace_id
             {$where}{$mpFilter}
             GROUP BY p.reference_month, m.id
             ORDER BY p.reference_month ASC, m.name ASC"
        );
        $stmt->execute($params + $mpParams);

        return $stmt->fetchAll();
    }

    /**
     * Períodos com seus lançamentos aninhados, para a tabela detalhada (agrupável por mês na view).
     * @param int[]|null $allowedMarketplaceIds
     * @return array<int, array{id:int, label:?string, start_date:string, end_date:string, reference_month:string, entries:array}>
     */
    public static function periodsWithEntries(int $clientId, ?string $from = null, ?string $to = null, ?array $allowedMarketplaceIds = null): array
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
        $mpFilter = '';
        $mpValues = [];
        if (!empty($allowedMarketplaceIds)) {
            $mpValues = array_map('intval', array_values($allowedMarketplaceIds));
            $mpFilter = ' AND e.marketplace_id IN (' . implode(',', array_fill(0, count($mpValues), '?')) . ')';
        }

        $entriesStmt = Database::connection()->prepare(
            "SELECT e.period_id,
                    m.name AS marketplace_name,
                    cma.account_name,
                    m.color,
                    e.value_cents,
                    " . self::adSpendColumnSql() . ",
                    e.orders_count
             FROM entries e
             INNER JOIN marketplaces m ON m.id = e.marketplace_id
             LEFT JOIN client_marketplace_accounts cma ON cma.id = e.client_marketplace_account_id
             WHERE e.period_id IN ({$placeholders}){$mpFilter}
             ORDER BY m.name ASC, cma.account_name ASC"
        );
        $entriesStmt->execute(array_merge($periodIds, $mpValues));

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
     * @param int[]|null $allowedMarketplaceIds
     */
    public static function kpis(int $clientId, ?string $month, ?array $allowedMarketplaceIds = null): array
    {
        if ($month === null) {
            return [
                'total_value_cents' => 0,
                'total_ad_spend_cents' => 0,
                'roas' => null,
                'total_orders' => 0,
                'ticket_medio_cents' => null,
                'variation_pct' => null,
                'variation_is_projected' => false,
                'best_marketplace' => null,
                'worst_marketplace' => null,
                'marketplace_breakdown' => [],
            ];
        }

        $current = self::marketplaceTotalsForMonth($clientId, $month, $allowedMarketplaceIds);
        $totalValueCents = array_sum(array_column($current, 'total_value_cents'));
        $totalAdSpendCents = array_sum(array_column($current, 'total_ad_spend_cents'));
        $totalOrders = array_sum(array_column($current, 'total_orders'));

        $previousMonth = self::previousMonth($month);
        $hasPrevious = self::hasDataForMonth($clientId, $previousMonth);
        $previous = $hasPrevious ? self::marketplaceTotalsForMonth($clientId, $previousMonth, $allowedMarketplaceIds) : [];
        $monthInProgress = self::isMonthInProgress($month);
        $previousByMarketplace = [];
        foreach ($previous as $row) {
            $rawValue = (int) $row['total_value_cents'];
            $previousByMarketplace[(int) $row['id']] = $monthInProgress
                ? self::proratedPreviousTotal($month, $previousMonth, $rawValue)
                : $rawValue;
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
            $adSpend = (int) $row['total_ad_spend_cents'];
            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'color' => $row['color'],
                'total_value_cents' => $value,
                'total_ad_spend_cents' => $adSpend,
                'total_orders' => $orders,
                'ticket_medio_cents' => $orders > 0 ? (int) round($value / $orders) : null,
                'roas' => $adSpend > 0 ? round($value / $adSpend, 2) : null,
            ];
        }, $current);

        return [
            'total_value_cents' => $totalValueCents,
            'total_ad_spend_cents' => $totalAdSpendCents,
            'roas' => $totalAdSpendCents > 0 ? round($totalValueCents / $totalAdSpendCents, 2) : null,
            'total_orders' => $totalOrders,
            'ticket_medio_cents' => $totalOrders > 0 ? (int) round($totalValueCents / $totalOrders) : null,
            'variation_pct' => $variationPct,
            'variation_is_projected' => $monthInProgress && $hasPrevious,
            'best_marketplace' => $bestMarketplace,
            'worst_marketplace' => $worstMarketplace,
            'marketplace_breakdown' => $breakdown,
        ];
    }

    /**
     * Monta todos os dados necessários para renderizar a view dashboard/client,
     * reaproveitado tanto pela visão do cliente final quanto pelo drill-down do admin.
     * @param int[]|null $allowedMarketplaceIds restringe a visão de um colaborador a
     *        marketplaces específicos (permissão por canal); null = sem restrição.
     */
    public static function forClient(int $clientId, ?string $month, ?string $from, ?string $to, ?array $allowedMarketplaceIds = null): array
    {
        $referenceMonths = self::referenceMonths($clientId);

        if ($month === null || !in_array($month, $referenceMonths, true)) {
            $month = $referenceMonths[0] ?? null;
        }

        $kpis = self::kpis($clientId, $month, $allowedMarketplaceIds);
        $goalCents = Client::monthlyGoalCents($clientId);
        $kpis['goal_cents'] = $goalCents;
        if ($goalCents !== null && $goalCents > 0) {
            $kpis['goal_progress_pct'] = round(min(100, ($kpis['total_value_cents'] / $goalCents) * 100), 1);
            $kpis['goal_remaining_cents'] = max(0, $goalCents - $kpis['total_value_cents']);
        } else {
            $kpis['goal_progress_pct'] = null;
            $kpis['goal_remaining_cents'] = null;
        }

        return [
            'referenceMonths' => $referenceMonths,
            'selectedMonth' => $month,
            // Ads/ROAS desativado globalmente no painel — confundia a operação (feedback do cliente).
            'adsEnabled' => false,
            'from' => $from,
            'to' => $to,
            'kpis' => $kpis,
            'monthlyTotals' => self::monthlyTotals($clientId, $from, $to, $allowedMarketplaceIds),
            'marketplaceTotals' => $month ? self::marketplaceTotalsForMonth($clientId, $month, $allowedMarketplaceIds) : [],
            'marketplaceMatrix' => self::marketplaceMonthlyMatrix($clientId, $from, $to, $allowedMarketplaceIds),
            'periods' => self::periodsWithEntries($clientId, $from, $to, $allowedMarketplaceIds),
        ];
    }

    /** Meses de competência com dados em qualquer cliente — usado no filtro do comparativo. */
    public static function allReferenceMonths(): array
    {
        $stmt = Database::connection()->query(
            "SELECT DISTINCT p.reference_month
             FROM periods p
             INNER JOIN clients c ON c.id = p.client_id AND c.status = 'active'
             ORDER BY p.reference_month DESC"
        );

        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Faturamento/pedidos/ticket médio/variação de cada cliente da carteira num mês,
     * para a aba de comparativo do admin. Uma consulta por mês (atual/anterior), não por cliente.
     * @param int[]|null $allowedMarketplaceIds
     */
    public static function clientComparison(string $month, ?array $allowedMarketplaceIds = null): array
    {
        $previousMonth = self::previousMonth($month);
        $current = self::totalsByClientForMonth($month, $allowedMarketplaceIds);
        $previous = self::totalsByClientForMonth($previousMonth, $allowedMarketplaceIds);
        $monthInProgress = self::isMonthInProgress($month);

        $rows = [];
        foreach (Client::all(true, $allowedMarketplaceIds) as $client) {
            $clientId = (int) $client['id'];
            $cur = $current[$clientId] ?? ['value' => 0, 'orders' => 0];
            $prev = $previous[$clientId] ?? null;
            if ($prev && $monthInProgress) {
                $prev['value'] = self::proratedPreviousTotal($month, $previousMonth, $prev['value']);
            }

            $variation = ($prev && $prev['value'] > 0)
                ? round((($cur['value'] - $prev['value']) / $prev['value']) * 100, 1)
                : null;

            $rows[] = [
                'client_id' => $clientId,
                'client_name' => $client['name'],
                'brand_color' => $client['brand_color'],
                'total_value_cents' => $cur['value'],
                'total_orders' => $cur['orders'],
                'ticket_medio_cents' => $cur['orders'] > 0 ? (int) round($cur['value'] / $cur['orders']) : null,
                'variation_pct' => $variation,
            ];
        }

        usort($rows, fn($a, $b) => $b['total_value_cents'] <=> $a['total_value_cents']);

        return $rows;
    }

    /**
     * @param int[]|null $allowedMarketplaceIds
     * @return array<int, array{value:int, orders:int}> chave = client_id
     */
    private static function totalsByClientForMonth(string $month, ?array $allowedMarketplaceIds = null): array
    {
        [$mpJoinFilter, $mpParams] = self::marketplaceFilterSql($allowedMarketplaceIds, 'e.marketplace_id');
        $stmt = Database::connection()->prepare(
            "SELECT p.client_id,
                    COALESCE(SUM(e.value_cents), 0) AS total_value_cents,
                    COALESCE(SUM(e.orders_count), 0) AS total_orders
             FROM periods p
             LEFT JOIN entries e ON e.period_id = p.id{$mpJoinFilter}
             WHERE p.reference_month = :month
             GROUP BY p.client_id"
        );
        $stmt->execute(['month' => $month] + $mpParams);

        $totals = [];
        foreach ($stmt->fetchAll() as $row) {
            $totals[(int) $row['client_id']] = [
                'value' => (int) $row['total_value_cents'],
                'orders' => (int) $row['total_orders'],
            ];
        }

        return $totals;
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

    /**
     * Filtro SQL nomeado (" AND {column} IN (:allowed_mp_0, ...)") para restringir
     * uma consulta aos marketplaces permitidos de um colaborador restrito. Retorna
     * clausula vazia e sem params quando não há restrição.
     * @param int[]|null $allowedMarketplaceIds
     * @return array{0:string,1:array<string,int>}
     */
    private static function marketplaceFilterSql(?array $allowedMarketplaceIds, string $column): array
    {
        if (empty($allowedMarketplaceIds)) {
            return ['', []];
        }

        $params = [];
        $placeholders = [];
        foreach (array_values($allowedMarketplaceIds) as $index => $marketplaceId) {
            $key = "allowed_mp_{$index}";
            $placeholders[] = ":{$key}";
            $params[$key] = (int) $marketplaceId;
        }

        return [' AND ' . $column . ' IN (' . implode(',', $placeholders) . ')', $params];
    }

    private static function adSpendSumSql(): string
    {
        return Entry::supportsAdsSpend() ? 'COALESCE(SUM(e.ad_spend_cents), 0)' : '0';
    }

    private static function adSpendColumnSql(): string
    {
        return Entry::supportsAdsSpend() ? 'e.ad_spend_cents' : '0 AS ad_spend_cents';
    }
}
