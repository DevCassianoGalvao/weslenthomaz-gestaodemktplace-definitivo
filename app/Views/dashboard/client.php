<?php
/**
 * @var array $client
 * @var array $referenceMonths
 * @var string|null $selectedMonth
 * @var string|null $from
 * @var string|null $to
 * @var array $kpis
 * @var array $monthlyTotals
 * @var array $marketplaceTotals
 * @var array $marketplaceMatrix
 * @var array $periods
 */
use App\Core\Format;

$hasAnyData = !empty($referenceMonths);

// --- Dados para os gráficos (ApexCharts) ---
$evolutionCategories = array_column($monthlyTotals, 'reference_month');
$evolutionValues = array_map(fn($r) => round(((int) $r['total_value_cents']) / 100, 2), $monthlyTotals);

$distributionLabels = [];
$distributionValues = [];
$distributionColors = [];
foreach ($marketplaceTotals as $row) {
    if ((int) $row['total_value_cents'] > 0) {
        $distributionLabels[] = $row['name'];
        $distributionValues[] = round(((int) $row['total_value_cents']) / 100, 2);
        $distributionColors[] = $row['color'] ?: '#3b82f6';
    }
}

$byMarketplace = [];
foreach ($marketplaceMatrix as $row) {
    $mid = (int) $row['marketplace_id'];
    if (!isset($byMarketplace[$mid])) {
        $byMarketplace[$mid] = [
            'name' => $row['name'],
            'color' => $row['color'] ?: '#3b82f6',
            'data' => array_fill_keys($evolutionCategories, 0),
        ];
    }
    if (array_key_exists($row['reference_month'], $byMarketplace[$mid]['data'])) {
        $byMarketplace[$mid]['data'][$row['reference_month']] = round(((int) $row['total_value_cents']) / 100, 2);
    }
}
$comparativoSeries = [];
$comparativoColors = [];
foreach ($byMarketplace as $mp) {
    $comparativoSeries[] = ['name' => $mp['name'], 'data' => array_values($mp['data'])];
    $comparativoColors[] = $mp['color'];
}

