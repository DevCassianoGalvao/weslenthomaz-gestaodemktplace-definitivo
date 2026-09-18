<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function render_user_marketplaces_migration(string $title, array $messages, bool $isError = false): void
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

function user_marketplaces_table_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = :table_name'
    );
    $stmt->execute(['table_name' => 'user_marketplaces']);

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

    if (user_marketplaces_table_exists($pdo)) {
        render_user_marketplaces_migration('Migration ja aplicada', [
            'A tabela user_marketplaces ja existe. Nenhum dado foi alterado.',
        ]);
        return;
    }

    $pdo->exec(
        'CREATE TABLE user_marketplaces (
            user_id INT NOT NULL,
            marketplace_id INT NOT NULL,
            PRIMARY KEY (user_id, marketplace_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    render_user_marketplaces_migration('Migration concluida', [
        'Tabela user_marketplaces criada.',
        'Nenhum colaborador existente foi restrito: sem vinculo na tabela, o acesso continua irrestrito (todos os marketplaces).',
        'Para restringir um colaborador a marketplaces especificos, edite/cadastre-o em Colaboradores e marque os canais permitidos.',
        'Por seguranca, apague este arquivo e public/migrate-user-marketplaces.php depois de usar.',
    ]);
} catch (Throwable $exception) {
    render_user_marketplaces_migration('Erro na migration', [$exception->getMessage()], true);
}
