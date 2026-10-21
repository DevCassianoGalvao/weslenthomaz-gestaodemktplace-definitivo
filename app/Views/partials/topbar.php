<?php
/** @var string $active */
use App\Core\Auth;
use App\Core\Icon;

$topbarSections = [
    'clients' => 'Clientes',
    'marketplaces' => 'Marketplaces',
    'dashboard' => 'Dashboard',
    'collaborators' => 'Colaboradores',
    'history' => 'Histórico',
];
$topbarSection = $topbarSections[$active ?? ''] ?? 'Minha conta';
$topbarName = (string) Auth::name();
$topbarInitials = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $topbarName), 0, 2)) ?: 'GW';
$topbarRole = ['admin' => 'Administrador', 'operator' => 'Colaborador', 'client' => 'Cliente'][Auth::role() ?? ''] ?? '';
?>
<header class="topbar">
    <div class="topbar-crumb">
        <span>Painel</span>
        <span class="topbar-crumb-sep">/</span>
        <strong><?= htmlspecialchars($topbarSection, ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="topbar-actions">
        <span class="topbar-pill"><?= Icon::svg('calendar', 15) ?><?= date('d/m/Y') ?></span>
        <a class="topbar-btn" href="<?= url('/account') ?>" title="Minha conta"><?= Icon::svg('settings', 17) ?></a>
        <div class="topbar-user">
            <span class="topbar-avatar"><?= htmlspecialchars($topbarInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="topbar-user-text">
                <strong><?= htmlspecialchars($topbarName, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($topbarRole, ENT_QUOTES, 'UTF-8') ?></small>
            </span>
        </div>
    </div>
</header>
