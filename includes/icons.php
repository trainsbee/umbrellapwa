<?php
declare(strict_types=1);

/**
 * App icons: Feather Icons (https://feathericons.com).
 * The library lives in assets/js/vendor/feather.min.js (local, works offline)
 * and feather.replace() turns every <i data-feather="..."> into an SVG.
 *
 * In PHP: <?= icon('mail') ?>, <?= icon('bell', 18, 'my-class') ?>
 * In HTML: <i data-feather="mail"></i>
 *
 * The logo (umbrella) is not a Feather icon: a solid scalloped canopy plus the
 * shaft and hook in the same stroke style. tools/brand.php draws the same shape.
 */

const LOGO_PATHS = '<path d="M1 12a11 11 0 0 1 22 0a3.667 2 0 0 0-7.333 0a3.667 2 0 0 0-7.334 0a3.667 2 0 0 0-7.333 0z" fill="currentColor" stroke="none"/>'
    . '<path d="M12 .75v1"/><path d="M12 11v8a2 2 0 0 0 4 0"/>';

function icon(string $name, int $size = 16, string $class = ''): string
{
    $cls = htmlspecialchars(trim('icon ' . $class), ENT_QUOTES, 'UTF-8');

    if ($name === 'logo') {
        return '<svg class="' . $cls . ' icon-logo" width="' . $size . '" height="' . $size . '"'
            . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
            . LOGO_PATHS . '</svg>';
    }

    // width/height reserve the space so the layout does not jump when replaced.
    return '<i data-feather="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" class="' . $cls . '"'
        . ' width="' . $size . '" height="' . $size . '" aria-hidden="true"></i>';
}
