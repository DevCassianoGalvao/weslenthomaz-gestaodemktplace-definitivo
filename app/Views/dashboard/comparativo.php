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

// Aba ativa acompanha a URL: /dashboard = Visão geral, /dashboard/comparativo = Comparativo.
$requestPath = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$isComparativoTab = str_ends_with($requestPath, '/comparativo');
$tabPath = $isComparativoTab ? '/dashboard/comparativo' : '/dashboard';
$presentationActive = $hideRevenue || $hideVariation || $showFilter !== 'all';

// Configurações do modo apresentação ficam escondidas na engrenagem da topbar (não aparecem numa reunião).
ob_start();
?>
<form method="get" action="<?= url($tabPath) ?>" class="settings-form">
    <input type="hidden" name="month" value="<?= htmlspecialchars((string) $selectedMonth, ENT_QUOTES, 'UTF-8') ?>">
    <div class="settings-title">Modo apresentação</div>
    <p class="settings-hint">Só muda o que aparece na tela. Os dados e o Excel continuam completos.</p>

    <label class="switch-row">
        <span class="switch-text"><strong>Ocultar faturamento</strong><small>Valor de cada cliente, card da carteira e gráfico</small></span>
        <span class="switch"><input type="checkbox" name="hide_revenue" value="1" <?= $hideRevenue ? 'checked' : '' ?> onchange="this.form.submit()"><span class="switch-track"></span></span>
    </label>
    <label class="switch-row">
        <span class="switch-text"><strong>Ocultar variação</strong><small>Coluna com as quedas e crescimentos</small></span>
        <span class="switch"><input type="checkbox" name="hide_variation" value="1" <?= $hideVariation ? 'checked' : '' ?> onchange="this.form.submit()"><span class="switch-track"></span></span>
    </label>

    <div class="settings-field">
        <label for="show">Mostrar clientes</label>
        <select id="show" name="show" onchange="this.form.submit()">
            <option value="all" <?= $showFilter === 'all' ? 'selected' : '' ?>>Todos os clientes</option>
            <option value="growing" <?= $showFilter === 'growing' ? 'selected' : '' ?>>Só crescendo (verde)</option>
            <option value="falling" <?= $showFilter === 'falling' ? 'selected' : '' ?>>Só em queda</option>
        </select>
    </div>

    <?php if ($presentationActive): ?>
        <a class="settings-reset" href="<?= url($tabPath . ($selectedMonth ? '?month=' . urlencode((string) $selectedMonth) : '')) ?>">Limpar tudo</a>
    <?php endif; ?>
</form>
<?php
$topbarSettingsPanel = ob_get_clean();
$topbarSettingsActive = $presentationActive;
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
                    <a href="<?= url('/dashboard') ?>" class="<?= $isComparativoTab ? '' : 'nav-active' ?>">Visão geral</a>
                    <a href="<?= url('/dashboard/comparativo') ?>" class="<?= $isComparativoTab ? 'nav-active' : '' ?>">Comparativo entre clientes</a>
                </div>
                <h1><?= $isComparativoTab ? 'Comparativo entre clientes' : 'Visão geral da carteira' ?></h1>

                <?php if (!$hasAnyData): ?>
                    <div class="empty-state">Nenhum lançamento registrado ainda em nenhum cliente.</div>
                <?php else: ?>
                    <form method="get" action="<?= url($tabPath) ?>" class="filter-bar">
                        <?php if ($hideRevenue): ?><input type="hidden" name="hide_revenue" value="1"><?php endif; ?>
                        <?php if ($hideVariation): ?><input type="hidden" name="hide_variation" value="1"><?php endif; ?>
                        <?php if ($showFilter !== 'all'): ?><input type="hidden" name="show" value="<?= htmlspecialchars($showFilter, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
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
