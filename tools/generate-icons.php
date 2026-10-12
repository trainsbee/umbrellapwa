<?php
declare(strict_types=1);

/**
 * Generates every PWA icon into assets/icons/.
 * Usage: php tools/generate-icons.php
 */

require __DIR__ . '/brand.php';

$dir = dirname(__DIR__) . '/assets/icons';

$icons = [
    // file                       size    mode
    ['favicon-16.png',            16,     'any'],
    ['favicon-32.png',            32,     'any'],
    ['apple-touch-icon.png',      180,    'full'],     // iPhone / iPad / macOS Safari
    ['icon-144.png',              144,    'full'],     // Windows tile
    ['icon-192.png',              192,    'any'],      // Android / Chrome / Edge
    ['icon-512.png',              512,    'any'],
    ['icon-maskable-192.png',     192,    'maskable'], // Android adaptive icons
    ['icon-maskable-512.png',     512,    'maskable'],
];

foreach ($icons as [$file, $size, $mode]) {
    imagepng(brand_icon($size, $mode), "$dir/$file", 9);
    echo "ok  $file ({$size}x{$size}, $mode)\n";
}

// Monochrome badge for notifications (Android status bar)
imagepng(brand_badge(96), "$dir/badge-96.png", 9);
echo "ok  badge-96.png (96x96, monochrome)\n";

// favicon.ico with embedded 16 and 32 px PNGs (modern ICO format)
$entries = [];
foreach ([16, 32] as $size) {
    ob_start();
    imagepng(brand_icon($size, 'any'), null, 9);
    $entries[] = [$size, ob_get_clean()];
}
$header = pack('vvv', 0, 1, count($entries));
$dirData = '';
$imgData = '';
$offset = 6 + 16 * count($entries);
foreach ($entries as [$size, $png]) {
    $dirData .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
    $imgData .= $png;
    $offset += strlen($png);
}
file_put_contents("$dir/favicon.ico", $header . $dirData . $imgData);
echo "ok  favicon.ico (16+32)\n";
