<?php

declare(strict_types=1);

// Execute uma vez no servidor para remover o status obsoleto sem apagar dados.
require __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;

$pdo = Database::connection();
$pdo->beginTransaction();
try {
    $pdo->exec("UPDATE clients SET status = 'paused' WHERE status = 'archived'");
    $pdo->exec("UPDATE periods SET reference_month = DATE_FORMAT(start_date, '%Y-%m') WHERE CAST(SUBSTRING(reference_month, 1, 4) AS UNSIGNED) < 2000");
    $pdo->exec("ALTER TABLE clients MODIFY status ENUM('active','paused') NOT NULL DEFAULT 'active'");
    $pdo->commit();
    echo "Migration completed.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage() . "\n";
}
