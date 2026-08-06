<?php
/**
 * @var string $mode 'create' | 'edit'
 * @var array|null $client
 * @var int[] $selectedMarketplaceIds
 * @var array $marketplaceAccounts
 * @var array $marketplaces
 * @var array $errors
 * @var array $old
 * @var array|null $accountUser
 */
$isEdit = $mode === 'edit';
$accountUser = $accountUser ?? null;
$values = array_merge($client ?? [], $old ?? []);
$val = fn(string $key, string $default = '') => htmlspecialchars($values[$key] ?? $default, ENT_QUOTES, 'UTF-8');
$marketplaceAccounts = $marketplaceAccounts ?? [];
$marketplaceOptionsJson = json_encode(array_map(fn($marketplace) => [
    'id' => (int) $marketplace['id'],
    'name' => $marketplace['name'],
], $marketplaces));
$accountRowsJson = json_encode(array_values(array_map(fn($account) => [
    'id' => $account['id'] ?? null,
    'marketplace_id' => (int) ($account['marketplace_id'] ?? 0),
    'account_name' => $account['account_name'] ?? '',
    'account_identifier' => $account['account_identifier'] ?? '',
], $marketplaceAccounts)));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?> - Painel de métricas Gestor Weslen</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1><?= $isEdit ? 'Editar cliente' : 'Novo cliente' ?></h1>

                <form class="form-card" method="post" enctype="multipart/form-data" action="<?= url($isEdit ? '/clients/' . (int) $client['id'] . '/update' : '/clients') ?>">
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
                            <input type="color" id="brand_color" name="brand_color" value="<?= $val('brand_color', '#d6b25e') ?>">
                            <?php if (!empty($errors['brand_color'])): ?><div class="field-error"><?= htmlspecialchars($errors['brand_color'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="logo_url">URL do logo (opcional)</label>
                            <input type="text" id="logo_url" name="logo_url" value="<?= $val('logo_url') ?>" placeholder="ex: empresa.com/logo.png">
                            <?php if (!empty($errors['logo_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['logo_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field logo-upload-field" style="grid-column:1 / -1;">
                            <div class="logo-upload">
                                <div class="logo-preview">
                                    <?php if (!empty($values['logo_url'])): ?>
                                        <img src="<?= $val('logo_url') ?>" alt="Logo atual">
                                    <?php else: ?>
                                        <span>LOGO</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label for="logo_file">Enviar arquivo do logo</label>
                                    <input type="file" id="logo_file" name="logo_file" accept="image/png,image/jpeg,image/webp">
                                    <p class="text-muted">PNG, JPG ou WEBP. Máximo de 3 MB. O arquivo enviado substitui a URL.</p>
                                    <?php if (!empty($errors['logo_file'])): ?><div class="field-error"><?= htmlspecialchars($errors['logo_file'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="field">
                            <label for="website_url">Site</label>
                            <input type="text" id="website_url" name="website_url" value="<?= $val('website_url') ?>" placeholder="ex: empresa.com.br">
                            <?php if (!empty($errors['website_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['website_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="instagram_url">Instagram</label>
                            <input type="text" id="instagram_url" name="instagram_url" value="<?= $val('instagram_url') ?>" placeholder="instagram.com/empresa">
                            <?php if (!empty($errors['instagram_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['instagram_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="facebook_url">Facebook</label>
                            <input type="text" id="facebook_url" name="facebook_url" value="<?= $val('facebook_url') ?>" placeholder="facebook.com/empresa">
                            <?php if (!empty($errors['facebook_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['facebook_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="tiktok_url">TikTok</label>
                            <input type="text" id="tiktok_url" name="tiktok_url" value="<?= $val('tiktok_url') ?>" placeholder="tiktok.com/@empresa">
                            <?php if (!empty($errors['tiktok_url'])): ?><div class="field-error"><?= htmlspecialchars($errors['tiktok_url'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="whatsapp">WhatsApp</label>
                            <input type="text" id="whatsapp" name="whatsapp" value="<?= $val('whatsapp') ?>" placeholder="+55...">
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="notes">Observações internas</label>
                            <input type="text" id="notes" name="notes" value="<?= $val('notes') ?>">
                        </div>
                        <?php if ($isEdit && \App\Core\Auth::isAdmin()): ?>
                            <div class="field">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <?php foreach (['active' => 'Ativo', 'paused' => 'Pausado'] as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= ($values['status'] ?? 'active') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="section-title">Contas de marketplaces do cliente</div>
                    <?php if (!empty($errors['marketplace_accounts'])): ?><div class="field-error" style="margin-bottom:10px;"><?= htmlspecialchars($errors['marketplace_accounts'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    <div id="marketplace-accounts" data-marketplaces="<?= htmlspecialchars($marketplaceOptionsJson, ENT_QUOTES, 'UTF-8') ?>" data-accounts="<?= htmlspecialchars($accountRowsJson, ENT_QUOTES, 'UTF-8') ?>">
                        <table>
                            <thead>
                                <tr>
                                    <th>Marketplace oficial</th>
                                    <th>Nome da conta</th>
                                    <th>Identificador</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="marketplace-account-rows"></tbody>
                        </table>
                        <button type="button" class="btn-secondary" id="add-marketplace-account" style="margin-top:12px;">Adicionar conta</button>
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
                        <p class="text-muted">A senha inicial é gerada automaticamente pelo sistema e exibida uma única vez após salvar.</p>
                    <?php elseif ($accountUser): ?>
                        <div class="section-title">Conta de acesso</div>
                        <div class="form-grid">
                            <div class="field">
                                <label for="account_email">E-mail de login</label>
                                <input type="email" id="account_email" name="account_email" value="<?= htmlspecialchars($accountUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                <?php if (!empty($errors['account_email'])): ?><div class="field-error"><?= htmlspecialchars($errors['account_email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="account_password">Nova senha (opcional)</label>
                                <input type="password" id="account_password" name="account_password" minlength="8" placeholder="deixe vazio para manter">
                                <?php if (!empty($errors['account_password'])): ?><div class="field-error"><?= htmlspecialchars($errors['account_password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn"><?= $isEdit ? 'Salvar alterações' : 'Criar cliente' ?></button>
                        <a href="<?= url('/clients') ?>" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Cancelar</a>
                        <?php if ($isEdit && \App\Core\Auth::isAdmin()): ?>
                            <button type="submit" form="delete-client-form" class="btn-secondary" style="color:var(--danger);margin-left:auto;" onclick="return confirm('Excluir este cliente e todos os lançamentos? Essa ação não pode ser desfeita.');">Excluir cliente</button>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($isEdit && \App\Core\Auth::isAdmin()): ?>
                    <form id="delete-client-form" method="post" action="<?= url('/clients/' . (int) $client['id'] . '/delete') ?>">
                        <?= \App\Core\Csrf::field() ?>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>
        (function () {
            var root = document.getElementById('marketplace-accounts');
            if (!root) return;

            var marketplaces = JSON.parse(root.dataset.marketplaces || '[]');
            var rows = JSON.parse(root.dataset.accounts || '[]');
            var body = document.getElementById('marketplace-account-rows');
            var addButton = document.getElementById('add-marketplace-account');

            function addRow(data) {
                data = data || {};
                var index = body.children.length;
                var tr = document.createElement('tr');

                var options = marketplaces.map(function (marketplace) {
                    var selected = Number(data.marketplace_id || 0) === Number(marketplace.id) ? ' selected' : '';
                    return '<option value="' + marketplace.id + '"' + selected + '>' + escapeHtml(marketplace.name) + '</option>';
                }).join('');

                tr.innerHTML =
                    '<td>' +
                        '<input type="hidden" name="marketplace_accounts[' + index + '][id]" value="' + escapeAttr(data.id || '') + '">' +
                        '<select name="marketplace_accounts[' + index + '][marketplace_id]" required>' +
                            '<option value="">Selecione</option>' + options +
                        '</select>' +
                    '</td>' +
                    '<td><input type="text" name="marketplace_accounts[' + index + '][account_name]" value="' + escapeAttr(data.account_name || '') + '" placeholder="ex: Shopee Principal" required></td>' +
                    '<td><input type="text" name="marketplace_accounts[' + index + '][account_identifier]" value="' + escapeAttr(data.account_identifier || '') + '" placeholder="loja, ID ou apelido"></td>' +
                    '<td><button type="button" class="btn-secondary" data-remove-row>Remover</button></td>';

                tr.querySelector('[data-remove-row]').addEventListener('click', function () {
                    tr.remove();
                    reindexRows();
                });
                body.appendChild(tr);
            }

            function reindexRows() {
                Array.prototype.forEach.call(body.children, function (tr, index) {
                    Array.prototype.forEach.call(tr.querySelectorAll('[name^="marketplace_accounts["]'), function (field) {
                        field.name = field.name.replace(/marketplace_accounts\[\d+\]/, 'marketplace_accounts[' + index + ']');
                    });
                });
            }

            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, function (char) {
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
                });
            }

            function escapeAttr(value) {
                return escapeHtml(value);
            }

            if (rows.length === 0) {
                addRow();
            } else {
                rows.forEach(addRow);
            }
            addButton.addEventListener('click', function () { addRow(); });
        })();
    </script>
</body>
</html>
