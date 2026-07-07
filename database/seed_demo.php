<?php

declare(strict_types=1);

/**
 * Seed de demonstração: cria 5 clientes fictícios com marketplaces vinculados,
 * conta de acesso e 3 meses de lançamentos, pra apresentação ao cliente final.
 *
 * Roda via navegador em /seed-demo.php (shim em /public) — apaga depois de usar,
 * igual ao install.php. Idempotente: cliente cujo slug já existe é pulado, então
 * rodar de novo não duplica nada.
 *
 * Requer que o schema já esteja instalado (rode install.php antes).
 */

header('Content-Type: text/html; charset=utf-8');

const DEMO_PASSWORD = 'Demonstracao@123';

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $accents = [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y',
    ];
    $text = strtr($text, $accents);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

/** @param string $title @param string $message @param bool $isError */
function render_result(string $title, string $message, bool $isError = false): void
{
    $color = $isError ? '#ef4444' : '#22c55e';
    echo "<!doctype html><html lang=\"pt-BR\"><head><meta charset=\"UTF-8\">";
    echo "<title>{$title}</title></head><body style=\"font-family:sans-serif;max-width:760px;margin:40px auto;line-height:1.6;\">";
    echo "<h1 style=\"color:{$color}\">{$title}</h1>";
    echo "<div>{$message}</div>";
    echo "</body></html>";
}

