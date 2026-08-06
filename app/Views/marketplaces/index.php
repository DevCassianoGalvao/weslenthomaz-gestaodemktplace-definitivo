<?php
/** @var array $marketplaces @var array $errors @var array $old */
$val = fn(string $key, string $default = '') => htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplaces - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'marketplaces'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1>Catálogo de marketplaces</h1>

                <table style="margin-bottom:32px;">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marketplaces as $marketplace): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?= url('/marketplaces/' . (int) $marketplace['id'] . '/update') ?>" style="display:flex;gap:8px;align-items:center;">
                                        <?= \App\Core\Csrf::field() ?>
                                        <input type="color" name="color" value="<?= htmlspecialchars($marketplace['color'] ?: '#d6b25e', ENT_QUOTES, 'UTF-8') ?>" style="width:42px;height:36px;padding:2px;">
                                        <input type="text" name="name" value="<?= htmlspecialchars($marketplace['name'], ENT_QUOTES, 'UTF-8') ?>" required style="min-width:180px;">
                                        <button type="submit" class="btn-secondary">Salvar</button>
                                    </form>
                                </td>
                                <td><?= htmlspecialchars($marketplace['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge <?= $marketplace['is_active'] ? 'badge-active' : 'badge-archived' ?>">
                                        <span class="badge-dot"></span><?= $marketplace['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (\App\Core\Auth::isAdmin()): ?>
                                        <form method="post" action="<?= url('/marketplaces/' . (int) $marketplace['id'] . '/toggle') ?>">
                                            <?= \App\Core\Csrf::field() ?>
                                            <button type="submit" class="btn-secondary"><?= $marketplace['is_active'] ? 'Desativar' : 'Ativar' ?></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Somente admin</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="section-title">Adicionar novo marketplace</div>
                <form class="form-card" method="post" action="<?= url('/marketplaces') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Nome</label>
                            <input type="text" id="name" name="name" value="<?= $val('name') ?>" required>
                            <?php if (!empty($errors['name'])): ?><div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="color">Cor</label>
                            <input type="color" id="color" name="color" value="<?= $val('color', '#d6b25e') ?>">
                            <?php if (!empty($errors['color'])): ?><div class="field-error"><?= htmlspecialchars($errors['color'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn">Adicionar</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
