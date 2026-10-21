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

// Modo apresentação (só visual, via query string): oculta faturamento e/ou variação e filtra a lista.
$hideRevenue = ($_GET['hide_revenue'] ?? '') === '1';
$hideVariation = ($_GET['hide_variation'] ?? '') === '1';
$showFilter = in_array($_GET['show'] ?? '', ['growing', 'falling'], true) ? $_GET['show'] : 'all';

$visibleRows = array_values(array_filter($rows, function ($r) use ($showFilter) {
    if ($showFilter === 'growing') {
        return $r['variation_pct'] !== null && $r['variation_pct'] >= 0;
    }
    if ($showFilter === 'falling') {
        return $r['variation_pct'] !== null && $r['variation_pct'] < 0;
    }
    return true;
}));

$chartLabels = [];
$chartValues = [];
$chartColors = [];
foreach ($visibleRows as $row) {
    if ($row['total_value_cents'] > 0) {
        $chartLabels[] = $row['client_name'];
        $chartValues[] = round($row['total_value_cents'] / 100, 2);
        $chartColors[] = $row['brand_color'] ?: '#d6b25e';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comparativo entre clientes - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
    <script src="<?= asset_url('/assets/js/dashboard-charts.js') ?>"></script>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'dashboard'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main"><?php require __DIR__ . '/../partials/topbar.php'; ?>
            <div class="content">
                <div class="dashboard-tabs dashboard-tabs-comparison">
                    <a href="<?= url('/dashboard') ?>">Visão geral</a>
                    <a href="<?= url('/dashboard/comparativo') ?>" class="nav-active">Comparativo entre clientes</a>
                </div>
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
                        <div class="field">
                            <label for="show">Mostrar</label>
                            <select id="show" name="show" onchange="fadeSubmit(this.form)">
                                <option value="all" <?= $showFilter === 'all' ? 'selected' : '' ?>>Todos os clientes</option>
                                <option value="growing" <?= $showFilter === 'growing' ? 'selected' : '' ?>>Só crescendo (verde)</option>
                                <option value="falling" <?= $showFilter === 'falling' ? 'selected' : '' ?>>Só em queda</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>Modo apresentação</label>
                            <div style="display:flex;flex-wrap:wrap;gap:6px 18px;padding-top:8px;">
                                <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin:0;">
                                    <input type="checkbox" name="hide_revenue" value="1" <?= $hideRevenue ? 'checked' : '' ?> onchange="fadeSubmit(this.form)">
                                    Ocultar faturamento
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-weight:400;margin:0;">
                                    <input type="checkbox" name="hide_variation" value="1" <?= $hideVariation ? 'checked' : '' ?> onchange="fadeSubmit(this.form)">
                                    Ocultar variação (quedas)
                                </label>
                            </div>
                        </div>
                        <a href="<?= url('/dashboard/comparativo/export' . ($selectedMonth ? '?month=' . urlencode($selectedMonth) : '')) ?>" class="btn-link" style="text-decoration:none;margin-left:auto;">Exportar Excel</a>
                    </form>
                    <?php if ($hideRevenue || $hideVariation || $showFilter !== 'all'): ?>
                        <p class="text-muted" style="margin:-12px 0 16px;">
                            Modo apresentação ativo — só visual, os dados reais não mudam e o Excel continua completo.
                            <a href="<?= url('/dashboard' . ($selectedMonth ? '?month=' . urlencode($selectedMonth) : '')) ?>">Limpar</a>
                        </p>
                    <?php endif; ?>

                    <div class="kpi-grid comparison-kpi-grid" <?= $hideRevenue ? 'style="grid-template-columns:repeat(3,minmax(0,1fr));"' : '' ?>>
                        <?php if (!$hideRevenue): ?>
                        <div class="kpi-card">
                            <div class="kpi-head">
                                <div class="kpi-label">Faturamento da carteira</div>
                                <span class="kpi-icon"><?= \App\Core\Icon::svg('wallet', 17) ?></span>
                            </div>
                            <div class="kpi-value" data-countup="<?= $portfolioValueCents / 100 ?>" data-format="currency"><?= Format::centsToBrl($portfolioValueCents) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="kpi-card">
                            <div class="kpi-head">
                                <div class="kpi-label">Pedidos no mês</div>
                                <span class="kpi-icon"><?= \App\Core\Icon::svg('receipt', 17) ?></span>
                            </div>
                            <div class="kpi-value" data-countup="<?= $portfolioOrders ?>" data-format="number"><?= $portfolioOrders ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-head">
                                <div class="kpi-label">Ticket médio da carteira</div>
                                <span class="kpi-icon"><?= \App\Core\Icon::svg('bar-chart', 17) ?></span>
                            </div>
                            <div class="kpi-value" <?= $portfolioTicketCents !== null ? 'data-countup="' . ($portfolioTicketCents / 100) . '" data-format="currency"' : '' ?>><?= $portfolioTicketCents !== null ? Format::centsToBrl($portfolioTicketCents) : '—' ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-head">
                                <div class="kpi-label">Clientes com faturamento</div>
                                <span class="kpi-icon kpi-icon-positive"><?= \App\Core\Icon::svg('users', 17) ?></span>
                            </div>
                            <div class="kpi-value"><?= $activeClients ?> / <?= count($rows) ?></div>
                        </div>
                    </div>

                    <?php if (!$hideRevenue): ?>
                    <div class="chart-grid full">
                        <div class="chart-card">
                            <h3>Faturamento por cliente (<?= htmlspecialchars($selectedMonth, ENT_QUOTES, 'UTF-8') ?>)</h3>
                            <div id="chart-comparativo-clientes"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <?php if (!$hideRevenue): ?><th>Faturamento</th><?php endif; ?>
                                <?php if (!$hideVariation): ?><th>Variação vs. mês anterior</th><?php endif; ?>
                                <th>Pedidos</th>
                                <th>Ticket médio</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($visibleRows)): ?>
                                <tr><td colspan="6" class="text-muted">Nenhum cliente nesse filtro.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($visibleRows as $row): ?>
                                <tr>
                                    <td class="client-name">
                                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($row['brand_color'] ?: '#d6b25e', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                        <?= htmlspecialchars($row['client_name'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <?php if (!$hideRevenue): ?><td><?= Format::centsToBrl($row['total_value_cents']) ?></td><?php endif; ?>
                                    <?php if (!$hideVariation): ?>
                                    <td>
                                        <?php if ($row['variation_pct'] === null): ?>
                                            —
                                        <?php else: ?>
                                            <span class="<?= $row['variation_pct'] >= 0 ? 'kpi-positive' : 'kpi-negative' ?>">
                                                <?= $row['variation_pct'] >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format($row['variation_pct'], 1, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td><?= $row['total_orders'] ?></td>
                                    <td><?= $row['ticket_medio_cents'] !== null ? Format::centsToBrl($row['ticket_medio_cents']) : '—' ?></td>
                                    <td><a href="<?= url('/clients/' . (int) $row['client_id'] . '/dashboard') ?>">Ver dashboard</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <script>
                        <?php if (!$hideRevenue): ?>
                        new ApexCharts(document.querySelector('#chart-comparativo-clientes'), {
                            chart: { type: 'bar', height: 340, parentHeightOffset: 0, redrawOnParentResize: true, toolbar: { show: false }, fontFamily: 'Manrope, sans-serif' },
                            series: [{ name: 'Faturamento', data: <?= json_encode($chartValues) ?> }],
                            xaxis: {
                                categories: <?= json_encode($chartLabels) ?>,
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: { style: { colors: '#8b93a7' } },
                            },
                            grid: { borderColor: 'rgba(255, 255, 255, 0.06)', strokeDashArray: 3 },
                            colors: <?= json_encode($chartColors) ?>.map(harmonizeChartColor),
                            plotOptions: { bar: { distributed: true, columnWidth: '42%', borderRadius: 3 } },
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            yaxis: { labels: { style: { colors: '#8b93a7' }, formatter: function (v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); } } },
                            tooltip: { theme: 'dark', fillSeriesColor: false, y: { formatter: function (v) { return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); } } },
                        }).render();
                        <?php endif; ?>
                        animateDashboardEntrance();
                    </script>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
