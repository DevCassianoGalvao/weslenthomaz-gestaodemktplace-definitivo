<?php
/** @var string $active */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Icon;

$active = $active ?? '';
$isAgency = Auth::isAdmin() || Auth::isOperator();
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-brand-mark"><?= Icon::svg('store', 18) ?></span>
        <span class="sidebar-brand-text">Painel de métricas <small>Gestor Weslen</small></span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-indicator" id="nav-indicator"></div>
        <?php if ($isAgency): ?>
            <a href="<?= url('/clients') ?>" class="nav-item <?= $active === 'clients' ? 'active' : '' ?>">
                <?= Icon::svg('users') ?><span>Clientes</span>
            </a>
            <a href="<?= url('/marketplaces') ?>" class="nav-item <?= $active === 'marketplaces' ? 'active' : '' ?>">
                <?= Icon::svg('shopping-bag') ?><span>Marketplaces</span>
            </a>
            <a href="<?= url('/dashboard') ?>" class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>">
                <?= Icon::svg('grid') ?><span>Dashboard</span>
            </a>
            <?php if (Auth::isAdmin()): ?>
                <a href="<?= url('/collaborators') ?>" class="nav-item <?= $active === 'collaborators' ? 'active' : '' ?>">
                    <?= Icon::svg('users') ?><span>Colaboradores</span>
                </a>
                <a href="<?= url('/history') ?>" class="nav-item <?= $active === 'history' ? 'active' : '' ?>">
                    <?= Icon::svg('clock') ?><span>Histórico</span>
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= url('/dashboard') ?>" class="nav-item active">
                <?= Icon::svg('grid') ?><span>Dashboard</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= htmlspecialchars(Auth::name(), ENT_QUOTES, 'UTF-8') ?></div>
            <a class="sidebar-account-link" href="<?= url('/account') ?>">Minha conta</a>
        </div>
        <form method="post" action="<?= url('/logout') ?>">
            <?= Csrf::field() ?>
            <button type="submit" class="sidebar-logout" title="Sair"><?= Icon::svg('log-out', 18) ?></button>
        </form>
    </div>
</aside>
