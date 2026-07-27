<?php
/** @var array $collaborators */
/** @var array $errors */
/** @var array $old */
$val = fn(string $key) => htmlspecialchars($old[$key] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Colaboradores - Painel de métricas by Weslen Thomaz</title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'collaborators'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <div class="content-header">
                    <div>
                        <div class="eyebrow">Equipe e permissões</div>
                        <h1>Colaboradores</h1>
                    </div>
                </div>

                <?php if (($_GET['created'] ?? '') === '1'): ?><div class="alert-success">Colaborador criado com sucesso.</div><?php endif; ?>

                <table style="margin-bottom:28px;">
                    <thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Criado em</th></tr></thead>
                    <tbody>
                        <?php foreach ($collaborators as $collaborator): ?>
                            <tr>
                                <td><?= htmlspecialchars($collaborator['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($collaborator['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-active"><span class="badge-dot"></span>Colaborador</span></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($collaborator['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($collaborators)): ?><tr><td colspan="4" class="text-muted">Nenhum colaborador cadastrado.</td></tr><?php endif; ?>
                    </tbody>
                </table>

                <div class="section-title">Cadastrar colaborador</div>
                <form class="form-card" method="post" action="<?= url('/collaborators') ?>">
                    <?= \App\Core\Csrf::field() ?>
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Nome</label>
                            <input type="text" id="name" name="name" value="<?= $val('name') ?>" required>
                            <?php if (!empty($errors['name'])): ?><div class="field-error"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="email">E-mail de acesso</label>
                            <input type="email" id="email" name="email" value="<?= $val('email') ?>" required>
                            <?php if (!empty($errors['email'])): ?><div class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field" style="grid-column:1 / -1;">
                            <label for="password">Senha inicial</label>
                            <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password">
                            <?php if (!empty($errors['password'])): ?><div class="field-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                    </div>
                    <p class="text-muted">O colaborador poderá cadastrar e editar dados operacionais, mas não poderá excluir ou desativar informações.</p>
                    <div class="form-actions"><button type="submit" class="btn">Criar colaborador</button></div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
