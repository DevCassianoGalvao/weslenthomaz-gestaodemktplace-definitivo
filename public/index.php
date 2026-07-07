<?php

declare(strict_types=1);

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

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

use App\Controllers\AuthController;
use App\Controllers\ClientController;
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
$router->post('/marketplaces/{id}/toggle', [new MarketplaceController(), 'toggle'], $agencyOnly);

$router->get('/clients/{clientId}/periods', [new PeriodController(), 'index'], $agencyOnly);
$router->get('/clients/{clientId}/periods/new', [new PeriodController(), 'create'], $agencyOnly);
$router->post('/clients/{clientId}/periods', [new PeriodController(), 'store'], $agencyOnly);
$router->get('/periods/{id}/edit', [new PeriodController(), 'edit'], $agencyOnly);
$router->post('/periods/{id}/update', [new PeriodController(), 'update'], $agencyOnly);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
