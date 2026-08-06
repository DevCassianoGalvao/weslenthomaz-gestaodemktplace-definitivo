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
 * @var bool $isInternal
 * @var array|null $allClients
 */
use App\Core\Format;

$isInternal = $isInternal ?? false;
$hasAnyData = !empty($referenceMonths);
$dashboardUrl = $isInternal ? url('/clients/' . (int) $client['id'] . '/dashboard') : url('/dashboard');
$currentFilters = array_filter(['month' => $selectedMonth, 'from' => $from, 'to' => $to]);
$exportUrl = $dashboardUrl . '/export' . (empty($currentFilters) ? '' : '?' . http_build_query($currentFilters));

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
        $distributionColors[] = $row['color'] ?: '#d6b25e';
    }
}

$byMarketplace = [];
foreach ($marketplaceMatrix as $row) {
    $mid = (int) $row['marketplace_id'];
    if (!isset($byMarketplace[$mid])) {
        $byMarketplace[$mid] = [
            'name' => $row['name'],
            'color' => $row['color'] ?: '#d6b25e',
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

$accentColor = (!empty($client['brand_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $client['brand_color']))
    ? $client['brand_color']
    : '#d6b25e';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?> · Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
    <?php require __DIR__ . '/../partials/brand-style.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3/dist/ScrollTrigger.min.js"></script>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'dashboard'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content dashboard-page client-dashboard">
                <?php if ($isInternal): ?>
                    <div class="dashboard-tabs">
                        <a href="<?= url('/clients/' . (int) $client['id'] . '/dashboard') ?>" class="nav-active">Visão do cliente</a>
                        <a href="<?= url('/dashboard') ?>">Comparativo entre clientes</a>
                    </div>
                    <div class="content-header client-heading">
                        <div>
                            <div class="eyebrow">Painel de métricas <span>·</span> visão do cliente</div>
                            <h1><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        </div>
                        <form method="get" action="">
                            <select name="client_id" onchange="fadeNavigate('<?= url('/clients') ?>/' + this.value + '/dashboard')">
                                <?php foreach ($allClients as $c): ?>
                                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $client['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="client-heading">
                        <div class="eyebrow">Painel de métricas <span>·</span> acompanhamento mensal</div>
                        <h1><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>
                <?php endif; ?>

                <?php
                    $profileLinks = array_filter([
                        'Site' => $client['website_url'] ?? null,
                        'Instagram' => $client['instagram_url'] ?? null,
                        'Facebook' => $client['facebook_url'] ?? null,
                        'TikTok' => $client['tiktok_url'] ?? null,
                    ]);
                ?>
                <?php $clientInitials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $client['name']), 0, 2)) ?: 'WT'; ?>
                <section class="client-hero" aria-label="Identidade do cliente">
                    <div class="client-hero-mark">
                        <?php if (!empty($client['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($client['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="Logo de <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <span><?= htmlspecialchars($clientInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="client-hero-copy">
                        <h2><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($profileLinks) || !empty($client['whatsapp'])): ?>
                            <div class="client-links">
                                <?php foreach ($profileLinks as $label => $href): ?>
                                    <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                                <?php endforeach; ?>
                                <?php if (!empty($client['whatsapp'])): ?><span><?= htmlspecialchars($client['whatsapp'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="client-hero-rule" aria-hidden="true"></div>
                </section>

                <?php if (!$hasAnyData): ?>
                    <div class="empty-state">
                        Nenhum lançamento registrado ainda.<br>
                        Assim que a agência lançar os primeiros dados, seu painel aparece aqui.
                    </div>
                <?php else: ?>

                    <form method="get" action="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="filter-bar" id="dashboard-filters">
                        <div class="field">
                            <label for="month">Competência</label>
                            <select id="month" name="month" onchange="fadeSubmit(this.form)">
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
                        <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Limpar</a>
                        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-link" style="text-decoration:none;margin-left:auto;">Exportar Excel</a>
                    </form>

                    <div class="kpi-grid">
                        <div class="kpi-card">
                            <div class="kpi-label">Faturamento do período</div>
                            <div class="kpi-value" data-countup="<?= (int) $kpis['total_value_cents'] / 100 ?>" data-format="currency"><?= Format::centsToBrl((int) $kpis['total_value_cents']) ?></div>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Variação vs. mês anterior</div>
                            <?php if ($kpis['variation_pct'] === null): ?>
                                <div class="kpi-value">—</div>
                                <div class="kpi-sub">sem dado do mês anterior</div>
                            <?php else: ?>
                                <div class="kpi-value <?= $kpis['variation_pct'] >= 0 ? 'kpi-positive' : 'kpi-negative' ?>" data-countup="<?= $kpis['variation_pct'] ?>" data-format="percent">
                                    <?= $kpis['variation_pct'] >= 0 ? '+' : '' ?><?= htmlspecialchars(number_format($kpis['variation_pct'], 1, ',', '.'), ENT_QUOTES, 'UTF-8') ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Melhor desempenho</div>
                            <?php if ($kpis['best_marketplace']): ?>
                                <div class="kpi-value"><?= htmlspecialchars($kpis['best_marketplace']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="kpi-sub" data-countup="<?= (int) $kpis['best_marketplace']['total_value_cents'] / 100 ?>" data-format="currency"><?= Format::centsToBrl((int) $kpis['best_marketplace']['total_value_cents']) ?></div>
                            <?php else: ?>
                                <div class="kpi-value">—</div>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Maior queda</div>
                            <?php if ($kpis['worst_marketplace']): ?>
                                <div class="kpi-value"><?= htmlspecialchars($kpis['worst_marketplace']['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="kpi-sub kpi-negative" data-countup="<?= (int) $kpis['worst_marketplace']['total_value_cents'] / 100 ?>" data-format="currency"><?= Format::centsToBrl((int) $kpis['worst_marketplace']['total_value_cents']) ?></div>
                            <?php else: ?>
                                <div class="kpi-value">—</div>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-card">
                            <div class="kpi-label">Ticket médio geral</div>
                            <div class="kpi-value" <?= $kpis['ticket_medio_cents'] !== null ? 'data-countup="' . ((int) $kpis['ticket_medio_cents'] / 100) . '" data-format="currency"' : '' ?>><?= $kpis['ticket_medio_cents'] !== null ? Format::centsToBrl((int) $kpis['ticket_medio_cents']) : '—' ?></div>
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
                                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($row['color'] ?: '#d6b25e', ENT_QUOTES, 'UTF-8') ?>;"></span>
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
                                            <tr><th>Marketplace</th><th>Conta</th><th>Valor</th><th>Pedidos</th><th>Participação</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($period['entries'] as $entry): ?>
                                                <tr>
                                                    <td>
                                                        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:<?= htmlspecialchars($entry['color'] ?: '#d6b25e', ENT_QUOTES, 'UTF-8') ?>;"></span>
                                                        <?= htmlspecialchars($entry['marketplace_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($entry['account_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
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

                    <script src="<?= asset_url('/assets/js/dashboard-charts.js') ?>"></script>
                    <script>
                        renderDashboardCharts(
                            { categories: <?= json_encode($evolutionCategories) ?>, values: <?= json_encode($evolutionValues) ?> },
                            { labels: <?= json_encode($distributionLabels) ?>, values: <?= json_encode($distributionValues) ?>, colors: <?= json_encode($distributionColors) ?> },
                            { categories: <?= json_encode($evolutionCategories) ?>, series: <?= json_encode($comparativoSeries) ?>, colors: <?= json_encode($comparativoColors) ?> },
                            <?= json_encode($accentColor) ?>
                        );
                        animateDashboardEntrance();
                    </script>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
