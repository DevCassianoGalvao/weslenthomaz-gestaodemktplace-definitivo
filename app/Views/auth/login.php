<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Gestão de Marketplaces</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="center-screen">
        <div class="card" id="login-card">
            <h1>Gestão de Marketplaces</h1>

            <?php if (!empty($error)): ?>
                <div class="alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="post" action="<?= url('/login') ?>">
                <?= \App\Core\Csrf::field() ?>
                <div class="field">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Entrar</button>
            </form>
        </div>
    </div>
    <script>
        if (typeof gsap !== 'undefined') {
            gsap.from('#login-card', { opacity: 0, y: 16, scale: 0.98, duration: 0.5, ease: 'power2.out' });
        }
    </script>
</body>
</html>
