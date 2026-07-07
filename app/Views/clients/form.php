<?php
/**
 * @var string $mode 'create' | 'edit'
 * @var array|null $client
 * @var int[] $selectedMarketplaceIds
 * @var array $marketplaces
 * @var array $errors
 * @var array $old
 * @var array|null $accountUser
 */
$isEdit = $mode === 'edit';
$values = array_merge($client ?? [], $old ?? []);
$val = fn(string $key, string $default = '') => htmlspecialchars($values[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?> - Gestão de Marketplaces</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $active = 'clients'; require __DIR__ . '/../partials/topbar.php'; ?>
    <div class="content">
        <h1><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?></h1>

        <form class="form-card" method="post" action="<?= $isEdit ? '/clients/' . (int) $client['id'] . '/update' : '/clients' ?>">
            <?= \App\Core\Csrf::field() ?>

            <div class="form-grid">
                <div class="field">
                    <label for="name">Nome do cliente</label>
                    <input type="text" id="name" name="name" value="<?= $val('name') ?>" required>
                    <?php if (!empty($errors['name'])): ?><div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="slug">Identificador (slug)</label>
                    <input type="text" id="slug" name="slug" value="<?= $val('slug') ?>" placeholder="gerado automaticamente se vazio">
                    <?php if (!empty($errors['slug'])): ?><div class="field-error"><?= htmlspecialchars($errors['slug'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="brand_color">Cor da marca</label>
                    <input type="color" id="brand_color" name="brand_color" value="<?= $val('brand_color', '#3b82f6') ?>">
                    <?php if (!empty($errors['brand_color'])): ?><div class="field-error"><?= htmlspecialchars($errors['brand_color'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <div class="field">
                    <label for="logo_url">URL do logo (opcional)</label>
                    <input type="url" id="logo_url" name="logo_url" value="<?= $val('logo_url') ?>" placeholder="https://...">
                    <?php if (!empty($errors['logo_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['logo_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </div>
                <?php if ($isEdit): ?>
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;">
                            <?php foreach (['active' => 'Ativo', 'paused' => 'Pausado', 'archived' => 'Arquivado'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($values['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-title">Marketplaces que este cliente opera</div>
            <div class="checkbox-list">
                <?php foreach ($marketplaces as $marketplace): ?>
                    <label class="checkbox-chip">
                        <input type="checkbox" name="marketplaces[]" value="<?= (int) $marketplace['id'] ?>"
                            <?= in_array((int) $marketplace['id'], $selectedMarketplaceIds, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($marketplace['name'], ENT_QUOTES, 'UTF-8') ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if (!$isEdit): ?>
                <div class="section-title">Conta de acesso do cliente final</div>
                <div class="form-grid">
                    <div class="field">
                        <label for="account_name">Nome do responsável</label>
                        <input type="text" id="account_name" name="account_name" value="<?= $val('account_name') ?>" required>
                        <?php if (!empty($errors['account_name'])): ?><div class="field-error"><?= htmlspecialchars($errors['account_name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="account_email">E-mail de login</label>
                        <input type="email" id="account_email" name="account_email" value="<?= $val('account_email') ?>" required>
                        <?php if (!empty($errors['account_email'])): ?><div class="field-error"><?= htmlspecialchars($errors['account_email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>
                </div>
                <p style="color:#94a3b8;font-size:0.85rem;">A senha inicial é gerada automaticamente pelo sistema e exibida uma única vez após salvar.</p>
            <?php elseif ($accountUser): ?>
                <div class="section-title">Conta de acesso</div>
                <p style="color:#94a3b8;font-size:0.9rem;">
                    <?= htmlspecialchars($accountUser['name'], ENT_QUOTES, 'UTF-8') ?>
                    &lt;<?= htmlspecialchars($accountUser['email'], ENT_QUOTES, 'UTF-8') ?>&gt;
                </p>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn"><?= $isEdit ? 'Salvar alterações' : 'Criar cliente' ?></button>
                <a href="/clients" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
