<?php
/**
 * @var string $mode 'create' | 'edit'
 * @var array $client
 * @var array|null $period
 * @var array $marketplaces
 * @var array $existingEntries chave = marketplace_id
 * @var array $errors
 * @var array $old
 */
use App\Core\Csrf;

$isEdit = $mode === 'edit';
$values = array_merge($period ?? [], $old ?? []);
$val = fn(string $key, string $default = '') => htmlspecialchars($values[$key] ?? $default, ENT_QUOTES, 'UTF-8');
$action = url($isEdit ? '/periods/' . (int) $period['id'] . '/update' : '/clients/' . (int) $client['id'] . '/periods');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $isEdit ? 'Editar período' : 'Novo período' ?> - <?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <?php require __DIR__ . '/../partials/head-assets.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body>
    <div class="app-shell">
        <?php $active = 'clients'; require __DIR__ . '/../partials/sidebar.php'; ?>
        <main class="app-main">
            <div class="content">
                <h1><?= $isEdit ? 'Editar período' : 'Novo período' ?></h1>
                <p class="text-muted" style="margin-top:-12px;"><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></p>

                <?php if (empty($marketplaces)): ?>
                    <div class="alert-error">
                        Este cliente ainda não tem marketplaces vinculados.
                        <a href="<?= url('/clients/' . (int) $client['id'] . '/edit') ?>" style="color:inherit;text-decoration:underline;">Vincule marketplaces primeiro</a>.
                    </div>
                <?php else: ?>
                    <form class="form-card" method="post" action="<?= $action ?>" style="max-width:760px;">
                        <?= Csrf::field() ?>

                        <div class="form-grid">
                            <div class="field">
                                <label for="label">Rótulo (opcional)</label>
                                <input type="text" id="label" name="label" value="<?= $val('label') ?>" placeholder="ex: 1ª quinzena">
                            </div>
                            <div class="field">
                                <label for="reference_month">Competência</label>
                                <input type="month" id="reference_month" name="reference_month" value="<?= $val('reference_month') ?>">
                                <?php if (!empty($errors['reference_month'])): ?><div class="field-error"><?= htmlspecialchars($errors['reference_month'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="start_date">Data de início</label>
                                <input type="date" id="start_date" name="start_date" value="<?= $val('start_date') ?>" required>
                                <?php if (!empty($errors['start_date'])): ?><div class="field-error"><?= htmlspecialchars($errors['start_date'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="end_date">Data de fim</label>
                                <input type="date" id="end_date" name="end_date" value="<?= $val('end_date') ?>" required>
                                <?php if (!empty($errors['end_date'])): ?><div class="field-error"><?= htmlspecialchars($errors['end_date'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="section-title">Faturamento por marketplace</div>

                        <div x-data="periodMatrix(
                                <?= htmlspecialchars(json_encode(array_values($marketplaces)), ENT_QUOTES, 'UTF-8') ?>,
                                <?= htmlspecialchars(json_encode((object) $existingEntries), ENT_QUOTES, 'UTF-8') ?>
                            )">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Marketplace</th>
                                        <th>Valor (R$)</th>
                                        <th>Nº de pedidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in rows" :key="row.id">
                                        <tr>
                                            <td>
                                                <span :style="'display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;background:' + (row.color || '#4f7fff')"></span>
                                                <span x-text="row.name"></span>
                                                <input type="hidden" :name="'value_cents[' + row.id + ']'" :value="row.valueCents">
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric" x-model="row.valueDisplay" @input="onValueInput(row)" style="width:140px;">
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="1" x-model.number="row.ordersCount" :name="'orders_count[' + row.id + ']'" style="width:100px;">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td><strong>Total</strong></td>
                                        <td><strong x-text="totalDisplay"></strong></td>
                                        <td><strong x-text="totalOrders"></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn"><?= $isEdit ? 'Salvar alterações' : 'Salvar lançamento' ?></button>
                            <a href="<?= url('/clients/' . (int) $client['id'] . '/periods') ?>" class="btn-secondary" style="text-decoration:none;display:inline-flex;align-items:center;">Cancelar</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= url('/assets/js/period-matrix.js') ?>"></script>
</body>
</html>
