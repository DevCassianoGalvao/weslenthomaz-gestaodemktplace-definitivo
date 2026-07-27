<?php
// Copie este arquivo para config.local.php (gitignored) e preencha com valores reais.
// Em produção (cPanel), prefira configurar via variáveis de ambiente quando possível.

return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'marketplace_gestao',
    'DB_USER' => 'root',
    'DB_PASS' => '',
    'APP_URL' => 'http://localhost:8000',
    'APP_ENV' => 'local',

    // Preencha se o app for publicado numa subpasta do domínio (ex: cPanel sem
    // subdomínio dedicado). Deixe '' se o app vive na raiz do domínio/subdomínio.
    'BASE_PATH' => '', // ex: '/paineldemetricas'

    // Usados apenas pelo database/install.php para criar o admin inicial.
    // A senha deve ser trocada no primeiro login.
    'ADMIN_EMAIL' => 'admin@example.com',
    'ADMIN_PASSWORD' => 'troque-esta-senha',
];
