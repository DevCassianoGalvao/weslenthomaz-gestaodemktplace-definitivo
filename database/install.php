<?php

declare(strict_types=1);

// Instalador do schema via navegador (sem precisar de linha de comando no cPanel).
// Acesse via /install.php (shim em /public). Depois de rodar com sucesso, APAGUE
// este arquivo e o public/install.php — eles não devem ficar acessíveis em produção.

header('Content-Type: text/html; charset=utf-8');

/** @param string $title @param string $message @param bool $isError */
function render_result(string $title, string $message, bool $isError = false): void
{
    $color = $isError ? '#ef4444' : '#22c55e';
    echo "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"UTF-8\">";
    echo "<title>{$title}</title></head><body style=\"font-family:sans-serif;max-width:720px;margin:40px auto;line-height:1.6;\">";
    echo "<h1 style=\"color:{$color}\">{$title}</h1>";
    echo "<div>{$message}</div>";
    echo "</body></html>";
}

try {
    $config = require __DIR__ . '/../config/config.php';

    $pdo = new PDO(
        sprintf('mysql:host=%s;charset=%s', $config['DB_HOST'], $config['DB_CHARSET']),
        $config['DB_USER'],
        $config['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $dbName = $config['DB_NAME'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");

    $expectedTables = ['clients', 'users', 'marketplaces', 'client_marketplaces', 'periods', 'entries', 'entry_history'];

    $existing = [];
    foreach ($expectedTables as $table) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table'
        );
        $stmt->execute(['schema' => $dbName, 'table' => $table]);
        if ((int) $stmt->fetchColumn() > 0) {
            $existing[] = $table;
        }
    }

    $log = [];

    if (count($existing) === count($expectedTables)) {
        $log[] = 'Todas as tabelas já existem — nenhuma alteração de schema foi feita (dados preservados).';
    } else {
        if (!empty($existing)) {
            $log[] = 'Atenção: as tabelas [' . implode(', ', $existing) . '] já existem e foram mantidas como estão.';
        }

        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        if ($schemaSql === false) {
            throw new RuntimeException('Não foi possível ler database/schema.sql.');
        }

        // Remove comentários de linha antes de dividir em statements (evita cortar no meio de um --).
        $cleanSql = preg_replace('/^--.*$/m', '', $schemaSql);
        $statements = array_filter(array_map('trim', explode(';', $cleanSql)));

        foreach ($statements as $statement) {
            if ($statement === '' || stripos($statement, 'SET ') === 0) {
                continue;
            }

            // Só executa CREATE TABLE para tabelas que ainda não existem.
            if (preg_match('/CREATE TABLE(?: IF NOT EXISTS)? `?(\w+)`?/i', $statement, $m)) {
                if (in_array($m[1], $existing, true)) {
                    continue;
                }
            }

            $pdo->exec($statement);
        }

        $log[] = 'Tabelas criadas com sucesso: ' . implode(', ', array_diff($expectedTables, $existing));
    }

    // Criação do admin inicial (apenas se ainda não existir um usuário com o e-mail configurado).
    $adminEmail = $config['ADMIN_EMAIL'] ?? null;
    $adminPassword = $config['ADMIN_PASSWORD'] ?? null;

    if (empty($adminEmail) || empty($adminPassword)) {
        $log[] = 'ADMIN_EMAIL / ADMIN_PASSWORD não configurados (config/config.local.php ou variáveis de ambiente) — '
            . 'usuário admin inicial NÃO foi criado. Configure e recarregue esta página para criá-lo.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $adminEmail]);

        if ($stmt->fetch()) {
            $log[] = "Usuário admin com e-mail {$adminEmail} já existe — nenhuma conta duplicada foi criada.";
        } else {
            $hash = password_hash($adminPassword, PASSWORD_BCRYPT);
            $insert = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, client_id) VALUES (:name, :email, :hash, :role, NULL)'
            );
            $insert->execute([
                'name' => 'Administrador',
                'email' => $adminEmail,
                'hash' => $hash,
                'role' => 'admin',
            ]);
            $log[] = "Usuário admin criado com e-mail {$adminEmail}. <strong>Troque a senha no primeiro login.</strong>";
        }
    }

    // Catálogo padrão de marketplaces (mesmos canais da planilha atual da agência).
    $catalog = [
        ['Shopee', 'shopee', '#EE4D2D'],
        ['Shein', 'shein', '#000000'],
        ['TikTok Shop', 'tiktok', '#000000'],
        ['Magalu', 'magalu', '#0086FF'],
        ['Kway', 'kway', '#6D28D9'],
        ['Mercado Livre', 'mercado-livre', '#FFE600'],
        ['Amazon', 'amazon', '#FF9900'],
        ['Temu', 'temu', '#FB7701'],
    ];

    $insertMarketplace = $pdo->prepare(
        'INSERT IGNORE INTO marketplaces (name, slug, color, is_active) VALUES (:name, :slug, :color, 1)'
    );
    $addedMarketplaces = 0;
    foreach ($catalog as [$name, $slug, $color]) {
        $insertMarketplace->execute(['name' => $name, 'slug' => $slug, 'color' => $color]);
        $addedMarketplaces += $insertMarketplace->rowCount();
    }
    $log[] = $addedMarketplaces > 0
        ? "Catálogo de marketplaces: {$addedMarketplaces} canal(is) novo(s) adicionado(s)."
        : 'Catálogo de marketplaces: nenhum canal novo (já estavam cadastrados).';

    $log[] = '<strong>Segurança:</strong> apague database/install.php e public/install.php agora que a instalação foi concluída.';

    render_result('Instalação concluída', '<ul><li>' . implode('</li><li>', $log) . '</li></ul>');
} catch (Throwable $e) {
    render_result('Erro na instalação', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), true);
}
