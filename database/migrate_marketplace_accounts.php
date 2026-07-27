<?php

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function render_migration_result(string $title, array $log, bool $isError = false): void
{
    $color = $isError ? '#ef4444' : '#22c55e';
    echo "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"UTF-8\"><title>{$title}</title></head>";
    echo "<body style=\"font-family:sans-serif;max-width:760px;margin:40px auto;line-height:1.6;\">";
    echo "<h1 style=\"color:{$color}\">{$title}</h1><ul><li>" . implode('</li><li>', array_map('htmlspecialchars', $log)) . '</li></ul>';
    echo '</body></html>';
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column'
    );
    $stmt->execute(['table' => $table, 'column' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
    );
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index_name'
    );
    $stmt->execute(['table' => $table, 'index_name' => $index]);

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

    $log = [];

    $clientColumns = [
        'website_url' => 'VARCHAR(255) NULL',
        'instagram_url' => 'VARCHAR(255) NULL',
        'facebook_url' => 'VARCHAR(255) NULL',
        'tiktok_url' => 'VARCHAR(255) NULL',
        'whatsapp' => 'VARCHAR(40) NULL',
        'notes' => 'VARCHAR(255) NULL',
    ];
    foreach ($clientColumns as $column => $definition) {
        if (!column_exists($pdo, 'clients', $column)) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN {$column} {$definition}");
            $log[] = "Coluna clients.{$column} criada.";
        }
    }

    if (!table_exists($pdo, 'client_marketplace_accounts')) {
        $pdo->exec(
            'CREATE TABLE client_marketplace_accounts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                client_id INT NOT NULL,
                marketplace_id INT NOT NULL,
                account_name VARCHAR(120) NOT NULL,
                account_identifier VARCHAR(120) NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                FOREIGN KEY (marketplace_id) REFERENCES marketplaces(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $log[] = 'Tabela client_marketplace_accounts criada.';
    }

    if (!column_exists($pdo, 'entries', 'client_marketplace_account_id')) {
        $pdo->exec('ALTER TABLE entries ADD COLUMN client_marketplace_account_id INT NULL AFTER period_id');
        $pdo->exec('ALTER TABLE entries ADD CONSTRAINT fk_entries_account FOREIGN KEY (client_marketplace_account_id) REFERENCES client_marketplace_accounts(id)');
        $log[] = 'Coluna entries.client_marketplace_account_id criada.';
    }

    if (!column_exists($pdo, 'entry_history', 'client_marketplace_account_id')) {
        $pdo->exec('ALTER TABLE entry_history ADD COLUMN client_marketplace_account_id INT NULL AFTER client_id');
        $pdo->exec('ALTER TABLE entry_history ADD CONSTRAINT fk_entry_history_account FOREIGN KEY (client_marketplace_account_id) REFERENCES client_marketplace_accounts(id)');
        $log[] = 'Coluna entry_history.client_marketplace_account_id criada.';
    }

    $seedAccounts = $pdo->exec(
        'INSERT INTO client_marketplace_accounts (client_id, marketplace_id, account_name, account_identifier, is_active)
         SELECT cm.client_id, cm.marketplace_id, m.name, NULL, 1
         FROM client_marketplaces cm
         INNER JOIN marketplaces m ON m.id = cm.marketplace_id
         LEFT JOIN client_marketplace_accounts cma
            ON cma.client_id = cm.client_id AND cma.marketplace_id = cm.marketplace_id
         WHERE cma.id IS NULL'
    );
    $log[] = "Contas padrao criadas a partir dos vinculos antigos: {$seedAccounts}.";

    $updatedEntries = $pdo->exec(
        'UPDATE entries e
         INNER JOIN periods p ON p.id = e.period_id
         INNER JOIN client_marketplace_accounts cma
            ON cma.client_id = p.client_id AND cma.marketplace_id = e.marketplace_id
         SET e.client_marketplace_account_id = cma.id
         WHERE e.client_marketplace_account_id IS NULL'
    );
    $log[] = "Lancamentos antigos vinculados a contas: {$updatedEntries}.";

    $updatedHistory = $pdo->exec(
        'UPDATE entry_history eh
         INNER JOIN client_marketplace_accounts cma
            ON cma.client_id = eh.client_id AND cma.marketplace_id = eh.marketplace_id
         SET eh.client_marketplace_account_id = cma.id
         WHERE eh.client_marketplace_account_id IS NULL'
    );
    $log[] = "Historico antigo vinculado a contas: {$updatedHistory}.";

    if (index_exists($pdo, 'entries', 'uniq_period_marketplace')) {
        $pdo->exec('ALTER TABLE entries DROP INDEX uniq_period_marketplace');
        $log[] = 'Indice antigo uniq_period_marketplace removido.';
    }

    if (!index_exists($pdo, 'entries', 'uniq_period_account')) {
        $pdo->exec('ALTER TABLE entries ADD UNIQUE KEY uniq_period_account (period_id, client_marketplace_account_id)');
        $log[] = 'Indice uniq_period_account criado.';
    }

    $log[] = 'Migracao concluida. Apague este arquivo e public/migrate-marketplace-accounts.php depois de rodar.';
    render_migration_result('Migracao concluida', $log);
} catch (Throwable $e) {
    render_migration_result('Erro na migracao', [$e->getMessage()], true);
}
