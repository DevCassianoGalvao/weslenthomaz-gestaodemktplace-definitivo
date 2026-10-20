<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <div class="content-header">
                    <h1>Clientes</h1>
                    <a href="<?= url('/clients/new') ?>" class="btn-link">+ Novo cliente</a>
                </div>

                <?php if (empty($clients)): ?>
                    <p>Nenhum cliente cadastrado ainda.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contas</th>
                                <th>Status</th>
                                <th>Cadastrado em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <?php $initials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $client['name']), 0, 2)) ?: '--'; ?>
                                <tr>
                                    <td>
                                        <div class="row-identity">
                                            <span class="avatar-sm" <?= !empty($client['brand_color']) ? 'style="background:' . htmlspecialchars($client['brand_color'], ENT_QUOTES, 'UTF-8') . '22;color:' . htmlspecialchars($client['brand_color'], ENT_QUOTES, 'UTF-8') . ';"' : '' ?>>
                                                <?php if (!empty($client['logo_url'])): ?>
                                                    <img src="<?= htmlspecialchars($client['logo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                                <?php else: ?>
                                                    <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                                                <?php endif; ?>
                                            </span>
                                            <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </td>
                                    <td><?= (int) $client['marketplace_count'] ?></td>
                                    <td>
                                        <?php $statusLabel = $client['status'] === 'active' ? 'Ativo' : 'Pausado'; ?>
                                        <span class="badge badge-<?= htmlspecialchars($client['status'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="badge-dot"></span><?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($client['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a href="<?= url('/clients/' . (int) $client['id'] . '/periods') ?>">Lançamentos</a>
                                        &nbsp;·&nbsp;
                                        <a href="<?= url('/clients/' . (int) $client['id'] . '/edit') ?>">Editar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
