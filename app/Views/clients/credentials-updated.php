<?php use App\Core\Icon; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Senha atualizada - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1>Senha atualizada</h1>
                <div class="form-card" style="max-width:620px;">
                    <div class="alert-success" style="display:flex;align-items:center;gap:8px;">
                        <?= Icon::svg('check-circle', 18) ?>
                        Nova senha salva para <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?>.
                    </div>
                    <p><strong>E-mail:</strong> <?= htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8') ?></p>
                    <label class="text-muted" for="updated-password">Copie esta senha agora. Ela não será exibida novamente.</label>
                    <div class="password-box">
                        <span id="updated-password"><?= htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('updated-password').textContent)">Copiar</button>
                    </div>
                    <div class="form-actions">
                        <a href="<?= url('/clients/' . (int) $client['id'] . '/edit') ?>" class="btn-link">Voltar para o cliente</a>
                        <a href="<?= url('/clients') ?>" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Lista de clientes</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
