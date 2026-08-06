<?php use App\Core\Icon; ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cliente criado - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1>Cliente criado com sucesso</h1>

                <div class="form-card" id="created-card">
                    <div class="alert-success" style="display:flex;align-items:center;gap:8px;">
                        <span id="created-check"><?= Icon::svg('check-circle', 18) ?></span>
                        Cliente <strong><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></strong> cadastrado.
                    </div>

                    <div class="section-title">Conta de acesso do cliente final</div>
                    <p>
                        <?= htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8') ?>
                        &lt;<?= htmlspecialchars($accountEmail, ENT_QUOTES, 'UTF-8') ?>&gt;
                    </p>

                    <label class="text-muted" style="display:block;margin-bottom:6px;">
                        Senha gerada (copie agora — não será exibida novamente):
                    </label>
                    <div class="password-box">
                        <span id="generated-password"><?= htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button" class="btn-secondary" onclick="copyPassword()">Copiar</button>
                    </div>
                    <p class="text-muted">Repasse a senha ao cliente pelo canal que preferir. Ele deve trocá-la no primeiro login.</p>

                    <div class="form-actions">
                        <a href="<?= url('/clients') ?>" class="btn-link">Voltar para a lista de clientes</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function copyPassword() {
            const text = document.getElementById('generated-password').textContent;
            navigator.clipboard.writeText(text);
        }
        if (typeof gsap !== 'undefined') {
            gsap.from('#created-card', { opacity: 0, y: 16, scale: 0.98, duration: 0.5, ease: 'back.out(1.4)' });
            gsap.from('#created-check', { scale: 0, rotate: -45, duration: 0.5, delay: 0.15, ease: 'back.out(2.5)' });
        }
    </script>
</body>
</html>
