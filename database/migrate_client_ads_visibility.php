<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function render_client_ads_migration(string $title, array $messages, bool $isError = false): void
{
    $color = $isError ? '#ef4444' : '#22c55e';
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>';
    echo '<body style="font-family:sans-serif;max-width:760px;margin:40px auto;line-height:1.6;">';
    echo '<h1 style="color:' . $color . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><ul>';
    foreach ($messages as $message) {
        echo '<li>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul></body></html>';
}

function client_ads_column_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
    );
    $stmt->execute(['table_name' => 'clients', 'column_name' => 'show_ads_metrics']);

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

    if (client_ads_column_exists($pdo)) {
        render_client_ads_migration('Migration ja aplicada', [
            'A coluna clients.show_ads_metrics ja existe. Nenhum dado foi alterado.',
        ]);
        return;
    }

    $pdo->exec('ALTER TABLE clients ADD COLUMN show_ads_metrics TINYINT(1) NOT NULL DEFAULT 1 AFTER notes');
    render_client_ads_migration('Migration concluida', [
        'Coluna clients.show_ads_metrics criada com valor inicial 1.',
        'Clientes existentes continuam exibindo Ads e ROAS.',
        'Agora o admin pode ocultar Ads e ROAS individualmente na edicao do cliente.',
        'Por seguranca, apague este arquivo e public/migrate-client-ads-visibility.php depois de usar.',
    ]);
} catch (Throwable $exception) {
    render_client_ads_migration('Erro na migration', [$exception->getMessage()], true);
}