// --- Tabela detalhada agrupada por competência ---
$monthGroups = [];
foreach ($periods as $period) {
    $monthGroups[$period['reference_month']][] = $period;
}
krsort($monthGroups);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
</head>
<body>
    <?php $active = 'dashboard'; require __DIR__ . '/../partials/topbar.php'; ?>
    <div class="content">
        <h1><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if (!$hasAnyData): ?>
            <div class="empty-state">
                Nenhum lançamento registrado ainda.<br>
                Assim que a agência lançar os primeiros dados, seu painel aparece aqui.
            </div>
        <?php else: ?>

            <form method="get" action="/dashboard" class="filter-bar">
                <div class="field">
                    <label for="month">Competência</label>
                    <select id="month" name="month" onchange="this.form.submit()">
                        <?php foreach ($referenceMonths as $m): ?>
                            <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="from">De</label>
                    <input type="date" id="from" name="from" value="<?= htmlspecialchars($from ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="field">
                    <label for="to">Até</label>
                    <input type="date" id="to" name="to" value="<?= htmlspecialchars($to ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="btn" style="width:auto;">Filtrar</button>
                <a href="/dashboard" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Limpar</a>
            </form>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Faturamento do período</div>
                    <div class="kpi-value"><?= Format::centsToBrl((int) $kpis['total_value_cents']) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Variação vs. mês anterior</div>
                    <?php if ($kpis['variation_pct'] === null): ?>
                        <div class="kpi-value">—</div>
                        <div class="kpi-sub">sem dado do mês anterior</div>
                    <?php else: ?>
                        <div class="kpi-value <?= $kpis['variation_pct'] >= 0 ? 'kpi-positive' : 'kpi-negative' ?>">
                            <?= $kpis['variation_pct'] >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format($kpis['variation_pct'], 1, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%
                        </div>
                    <?php endif; ?>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Melhor desempenho</div>
                    <?php if ($kpis['best_marketplace']): ?>
                        <div class="kpi-value"><?= htmlspecialchars($kpis['best_marketplace']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="kpi-sub"><?= Format::centsToBrl((int) $kpis['best_marketplace']['total_value_cents']) ?></div>
                    <?php else: ?>
                        <div class="kpi-value">—</div>
                    <?php endif; ?>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Maior queda</div>
                    <?php if ($kpis['worst_marketplace']): ?>
                        <div class="kpi-value"><?= htmlspecialchars($kpis['worst_marketplace']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="kpi-sub kpi-negative"><?= Format::centsToBrl((int) $kpis['worst_marketplace']['total_value_cents']) ?></div>
                    <?php else: ?>
                        <div class="kpi-value">—</div>
                    <?php endif; ?>
                </div>
                <div class="kpi-card">
                    <div class="kpi-label">Ticket médio geral</div>
                    <div class="kpi-value"><?= $kpis['ticket_medio_cents'] !== null ? Format::centsToBrl((int) $kpis['ticket_medio_cents']) : '—' ?></div>
                </div>
            </div>

            <?php if (!empty($kpis['marketplace_breakdown'])): ?>
                <div class="section-title">Ticket médio por marketplace</div>
                <table style="margin-bottom:24px;">
                    <thead>
                        <tr><th>Marketplace</th><th>Faturamento</th><th>Pedidos</th><th>Ticket médio</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kpis['marketplace_breakdown'] as $row): ?>
                            <tr>
                                <td>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($row['color'] ?: '#3b82f6', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                    <?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td><?= Format::centsToBrl($row['total_value_cents']) ?></td>
                                <td><?= $row['total_orders'] ?></td>
                                <td><?= $row['ticket_medio_cents'] !== null ? Format::centsToBrl($row['ticket_medio_cents']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="chart-grid full">
                <div class="chart-card">
                    <h3>Evolução do faturamento</h3>
                    <div id="chart-evolution"></div>
                </div>
            </div>

            <div class="chart-grid">
                <div class="chart-card">
                    <h3>Distribuição por marketplace (<?= htmlspecialchars($selectedMonth ?? '', ENT_QUOTES, 'UTF-8') ?>)</h3>
                    <div id="chart-distribution"></div>
                </div>
                <div class="chart-card">
                    <h3>Comparativo mês a mês por marketplace</h3>
                    <div id="chart-comparativo"></div>
                </div>
            </div>

            <div class="section-title">Detalhamento por período</div>
            <?php foreach ($monthGroups as $refMonth => $groupPeriods): ?>
                <?php $monthTotal = array_sum(array_map(fn($p) => array_sum(array_column($p['entries'], 'value_cents')), $groupPeriods)); ?>
                <details class="month-group" <?= $refMonth === $selectedMonth ? 'open' : '' ?>>
                    <summary>
                        <span><?= htmlspecialchars($refMonth, ENT_QUOTES, 'UTF-8') ?></span>
                        <span><?= Format::centsToBrl($monthTotal) ?></span>
                    </summary>
                    <?php foreach ($groupPeriods as $period): ?>
                        <?php $periodTotal = array_sum(array_column($period['entries'], 'value_cents')); ?>
                        <div class="period-block">
                            <h4>
                                <?= htmlspecialchars(date('d/m/Y', strtotime($period['start_date'])), ENT_QUOTES, 'UTF-8') ?>
                                –
                                <?= htmlspecialchars(date('d/m/Y', strtotime($period['end_date'])), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($period['label'])): ?> · <?= htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                            </h4>
                            <table>
                                <thead>
                                    <tr><th>Marketplace</th><th>Valor</th><th>Pedidos</th><th>Participação</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($period['entries'] as $entry): ?>
                                        <tr>
                                            <td>
                                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($entry['color'] ?: '#3b82f6', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                <?= htmlspecialchars($entry['marketplace_name'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td><?= Format::centsToBrl((int) $entry['value_cents']) ?></td>
                                            <td><?= (int) $entry['orders_count'] ?></td>
                                            <td><?= $periodTotal > 0 ? number_format(($entry['value_cents'] / $periodTotal) * 100, 1, ',', '.') . '%' : '—' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </details>
            <?php endforeach; ?>

            <script src="/assets/js/dashboard-charts.js"></script>
            <script>
                renderDashboardCharts(
                    { categories: <?= json_encode($evolutionCategories) ?>, values: <?= json_encode($evolutionValues) ?> },
                    { labels: <?= json_encode($distributionLabels) ?>, values: <?= json_encode($distributionValues) ?>, colors: <?= json_encode($distributionColors) ?> },
                    { categories: <?= json_encode($evolutionCategories) ?>, series: <?= json_encode($comparativoSeries) ?>, colors: <?= json_encode($comparativoColors) ?> }
                );
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
