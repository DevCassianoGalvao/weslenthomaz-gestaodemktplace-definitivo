<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function render_client_goal_migration(string $title, array $messages, bool $isError = false): void
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

function client_goal_column_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
    );
    $stmt->execute(['table_name' => 'clients', 'column_name' => 'monthly_goal_cents']);

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

    if (client_goal_column_exists($pdo)) {
        render_client_goal_migration('Migration ja aplicada', [
            'A coluna clients.monthly_goal_cents ja existe. Nenhum dado foi alterado.',
        ]);
        return;
    }

    $pdo->exec('ALTER TABLE clients ADD COLUMN monthly_goal_cents BIGINT NULL DEFAULT NULL AFTER show_ads_metrics');
    render_client_goal_migration('Migration concluida', [
        'Coluna clients.monthly_goal_cents criada (vazia/NULL para clientes existentes).',
        'Nenhum lancamento ou cliente existente foi alterado.',
        'Defina a meta mensal de cada cliente na tela de edicao do cliente.',
        'Por seguranca, apague este arquivo e public/migrate-client-goal.php depois de usar.',
    ]);
} catch (Throwable $exception) {
    render_client_goal_migration('Erro na migration', [$exception->getMessage()], true);
}
