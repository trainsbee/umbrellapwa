<?php
declare(strict_types=1);

/**
 * Genera las pantallas de arranque (splash) de iOS / iPadOS en assets/splash/
 * y el archivo includes/splash-links.php con las etiquetas <link> para cada
 * dispositivo. Android y Windows no las necesitan: usan el manifest.
 *
 * Uso: php tools/generate-splash.php
 * Para agregar un dispositivo nuevo, agregalo a $devices y vuelve a ejecutar.
 */

require __DIR__ . '/brand.php';

$root = dirname(__DIR__);
$outDir = $root . '/assets/splash';

// [nombre, ancho CSS, alto CSS, pixel ratio] en vertical
$devices = [
    ['iPhone 16/17 Pro Max',            440, 956,  3],
    ['iPhone Air',                      420, 912,  3],
    ['iPhone 16/17 Pro',                402, 874,  3],
    ['iPhone 14 Pro Max, 15/16 Plus',   430, 932,  3],
    ['iPhone 14 Pro, 15, 16',           393, 852,  3],
    ['iPhone 12/13 Pro Max, 14 Plus',   428, 926,  3],
    ['iPhone 12, 13, 14',               390, 844,  3],
    ['iPhone X, XS, 11 Pro, 12/13 mini', 375, 812, 3],
    ['iPhone XS Max, 11 Pro Max',       414, 896,  3],
    ['iPhone XR, 11',                   414, 896,  2],
    ['iPhone 8 Plus',                   414, 736,  3],
    ['iPhone 8, SE 2/3',                375, 667,  2],
    ['iPhone SE 1',                     320, 568,  2],
    ['iPad Pro 13 M4',                  1032, 1376, 2],
    ['iPad Pro 12.9',                   1024, 1366, 2],
    ['iPad Pro 11 M4',                  834, 1210, 2],
    ['iPad Pro 11, Air 11',             834, 1194, 2],
    ['iPad Air 10.9, iPad 10',          820, 1180, 2],
    ['iPad Air 10.5',                   834, 1112, 2],
    ['iPad 10.2',                       810, 1080, 2],
    ['iPad 9.7, mini 5',                768, 1024, 2],
    ['iPad mini 6/7',                   744, 1133, 2],
];

foreach (glob("$outDir/*.png") as $old) {
    unlink($old);
}

$links = [];
foreach ($devices as [$name, $cssW, $cssH, $ratio]) {
    foreach (['portrait', 'landscape'] as $orientation) {
        $w = ($orientation === 'portrait' ? $cssW : $cssH) * $ratio;
        $h = ($orientation === 'portrait' ? $cssH : $cssW) * $ratio;
        $file = "splash-{$w}x{$h}.png";

        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, brand_color($img, BRAND_BG));
        $logo = (int) round(min($w, $h) * 0.28);
        imagecopy($img, brand_icon($logo, 'full'), intdiv($w - $logo, 2), intdiv($h - $logo, 2), 0, 0, $logo, $logo);
        imagepng($img, "$outDir/$file", 9);

        $media = "(device-width: {$cssW}px) and (device-height: {$cssH}px) and "
            . "(-webkit-device-pixel-ratio: {$ratio}) and (orientation: {$orientation})";
        $links[] = "<!-- {$name} ({$orientation}) -->\n"
            . "<link rel=\"apple-touch-startup-image\" media=\"{$media}\" href=\"assets/splash/{$file}\">";
        echo "ok  $file  $name ($orientation)\n";
    }
}

$php = "<?php\n// Generado por tools/generate-splash.php -- no editar a mano.\n?>\n"
    . implode("\n", $links) . "\n";
file_put_contents($root . '/includes/splash-links.php', $php);
echo "ok  includes/splash-links.php (" . count($links) . " enlaces)\n";
