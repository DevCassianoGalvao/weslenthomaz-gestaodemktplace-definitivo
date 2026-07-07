<?php

/**
 * Helper global de template — atalho pra App\Core\Url::to().
 * Use em toda URL interna (href, action, src, header Location): url('/dashboard').
 */
function url(string $path = '/'): string
{
    return \App\Core\Url::to($path);
}
