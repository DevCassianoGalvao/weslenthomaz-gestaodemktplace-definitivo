<?php
/** @var array $client @var array $periods */
use App\Core\Format;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lançamentos - <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <?php $active = 'clients'; require __DIR__ . '/../partials/topbar.php'; ?>
    <div class="content">
        <div class="content-header">
            <div>
                <h1><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p style="color:#94a3b8;margin-top:-12px;">Lançamentos por período</p>
            </div>
            <a href="/clients/<?= (int) $client['id'] ?>/periods/new" class="btn-link">+ Novo período</a>
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
                                    <br><span style="color:#94a3b8;font-size:0.8rem;"><?= htmlspecialchars($period['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($period['reference_month'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= Format::centsToBrl((int) $period['total_value_cents']) ?></td>
                            <td><?= (int) $period['total_orders'] ?></td>
                            <td><a href="/periods/<?= (int) $period['id'] ?>/edit">Editar</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
