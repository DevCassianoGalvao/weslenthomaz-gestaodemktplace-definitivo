<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_client_id'] = $user['client_id'];
        $_SESSION['user_marketplace_ids'] = $user['role'] === 'operator'
            ? User::marketplaceIds((int) $user['id'])
            : [];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function name(): ?string
    {
        return $_SESSION['user_name'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function clientId(): ?int
    {
        return $_SESSION['user_client_id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isOperator(): bool
    {
        return self::role() === 'operator';
    }

    public static function isClient(): bool
    {
        return self::role() === 'client';
    }

    /**
     * IDs de marketplace aos quais o usuário logado está restrito, ou null quando não
     * há restrição (admin, cliente final, ou operador sem canais marcados — vê tudo).
     * @return int[]|null
     */
    public static function allowedMarketplaceIds(): ?array
    {
        if (self::role() !== 'operator') {
            return null;
        }

        $ids = $_SESSION['user_marketplace_ids'] ?? [];

        return empty($ids) ? null : $ids;
    }
}
