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
        <?php if (!empty($topbarSettingsPanel)): ?>
            <div class="topbar-settings" id="topbar-settings">
                <button type="button" class="topbar-btn<?= !empty($topbarSettingsActive) ? ' has-dot' : '' ?>" title="Configurações da tela" aria-haspopup="true" aria-expanded="false" id="topbar-settings-btn"><?= Icon::svg('settings', 17) ?></button>
                <div class="topbar-popover" id="topbar-popover" hidden><?= $topbarSettingsPanel ?></div>
            </div>
            <script>
                (function () {
                    var btn = document.getElementById('topbar-settings-btn');
                    var pop = document.getElementById('topbar-popover');
                    if (!btn || !pop) return;
                    function setOpen(open) {
                        pop.hidden = !open;
                        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    }
                    try {
                        if (sessionStorage.getItem('topbarSettingsOpen') === '1') {
                            sessionStorage.removeItem('topbarSettingsOpen');
                            setOpen(true);
                        }
                    } catch (err) {}
                    pop.addEventListener('change', function () {
                        try { sessionStorage.setItem('topbarSettingsOpen', '1'); } catch (err) {}
                    });
                    btn.addEventListener('click', function (e) { e.stopPropagation(); setOpen(pop.hidden); });
                    document.addEventListener('click', function (e) { if (!pop.contains(e.target)) setOpen(false); });
                    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
                })();
            </script>
        <?php else: ?>
            <a class="topbar-btn" href="<?= url('/account') ?>" title="Minha conta"><?= Icon::svg('settings', 17) ?></a>
        <?php endif; ?>
        <div class="topbar-user">
            <span class="topbar-avatar"><?= htmlspecialchars($topbarInitials, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="topbar-user-text">
                <strong><?= htmlspecialchars($topbarName, ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars($topbarRole, ENT_QUOTES, 'UTF-8') ?></small>
            </span>
        </div>
    </div>
</header>
