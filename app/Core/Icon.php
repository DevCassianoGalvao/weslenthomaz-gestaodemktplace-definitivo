<?php

namespace App\Core;

/**
 * Set único de ícones (estilo outline, 24x24, stroke) usado em todo o sistema —
 * evita depender de fonte de ícones externa (sem build step, funciona offline).
 */
class Icon
{
    private const ICONS = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users' => '<path d="M17 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 5 18.5V20"/><circle cx="9.5" cy="8" r="3.5"/><path d="M19 20v-1.5a3.5 3.5 0 0 0-2.5-3.36"/><path d="M15 4.13a3.5 3.5 0 0 1 0 6.75"/>',
        'shopping-bag' => '<path d="M6 8h12l-1 12.5a1.5 1.5 0 0 1-1.5 1.5H8.5A1.5 1.5 0 0 1 7 20.5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'log-out' => '<path d="M10 17v.5A2.5 2.5 0 0 0 12.5 20H17a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-4.5A2.5 2.5 0 0 0 10 6.5V7"/><path d="M14 12H4M4 12l3-3M4 12l3 3"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'pencil' => '<path d="M16.5 4.5a2.1 2.1 0 0 1 3 3L8 19l-4 1 1-4Z"/>',
        'download' => '<path d="M12 4v11m0 0 4-4m-4 4-4-4"/><path d="M5 19h14"/>',
        'bar-chart' => '<path d="M5 20V10M12 20V4M19 20v-7"/>',
        'external-link' => '<path d="M14 4h6v6"/><path d="M20 4 10 14"/><path d="M18 13v5.5A1.5 1.5 0 0 1 16.5 20h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H11"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5.5"/>',
        'store' => '<path d="M4 9.5 5 4h14l1 5.5"/><path d="M4 9.5A2 2 0 0 0 6 11.5a2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2 2 2 0 0 0 2-2 2 2 0 0 0 2 2A2 2 0 0 0 20 9.5"/><path d="M5.5 11.5V19a1 1 0 0 0 1 1H10v-4.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1V20h3.5a1 1 0 0 0 1-1v-7.5"/>',
    ];

    public static function svg(string $name, int $size = 20): string
    {
        $paths = self::ICONS[$name] ?? '';
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
            . 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
            . 'class="icon icon-' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">' . $paths . '</svg>';
    }
}
