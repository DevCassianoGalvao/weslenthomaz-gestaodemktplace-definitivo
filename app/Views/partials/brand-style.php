<?php
/**
 * Identidade visual por cliente (PRD fase 8): sobrescreve a cor primária do tema
 * com o brand_color do cliente, quando definido e num formato hex válido.
 * @var array $client
 */
$brandColor = $client['brand_color'] ?? null;
if ($brandColor && preg_match('/^#[0-9a-fA-F]{6}$/', $brandColor)):
?>
<style>
    :root { --color-primary: <?= htmlspecialchars($brandColor, ENT_QUOTES, 'UTF-8') ?>; }
</style>
<?php endif; ?>
