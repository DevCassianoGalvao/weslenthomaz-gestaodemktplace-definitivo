<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cliente criado - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $active = 'clients'; require __DIR__ . '/../partials/topbar.php'; ?>
    <div class="content">
        <h1>Cliente criado com sucesso</h1>

        <div class="form-card">
            <div class="alert-success">
                Cliente <strong><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></strong> cadastrado.
            </div>

            <div class="section-title">Conta de acesso do cliente final</div>
            <p>
                <?= htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8') ?>
                &lt;<?= htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8') ?>&gt;
            </p>

            <label style="display:block;margin-bottom:6px;font-size:0.85rem;color:#94a3b8;">
                Senha gerada (copie agora — não será exibida novamente):
            </label>
            <div class="password-box">
                <span id="generated-password"><?= htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" class="btn-secondary" onclick="copyPassword()">Copiar</button>
            </div>
            <p style="color:#94a3b8;font-size:0.85rem;">Repasse a senha ao cliente pelo canal que preferir. Ele deve trocá-la no primeiro login.</p>

            <div class="form-actions">
                <a href="/clients" class="btn-link">Voltar para a lista de clientes</a>
            </div>
        </div>
    </div>

    <script>
        function copyPassword() {
            const text = document.getElementById('generated-password').textContent;
            navigator.clipboard.writeText(text);
        }
    </script>
</body>
</html>
