<?php
declare(strict_types=1);

/**
 * Iconos de la app: Feather Icons (https://feathericons.com).
 * La libreria esta en assets/js/vendor/feather.min.js (local, funciona offline)
 * y feather.replace() convierte cada <i data-feather="..."> en un SVG.
 *
 * Uso en PHP: <?= icon('mail') ?>, <?= icon('bell', 18, 'mi-clase') ?>
 * Uso en HTML: <i data-feather="mail"></i>
 *
 * El logo (paraguas) no es de Feather: se dibuja en linea con el mismo trazo.
 */

const LOGO_PATHS = '<path d="M23 12a11 11 0 0 0-22 0"/><path d="M12 12v7a2 2 0 0 0 4 0"/>';

function icon(string $name, int $size = 16, string $class = ''): string
{
    $cls = htmlspecialchars(trim('icon ' . $class), ENT_QUOTES, 'UTF-8');

    if ($name === 'logo') {
        return '<svg class="' . $cls . ' icon-logo" width="' . $size . '" height="' . $size . '"'
            . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . LOGO_PATHS . '</svg>';
    }

    // Se reserva el espacio con width/height para que no salte al reemplazarse.
    return '<i data-feather="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '"'
        . ' width="' . $size . '" height="' . $size . '" aria-hidden="true"></i>';
}
