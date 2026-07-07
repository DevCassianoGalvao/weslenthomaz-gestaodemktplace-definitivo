<?php
// Config central: variáveis de ambiente > config.local.php (gitignored) > defaults de dev.
// Nunca versionar credenciais reais — config.local.php fica fora do git.

$defaults = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'marketplace_gestao',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4',
    'APP_URL' => 'http://localhost:8000',
    'APP_ENV' => 'local',
    'ADMIN_EMAIL' => null,
    'ADMIN_PASSWORD' => null,
];

$config = $defaults;

$localFile = __DIR__ . '/config.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

foreach ($config as $key => $default) {
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        $config[$key] = $env;
    }
}

return $config;
