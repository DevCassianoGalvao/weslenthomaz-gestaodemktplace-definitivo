<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function render_ads_migration(string $title, array $messages, bool $isError = false): void
{
    $color = $isError ? '#ef4444' : '#22c55e';
    echo "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"UTF-8\"><title>{$title}</title></head>";
    echo "<body style=\"font-family:sans-serif;max-width:760px;margin:40px auto;line-height:1.6;\">";
    echo "<h1 style=\"color:{$color}\">{$title}</h1><ul><li>" . implode('</li><li>', array_map('htmlspecialchars', $messages)) . '</li></ul>';
    echo '</body></html>';
}

function ads_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
    );
    $stmt->execute(['table_name' => $table, 'column_name' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

try {
    $config = require __DIR__ . '/../config/config.php';
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['DB_HOST'], $config['DB_NAME'], $config['DB_CHARSET']),
        $config['DB_USER'],
        $config['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    $messages = [];

    if (!ads_column_exists($pdo, 'entries', 'ad_spend_cents')) {
        $pdo->exec('ALTER TABLE entries ADD COLUMN ad_spend_cents BIGINT NOT NULL DEFAULT 0 AFTER value_cents');
        $messages[] = 'Coluna entries.ad_spend_cents criada com valor inicial 0. Lancamentos existentes foram preservados.';
    } else {
        $messages[] = 'Coluna entries.ad_spend_cents ja existe. Nenhum dado foi alterado.';
    }

    if (!ads_column_exists($pdo, 'entry_history', 'old_ad_spend_cents')) {
        $pdo->exec('ALTER TABLE entry_history ADD COLUMN old_ad_spend_cents BIGINT NULL AFTER new_value_cents');
        $messages[] = 'Coluna entry_history.old_ad_spend_cents criada.';
    } else {
        $messages[] = 'Coluna entry_history.old_ad_spend_cents ja existe.';
    }

    if (!ads_column_exists($pdo, 'entry_history', 'new_ad_spend_cents')) {
        $pdo->exec('ALTER TABLE entry_history ADD COLUMN new_ad_spend_cents BIGINT NULL AFTER old_ad_spend_cents');
        $messages[] = 'Coluna entry_history.new_ad_spend_cents criada.';
    } else {
        $messages[] = 'Coluna entry_history.new_ad_spend_cents ja existe.';
    }

    $messages[] = 'Migracao concluida. Por seguranca, apague este arquivo e public/migrate-ads-metrics.php depois de usar.';
    render_ads_migration('Migracao de Ads concluida', $messages);
} catch (Throwable $exception) {
    render_ads_migration('Erro na migracao de Ads', [$exception->getMessage()], true);
}
