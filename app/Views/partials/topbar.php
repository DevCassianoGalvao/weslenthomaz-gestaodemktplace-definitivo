<?php
/** @var string $active */
use App\Core\Auth;
use App\Core\Csrf;

$active = $active ?? '';
$isAgency = Auth::isAdmin() || Auth::isOperator();
?>
<div class="topbar">
    <div class="topbar-nav">
        <strong>Gestão de Marketplaces</strong>
        <?php if ($isAgency): ?>
            <a href="/clients" class="<?= $active === 'clients' ? 'nav-active' : '' ?>">Clientes</a>
            <a href="/marketplaces" class="<?= $active === 'marketplaces' ? 'nav-active' : '' ?>">Marketplaces</a>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
            <a href="/history" class="<?= $active === 'history' ? 'nav-active' : '' ?>">Histórico</a>
        <?php endif; ?>
    </div>
    <div class="topbar-user">
        <span>Olá, <strong><?= htmlspecialchars(Auth::name(), ENT_QUOTES, 'UTF-8') ?></strong> <span class="badge"><?= htmlspecialchars(Auth::role(), ENT_QUOTES, 'UTF-8') ?></span></span>
        <form method="post" action="/logout">
            <?= Csrf::field() ?>
            <button type="submit">Sair</button>
        </form>
    </div>
</div>