try {
    $config = require __DIR__ . '/../config/config.php';

    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['DB_HOST'], $config['DB_NAME'], $config['DB_CHARSET']),
        $config['DB_USER'],
        $config['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );

    // Confere se o schema já existe.
    $check = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'marketplaces'");
    if ((int) $check->fetchColumn() === 0) {
        render_result('Schema não encontrado', 'Rode <code>install.php</code> antes de gerar os dados de demonstração.', true);
        exit;
    }

    $marketplaceRows = $pdo->query('SELECT id, slug FROM marketplaces')->fetchAll();
    $marketplaceIdBySlug = [];
    foreach ($marketplaceRows as $row) {
        $marketplaceIdBySlug[$row['slug']] = (int) $row['id'];
    }
    if (empty($marketplaceIdBySlug)) {
        render_result('Catálogo de marketplaces vazio', 'Rode <code>install.php</code> antes de gerar os dados de demonstração.', true);
        exit;
    }

    // Precisa de pelo menos um admin/operador pra ser o "created_by" dos períodos.
    $creatorStmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin','operator') ORDER BY id LIMIT 1");
    $creatorId = $creatorStmt->fetchColumn();
    if ($creatorId === false) {
        render_result('Nenhum usuário admin/operador encontrado', 'Crie o admin inicial via <code>install.php</code> primeiro.', true);
        exit;
    }
    $creatorId = (int) $creatorId;

    $companies = [
        [
            'name' => 'Bella Casa Decor',
            'brand_color' => '#E85D75',
            'marketplaces' => ['shopee', 'mercado-livre', 'magalu', 'shein'],
            'account_name' => 'Fernanda Lima',
            'base_cents' => 180000,
            'ticket_cents' => [4500, 9000],
        ],
        [
            'name' => 'TechNova Eletrônicos',
            'brand_color' => '#2DD4BF',
            'marketplaces' => ['mercado-livre', 'amazon', 'magalu', 'shopee'],
            'account_name' => 'Rafael Souza',
            'base_cents' => 420000,
            'ticket_cents' => [15000, 35000],
        ],
        [
            'name' => 'Verde Vida Suplementos',
            'brand_color' => '#22C55E',
            'marketplaces' => ['shopee', 'mercado-livre', 'tiktok'],
            'account_name' => 'Camila Rocha',
            'base_cents' => 95000,
            'ticket_cents' => [3000, 6000],
        ],
        [
            'name' => 'Urban Style Moda',
            'brand_color' => '#A78BFA',
            'marketplaces' => ['shein', 'shopee', 'tiktok', 'magalu'],
            'account_name' => 'Bruno Alves',
            'base_cents' => 260000,
            'ticket_cents' => [6000, 12000],
        ],
        [
            'name' => 'PetFeliz Acessórios',
            'brand_color' => '#F59E0B',
            'marketplaces' => ['mercado-livre', 'shopee', 'amazon'],
            'account_name' => 'Juliana Prado',
            'base_cents' => 140000,
            'ticket_cents' => [3500, 7000],
        ],
    ];

    // Últimos 3 meses de competência, do mais antigo pro mais novo.
    $referenceMonths = [];
    for ($i = 2; $i >= 0; $i--) {
        $referenceMonths[] = date('Y-m', strtotime("-{$i} months"));
    }

    $log = [];
    $credentials = [];

    foreach ($companies as $company) {
        $slug = slugify($company['name']);

        $existsStmt = $pdo->prepare('SELECT id FROM clients WHERE slug = :slug');
        $existsStmt->execute(['slug' => $slug]);
        if ($existsStmt->fetch()) {
            $log[] = "{$company['name']}: já existe (slug '{$slug}') — pulado, nada foi alterado.";
            continue;
        }

        $pdo->beginTransaction();
        try {
            $insertClient = $pdo->prepare(
                'INSERT INTO clients (name, slug, brand_color, status) VALUES (:name, :slug, :brand_color, "active")'
            );
            $insertClient->execute([
                'name' => $company['name'],
                'slug' => $slug,
                'brand_color' => $company['brand_color'],
            ]);
            $clientId = (int) $pdo->lastInsertId();

            $linkStmt = $pdo->prepare('INSERT INTO client_marketplaces (client_id, marketplace_id) VALUES (:client_id, :marketplace_id)');
            $marketplaceIds = [];
            foreach ($company['marketplaces'] as $mpSlug) {
                if (!isset($marketplaceIdBySlug[$mpSlug])) {
                    continue;
                }
                $mpId = $marketplaceIdBySlug[$mpSlug];
                $marketplaceIds[] = $mpId;
                $linkStmt->execute(['client_id' => $clientId, 'marketplace_id' => $mpId]);
            }

            $email = 'contato@' . $slug . '.com.br';
            $insertUser = $pdo->prepare(
                'INSERT INTO users (name, email, password_hash, role, client_id) VALUES (:name, :email, :hash, "client", :client_id)'
            );
            $insertUser->execute([
                'name' => $company['account_name'],
                'email' => $email,
                'hash' => password_hash(DEMO_PASSWORD, PASSWORD_BCRYPT),
                'client_id' => $clientId,
            ]);

            $insertPeriod = $pdo->prepare(
                'INSERT INTO periods (client_id, label, start_date, end_date, reference_month, created_by)
                 VALUES (:client_id, :label, :start_date, :end_date, :reference_month, :created_by)'
            );
            $insertEntry = $pdo->prepare(
                'INSERT INTO entries (period_id, marketplace_id, value_cents, orders_count) VALUES (:period_id, :marketplace_id, :value_cents, :orders_count)'
            );

            foreach ($referenceMonths as $monthIndex => $refMonth) {
                $startDate = $refMonth . '-01';
                $endDate = date('Y-m-t', strtotime($startDate));

                $insertPeriod->execute([
                    'client_id' => $clientId,
                    'label' => 'Mês completo',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'reference_month' => $refMonth,
                    'created_by' => $creatorId,
                ]);
                $periodId = (int) $pdo->lastInsertId();

                // Leve tendência de crescimento mês a mês + variação aleatória por marketplace.
                $growthFactor = 1 + ($monthIndex * 0.10);

                foreach ($marketplaceIds as $mpId) {
                    $randomFactor = mt_rand(70, 130) / 100;
                    $valueCents = (int) round($company['base_cents'] * $growthFactor * $randomFactor);
                    $ticketCents = mt_rand($company['ticket_cents'][0], $company['ticket_cents'][1]);
                    $ordersCount = max(1, (int) round($valueCents / $ticketCents));

                    $insertEntry->execute([
                        'period_id' => $periodId,
                        'marketplace_id' => $mpId,
                        'value_cents' => $valueCents,
                        'orders_count' => $ordersCount,
                    ]);
                }
            }

            $pdo->commit();

            $log[] = "{$company['name']}: criado com " . count($marketplaceIds) . ' marketplace(s) e ' . count($referenceMonths) . ' meses de lançamentos.';
            $credentials[] = ['name' => $company['name'], 'email' => $email];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    $html = '<ul><li>' . implode('</li><li>', $log) . '</li></ul>';

    if (!empty($credentials)) {
        $html .= '<h2>Logins de demonstração</h2><p>Senha única para todas as contas: <code>' . DEMO_PASSWORD . '</code></p><table border="1" cellpadding="8" style="border-collapse:collapse;">';
        $html .= '<tr><th>Cliente</th><th>E-mail</th></tr>';
        foreach ($credentials as $cred) {
            $html .= '<tr><td>' . htmlspecialchars($cred['name'], ENT_QUOTES, 'UTF-8') . '</td><td>' . htmlspecialchars($cred['email'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
        }
        $html .= '</table>';
    }

    $html .= '<p><strong>Segurança:</strong> apague database/seed_demo.php e public/seed-demo.php depois de usar — são apenas para gerar a demonstração.</p>';

    render_result('Dados de demonstração processados', $html);
} catch (Throwable $e) {
    render_result('Erro ao gerar demonstração', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), true);
}
