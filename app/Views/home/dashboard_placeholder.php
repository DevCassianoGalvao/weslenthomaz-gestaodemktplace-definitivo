<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="topbar">
        <div>Olá, <strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge"><?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?></span></div>
        <form method="post" action="/logout">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit">Sair</button>
        </form>
    </div>
    <div class="content">
        <h1>Dashboard</h1>
        <p>Placeholder da Fase 1 — KPIs e gráficos chegam na Fase 5/6.</p>
        <?php if ($clientId !== null): ?>
            <p>Escopo restrito ao client_id = <?= (int) $clientId ?> (resolvido pela sessão, nunca por parâmetro de URL).</p>
        <?php endif; ?>
    </div>
</body>
</html>
