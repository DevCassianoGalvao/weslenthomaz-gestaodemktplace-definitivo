<?php
/**
 * @var array $entries
 * @var array $clients
 * @var array $marketplaces
 * @var array $filters
 * @var string|null $clearError
 */
use App\Core\Csrf;
use App\Core\Format;

$exportUrl = '/history/export' . (empty($filters) ? '' : '?' . http_build_query($filters));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Histórico de alterações - Gestão de Marketplaces</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'history'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1>Histórico de alterações</h1>

                <?php if (($_GET['cleared'] ?? '') === 'client'): ?>
                    <div class="alert-success">Histórico do cliente removido com sucesso.</div>
                <?php elseif (($_GET['cleared'] ?? '') === 'all'): ?>
                    <div class="alert-success">Histórico geral removido com sucesso.</div>
                <?php endif; ?>

                <?php if (!empty($clearError)): ?>
                    <div class="alert-error"><?= htmlspecialchars($clearError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="get" action="/history" class="form-card" style="margin-bottom:24px;max-width:none;">
                    <div class="form-grid" style="grid-template-columns:repeat(5, 1fr);gap:12px;">
                        <div class="field">
                            <label for="client_id">Cliente</label>
                            <select id="client_id" name="client_id">
                                <option value="">Todos</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= (int) $client['id'] ?>" <?= (string) ($filters['client_id'] ?? '') === (string) $client['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="marketplace_id">Marketplace</label>
                            <select id="marketplace_id" name="marketplace_id">
                                <option value="">Todos</option>
                                <?php foreach ($marketplaces as $marketplace): ?>
                                    <option value="<?= (int) $marketplace['id'] ?>" <?= (string) ($filters['marketplace_id'] ?? '') === (string) $marketplace['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($marketplace['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="reference_month">Competência</label>
                            <input type="month" id="reference_month" name="reference_month" value="<?= htmlspecialchars($filters['reference_month'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="from">De</label>
                            <input type="date" id="from" name="from" value="<?= htmlspecialchars($filters['from'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="field">
                            <label for="to">Até</label>
                            <input type="date" id="to" name="to" value="<?= htmlspecialchars($filters['to'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn" style="width:auto;">Filtrar</button>
                        <a href="/history" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Limpar filtros</a>
                        <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Exportar CSV</a>
                    </div>
                </form>

                <?php if (empty($entries)): ?>
                    <p>Nenhuma alteração registrada para os filtros selecionados.</p>
                <?php else: ?>
                    <table style="margin-bottom:32px;">
                        <thead>
                            <tr>
                                <th>Quando</th>
                                <th>Cliente</th>
                                <th>Marketplace</th>
                                <th>Ação</th>
                                <th>Valor (antes → depois)</th>
                                <th>Pedidos (antes → depois)</th>
                                <th>Alterado por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($entry['changed_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($entry['client_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($entry['marketplace_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="badge-dot"></span><?= htmlspecialchars($entry['action'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $entry['old_value_cents'] !== null ? Format::centsToBrl((int) $entry['old_value_cents']) : '—' ?>
                                        →
                                        <?= Format::centsToBrl((int) $entry['new_value_cents']) ?>
                                    </td>
                                    <td>
                                        <?= $entry['old_orders_count'] !== null ? (int) $entry['old_orders_count'] : '—' ?>
                                        →
                                        <?= (int) $entry['new_orders_count'] ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['changed_by_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <div class="section-title">Limpar histórico (ação irreversível)</div>
                <p class="text-muted">A limpeza remove apenas os registros de auditoria — os lançamentos (entries) não são afetados. Exporte o CSV acima antes de confirmar.</p>

                <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:24px;">
                    <form class="form-card" method="post" action="/history/clear-client" onsubmit="return confirm('Confirma a limpeza do histórico deste cliente? Essa ação não pode ser desfeita.');">
                        <?= Csrf::field() ?>
                        <div class="section-title" style="margin-top:0;">Limpar de um cliente específico</div>
                        <div class="field">
                            <label for="clear_client_id">Cliente</label>
                            <select id="clear_client_id" name="client_id" required>
                                <option value="">Selecione…</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="confirmation_client">Digite o nome do cliente para confirmar</label>
                            <input type="text" id="confirmation_client" name="confirmation" required autocomplete="off">
                        </div>
                        <button type="submit" class="btn" style="background:var(--danger);">Limpar histórico deste cliente</button>
                    </form>

                    <form class="form-card" method="post" action="/history/clear-all" onsubmit="return confirm('Confirma a limpeza de TODO o histórico do sistema? Essa ação não pode ser desfeita.');">
                        <?= Csrf::field() ?>
                        <div class="section-title" style="margin-top:0;">Limpar histórico geral</div>
                        <p class="text-muted">Remove o histórico de todos os clientes.</p>
                        <div class="field">
                            <label for="confirmation_all">Digite <code>LIMPAR HISTORICO</code> para confirmar</label>
                            <input type="text" id="confirmation_all" name="confirmation" required autocomplete="off" placeholder="LIMPAR HISTORICO">
                        </div>
                        <button type="submit" class="btn" style="background:var(--danger);">Limpar histórico geral</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
