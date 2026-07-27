<?php

declare(strict_types=1);

// Autoload das dependências do Composer (PhpSpreadsheet e afins)
require __DIR__ . '/../vendor/autoload.php';

// Autoload simples PSR-4-like: App\Foo\Bar -> app/Foo/Bar.php
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/../app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/../app/Core/helpers.php';

$config = require __DIR__ . '/../config/config.php';
$basePath = rtrim($config['BASE_PATH'] ?? '', '/');

// Cookie de sessão escopado ao BASE_PATH quando o app roda numa subpasta do
// domínio (ex: /paineldemetricas) — evita que a sessão vaze pra outros
// apps hospedados no mesmo domínio.
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_path' => $basePath !== '' ? $basePath . '/' : '/',
]);

use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\DashboardController;
use App\Controllers\ExportController;
use App\Controllers\HistoryController;
use App\Controllers\HomeController;
use App\Controllers\MarketplaceController;
use App\Controllers\PeriodController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$router = new Router();

$router->get('/login', [new AuthController(), 'showLogin']);
$router->post('/login', [new AuthController(), 'login']);
$router->post('/logout', [new AuthController(), 'logout']);

$router->get('/', [new HomeController(), 'index'], [
    fn() => AuthMiddleware::handle(),
]);

$router->get('/dashboard', [new HomeController(), 'dashboard'], [
    fn() => AuthMiddleware::handle(),
]);

$router->get('/dashboard/export', [new ExportController(), 'ownDashboard'], [
    fn() => AuthMiddleware::handle(),
    RoleMiddleware::only(['client']),
]);

$router->get('/dashboard/comparativo/export', [new ExportController(), 'comparativo'], [
    fn() => AuthMiddleware::handle(),
    RoleMiddleware::only(['admin', 'operator']),
]);

$agencyOnly = [
    fn() => AuthMiddleware::handle(),
    RoleMiddleware::only(['admin', 'operator']),
];

$router->get('/clients', [new ClientController(), 'index'], $agencyOnly);
$router->get('/clients/new', [new ClientController(), 'create'], $agencyOnly);
$router->post('/clients', [new ClientController(), 'store'], $agencyOnly);
$router->get('/clients/{id}/edit', [new ClientController(), 'edit'], $agencyOnly);
$router->post('/clients/{id}/update', [new ClientController(), 'update'], $agencyOnly);

$router->get('/marketplaces', [new MarketplaceController(), 'index'], $agencyOnly);
$router->post('/marketplaces', [new MarketplaceController(), 'store'], $agencyOnly);
$router->post('/marketplaces/{id}/update', [new MarketplaceController(), 'update'], $agencyOnly);
$router->post('/marketplaces/{id}/toggle', [new MarketplaceController(), 'toggle'], $agencyOnly);

$router->get('/clients/{clientId}/periods', [new PeriodController(), 'index'], $agencyOnly);
$router->get('/clients/{clientId}/periods/new', [new PeriodController(), 'create'], $agencyOnly);
$router->post('/clients/{clientId}/periods', [new PeriodController(), 'store'], $agencyOnly);
$router->get('/periods/{id}/edit', [new PeriodController(), 'edit'], $agencyOnly);
$router->post('/periods/{id}/update', [new PeriodController(), 'update'], $agencyOnly);

$router->get('/clients/{id}/dashboard', [new DashboardController(), 'client'], $agencyOnly);
$router->get('/clients/{id}/dashboard/export', [new ExportController(), 'forClient'], $agencyOnly);

$adminOnly = [
    fn() => AuthMiddleware::handle(),
    RoleMiddleware::only(['admin']),
];

$router->post('/clients/{id}/delete', [new ClientController(), 'delete'], $adminOnly);

$router->get('/history', [new HistoryController(), 'index'], $adminOnly);
$router->get('/history/export', [new HistoryController(), 'exportCsv'], $adminOnly);
$router->post('/history/clear-client', [new HistoryController(), 'clearClient'], $adminOnly);
$router->post('/history/clear-all', [new HistoryController(), 'clearAll'], $adminOnly);

// Remove o BASE_PATH do URI antes de rotear — as rotas acima são declaradas
// sempre "relativas" (ex: '/dashboard'), nunca com o prefixo da subpasta.
$requestUri = $_SERVER['REQUEST_URI'];
if ($basePath !== '' && str_starts_with($requestUri, $basePath)) {
    $requestUri = substr($requestUri, strlen($basePath));
    if ($requestUri === '' || $requestUri[0] !== '/') {
        $requestUri = '/' . ltrim($requestUri, '/');
    }
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $requestUri);
