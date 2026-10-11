<?php
declare(strict_types=1);

/**
 * Iconos SVG en linea. Todos comparten el mismo estilo de trazo que el
 * logo de Umbrella: viewBox 24x24, stroke 2, puntas y uniones redondeadas,
 * y heredan el color del texto (currentColor).
 *
 * Uso: <?= icon('mail') ?>  o  <?= icon('logo', 32, 'brand-logo') ?>
 * Para agregar un icono, agrega sus trazos al arreglo (estilo Feather).
 */

const ICONS = [
    'logo'         => '<path d="M23 12a11 11 0 0 0-22 0"/><path d="M12 12v7a2 2 0 0 0 4 0"/>',
    'mail'         => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
    'lock'         => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    'eye'          => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
    'eye-off'      => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="m1 1 22 22"/>',
    'log-in'       => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
    'log-out'      => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
    'download'     => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
    'share'        => '<path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="m16 6-4-4-4 4"/><path d="M12 2v13"/>',
    'refresh-cw'   => '<path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
    'check'        => '<path d="M20 6 9 17l-5-5"/>',
    'x'            => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    'alert-circle' => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
    'wifi-off'     => '<path d="m1 1 22 22"/><path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/><path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/><path d="M10.71 5.05A16 16 0 0 1 22.58 9"/><path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/><path d="M8.53 16.11a6 6 0 0 1 6.95 0"/><path d="M12 20h.01"/>',
];

function icon(string $name, int $size = 16, string $class = ''): string
{
    $paths = ICONS[$name] ?? ICONS['alert-circle'];
    $cls = trim('icon icon-' . $name . ' ' . $class);
    return '<svg class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" width="' . $size . '" height="' . $size . '"'
        . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
        . $paths . '</svg>';
}
