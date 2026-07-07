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
use App\Controllers\HomeController;
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

$router->get('/clients', [new HomeController(), 'clients'], [
    fn() => AuthMiddleware::handle(),
    RoleMiddleware::only(['admin', 'operator']),
]);

$router->get('/dashboard', [new HomeController(), 'dashboard'], [
    fn() => AuthMiddleware::handle(),
]);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
