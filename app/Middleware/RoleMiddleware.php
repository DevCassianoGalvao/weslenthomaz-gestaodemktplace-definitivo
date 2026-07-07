<?php

namespace App\Middleware;

use App\Core\Auth;

class RoleMiddleware
{
    /**
     * Retorna um middleware que só deixa passar os papéis informados.
     * Uso: RoleMiddleware::only(['admin', 'operator'])
     */
    public static function only(array $roles): callable
    {
        return function () use ($roles) {
            if (!in_array(Auth::role(), $roles, true)) {
                http_response_code(403);
                require __DIR__ . '/../Views/errors/403.php';
                exit;
            }
        };
    }
}
