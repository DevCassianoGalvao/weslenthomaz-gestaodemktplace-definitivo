<?php
/**
 * @var array $months
 * @var string|null $selectedMonth
 * @var array $rows
 * @var array $clients
 */
use App\Core\Format;

$hasAnyData = !empty($months);
$portfolioValueCents = array_sum(array_column($rows, 'total_value_cents'));
$portfolioOrders = array_sum(array_column($rows, 'total_orders'));
$portfolioTicketCents = $portfolioOrders > 0 ? (int) round($portfolioValueCents / $portfolioOrders) : null;
$activeClients = count(array_filter($rows, fn($r) => $r['total_value_cents'] > 0));

$chartLabels = [];
$chartValues = [];
$chartColors = [];
foreach ($rows as $row) {
    if ($row['total_value_cents'] > 0) {
        $chartLabels[] = $row['client_name'];
        $chartValues[] = round($row['total_value_cents'] / 100, 2);
        $chartColors[] = $row['brand_color'] ?: '#4f7fff';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comparativo entre clientes - Gestão de Marketplaces</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
    <script src="<?= url('/assets/js/dashboard-charts.js') ?>"></script>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'dashboard'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1>Comparativo entre clientes</h1>

                <?php if (!$hasAnyData): ?>
                    <div class="empty-state">Nenhum lançamento registrado ainda em nenhum cliente.</div>
                <?php else: ?>
                    <form method="get" action="<?= url('/dashboard') ?>" class="filter-bar">
                        <div class="field">
                            <label for="month">Competência</label>
                            <select id="month" name="month" onchange="fadeSubmit(this.form)">
                                <?php foreach ($months as $m): ?>
                                    <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <a href="<?= url('/dashboard/comparativo/export' . ($selectedMonth ? '?month=' . urlencode($selectedMonth) : '')) ?>" class="btn-link" style="text-decoration:none;margin-left:auto;">Exportar Excel</a>
                    </form>

                    <div class="kpi-grid" style="grid-template-columns:repeat(4, 1fr);">
                        <div class="kpi-card">
                            <div class="kpi-label">Faturamento da carteira</div>
                            <div class="kpi-value" data-countup="<?= $portfolioValueCents / 100 ?>" data-format="currency"><?= Format::centsToBrl($portfolioValueCents) ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Pedidos no mês</div>
                            <div class="kpi-value" data-countup="<?= $portfolioOrders ?>" data-format="number"><?= $portfolioOrders ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Ticket médio da carteira</div>
                            <div class="kpi-value" <?= $portfolioTicketCents !== null ? 'data-countup="' . ($portfolioTicketCents / 100) . '" data-format="currency"' : '' ?>><?= $portfolioTicketCents !== null ? Format::centsToBrl($portfolioTicketCents) : '—' ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Clientes com faturamento</div>
                            <div class="kpi-value"><?= $activeClients ?> / <?= count($rows) ?></div>
                        </div>
                    </div>

                    <div class="chart-grid full">
                        <div class="chart-card">
                            <h3>Faturamento por cliente (<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>)</h3>
                            <div id="chart-comparativo-clientes"></div>
                        </div>
                    </div>

                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Faturamento</th>
                                <th>Variação vs. mês anterior</th>
                                <th>Pedidos</th>
                                <th>Ticket médio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td class="client-name">
                                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($row['brand_color'] ?: '#4f7fff', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                        <?= htmlspecialchars($row['client_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= Format::centsToBrl($row['total_value_cents']) ?></td>
                                    <td>
                                        <?php if ($row['variation_pct'] === null): ?>
                                            —
                                        <?php else: ?>
                                            <span class="<?= $row['variation_pct'] >= 0 ? 'kpi-positive' : 'kpi-negative' ?>">
                                                <?= $row['variation_pct'] >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format($row['variation_pct'], 1, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['total_orders'] ?></td>
                                    <td><?= $row['ticket_medio_cents'] !== null ? Format::centsToBrl($row['ticket_medio_cents']) : '—' ?></td>
                                    <td><a href="<?= url('/clients/' . (int) $row['client_id'] . '/dashboard') ?>">Ver dashboard</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <script>
                        new ApexCharts(document.querySelector('#chart-comparativo-clientes'), {
                            chart: { type: 'bar', height: 300, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
                            series: [{ name: 'Faturamento', data: <?= json_encode($chartValues) ?> }],
                            xaxis: {
                                categories: <?= json_encode($chartLabels) ?>,
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: { style: { colors: '#8b93a7' } },
                            },
                            grid: { borderColor: 'rgba(255, 255, 255, 0.06)', strokeDashArray: 3 },
                            colors: <?= json_encode($chartColors) ?>.map(harmonizeChartColor),
                            plotOptions: { bar: { distributed: true, columnWidth: '50%', borderRadius: 4 } },
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            yaxis: { labels: { style: { colors: '#8b93a7' }, formatter: function (v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); } } },
                            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); } } },
                        }).render();
                        animateDashboardEntrance();
                    </script>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
