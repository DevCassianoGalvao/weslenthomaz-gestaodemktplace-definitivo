<?php
/** @var array $client @var array $periods */
use App\Core\Format;
use App\Core\Icon;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lançamentos - <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <?php if (($_GET['saved'] ?? '') === '1'): ?>
                    <div class="alert-success" id="saved-banner" style="display:flex;align-items:center;gap:8px;">
                        <span id="saved-check"><?= Icon::svg('check-circle', 18) ?></span>
                        Lançamento salvo com sucesso.
                    </div>
                <?php endif; ?>

                <div class="content-header">
                    <div>
                        <h1><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p class="text-muted page-subtitle">Lançamentos por período</p>
                    </div>
                    <a href="<?= url('/clients/' . (int) $client['id'] . '/periods/new') ?>" class="btn-link">+ Novo período</a>
                </div>

                <?php if (empty($periods)): ?>
                    <p>Nenhum período lançado ainda para este cliente.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Competência</th>
                                <th>Total faturado</th>
                                <th>Total de pedidos</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($periods as $period): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($period['start_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        –
                                        <?= htmlspecialchars(date('d/m/Y', strtotime($period['end_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($period['label'])): ?>
                                            <br><span class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($period['reference_month'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= Format::centsToBrl((int) $period['total_value_cents']) ?></td>
                                    <td><?= (int) $period['total_orders'] ?></td>
                                    <td><a href="<?= url('/periods/' . (int) $period['id'] . '/edit') ?>">Editar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php if (($_GET['saved'] ?? '') === '1'): ?>
        <script>
            animateSuccessBanner('#saved-banner', 2500);
            if (typeof gsap !== 'undefined') {
                gsap.from('#saved-check', { scale: 0, rotate: -45, duration: 0.5, delay: 0.1, ease: 'back.out(2.5)' });
            }
        </script>
    <?php endif; ?>
</body>
</html>
