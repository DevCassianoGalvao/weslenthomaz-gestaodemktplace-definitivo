<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="topbar">
        <div>Olá, <strong><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge"><?= htmlspecialchars(\App\Core\Auth::role(), ENT_QUOTES, 'UTF-8') ?></span></div>
        <form method="post" action="/logout">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit">Sair</button>
        </form>
    </div>
    <div class="content">
        <h1>Lista de clientes</h1>
        <p>Placeholder da Fase 1 — CRUD de clientes chega na Fase 2.</p>
    </div>
</body>
</html>
