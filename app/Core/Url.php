<?php

namespace App\Core;

/**
 * Resolve URLs absolutas considerando BASE_PATH (config), pro app funcionar
 * tanto na raiz de um (sub)domínio quanto numa subpasta dele (ex: cPanel sem
 * subdomínio dedicado — /weslenmarketplaces). Nunca monte "href=\"/algo\""
 * direto numa view; sempre passe por Url::to()/url().
 */
class Url
{
    private static ?string $basePath = null;

    public static function to(string $path = '/'): string
    {
        $base = self::basePath();

        if ($path === '' || $path === '/') {
            return $base === '' ? '/' : $base . '/';
        }

        return $base . $path;
    }

    public static function basePath(): string
    {
        if (self::$basePath === null) {
            $config = require __DIR__ . '/../../config/config.php';
            self::$basePath = rtrim($config['BASE_PATH'] ?? '', '/');
        }

        return self::$basePath;
    }
}
