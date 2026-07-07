<?php
/** @var array $marketplaces @var array $errors @var array $old */
$val = fn(string $key, string $default = '') => htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Marketplaces - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $active = 'marketplaces'; require __DIR__ . '/../partials/topbar.php'; ?>
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
                            <?php if (!empty($marketplace['color'])): ?>
                                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= htmlspecialchars($marketplace['color'], ENT_QUOTES, 'UTF-8') ?>;margin-right:6px;"></span>
                            <?php endif; ?>
                            <?= htmlspecialchars($marketplace['name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($marketplace['slug'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge <?= $marketplace['is_active'] ? 'badge-active' : 'badge-archived' ?>"><?= $marketplace['is_active'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td>
                            <form method="post" action="/marketplaces/<?= (int) $marketplace['id'] ?>/toggle">
                                <?= \App\Core\Csrf::field() ?>
                                <button type="submit" class="btn-secondary"><?= $marketplace['is_active'] ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section-title">Adicionar novo marketplace</div>
        <form class="form-card" method="post" action="/marketplaces">
            <?= \App\Core\Csrf::field() ?>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Nome</label>
                    <input type="text" id="name" name="name" value="<?= $val('name') ?>" required>
                    <?php if (!empty($errors['name'])): ?><div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="color">Cor</label>
                    <input type="color" id="color" name="color" value="<?= $val('color', '#3b82f6') ?>">
                    <?php if (!empty($errors['color'])): ?><div class="field-error"><?= htmlspecialchars($errors['color'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn">Adicionar</button>
            </div>
        </form>
    </div>
</body>
</html>
