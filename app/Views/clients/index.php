<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $active = 'clients'; require __DIR__ . '/../partials/topbar.php'; ?>
    <div class="content">
        <div class="content-header">
            <h1>Clientes</h1>
            <a href="/clients/new" class="btn-link">+ Novo cliente</a>
        </div>

        <?php if (empty($clients)): ?>
            <p>Nenhum cliente cadastrado ainda.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Marketplaces</th>
                        <th>Status</th>
                        <th>Cadastrado em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td>
                                <?php if (!empty($client['brand_color'])): ?>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($client['brand_color'], ENT_QUOTES, 'UTF-8') ?>;margin-right:6px;"></span>
                                <?php endif; ?>
                                <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= (int) $client['marketplace_count'] ?></td>
                            <td><span class="badge badge-<?= htmlspecialchars($client['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($client['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($client['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><a href="/clients/<?= (int) $client['id'] ?>/edit">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
