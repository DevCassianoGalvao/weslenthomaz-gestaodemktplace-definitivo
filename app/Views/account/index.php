<?php
/** @var array $user */
/** @var array $errors */
/** @var array $old */
/** @var bool $success */
$values = array_merge($user, $old ?? []);
$val = fn(string $key) => htmlspecialchars($values[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minha conta - Painel de métricas by Weslen Thomaz</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = ''; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <div class="content-header">
                    <div>
                        <div class="eyebrow">Acesso e segurança</div>
                        <h1>Minha conta</h1>
                    </div>
                </div>

                <?php if ($success): ?><div class="alert-success">Dados de acesso atualizados.</div><?php endif; ?>

                <form class="form-card account-form" method="post" action="<?= url('/account') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="section-title">Dados de acesso</div>
                    <div class="field">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?= $val('email') ?>" required autocomplete="email">
                        <?php if (!empty($errors['email'])): ?><div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="section-title">Alterar senha</div>
                    <p class="text-muted">Deixe os campos de nova senha vazios para manter a senha atual.</p>
                    <div class="form-grid">
                        <div class="field">
                            <label for="new_password">Nova senha</label>
                            <input type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password">
                            <?php if (!empty($errors['new_password'])): ?><div class="field-error"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="confirm_password">Confirmar nova senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password">
                            <?php if (!empty($errors['confirm_password'])): ?><div class="field-error"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="current_password">Senha atual</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        <?php if (!empty($errors['current_password'])): ?><div class="field-error"><?= htmlspecialchars($errors['current_password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Salvar alterações</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
