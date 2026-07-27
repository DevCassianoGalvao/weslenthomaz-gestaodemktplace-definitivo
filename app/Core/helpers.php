<?php

/**
 * Helper global de template — atalho pra App\Core\Url::to().
 * Use em toda URL interna (href, action, src, header Location): url('/dashboard').
 */
function url(string $path = '/'): string
{
    return \App\Core\Url::to($path);
}

function asset_url(string $path): string
{
    $file = __DIR__ . '/../../public' . $path;
    $version = is_file($file) ? (string) filemtime($file) : '1';
    return url($path) . '?v=' . rawurlencode($version);
}
