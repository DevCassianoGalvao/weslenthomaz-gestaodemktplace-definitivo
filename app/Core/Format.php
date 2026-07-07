<?php

namespace App\Core;

class Format
{
    public static function centsToBrl(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }
}
