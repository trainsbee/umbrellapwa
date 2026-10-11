<?php
declare(strict_types=1);

/**
 * Dibujo del logo de UmbrellaApp con GD: el paraguas del icono
 * 'logo' de includes/icons.php, trazado en color acento sobre fondo oscuro.
 *   <path d="M23 12a11 11 0 0 0-22 0"/>   (cupula)
 *   <path d="M12 12v7a2 2 0 0 0 4 0"/>    (baston y mango)
 * Lo usan generate-icons.php y generate-splash.php.
 */

const BRAND_BG     = [0x0F, 0x10, 0x12];
const BRAND_STROKE = [0x7C, 0x74, 0xFF];

/** Color GD a partir de [r, g, b]. */
function brand_color(GdImage $img, array $rgb): int
{
    return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
}

/**
 * Dibuja el paraguas centrado en un cuadro de $size px con esquina en ($ox, $oy).
 * Coordenadas del icono en una cuadricula de 24x24 (trazo de 2 unidades).
 */
function brand_draw_logo(GdImage $img, float $ox, float $oy, float $size): void
{
    $u = $size / 24;
    // El dibujo ocupa y de 0 a 22: se baja 1 unidad para centrarlo.
    $x = fn (float $v): int => (int) round($ox + $v * $u);
    $y = fn (float $v): int => (int) round($oy + ($v + 1) * $u);
    $d = fn (float $v): int => (int) round($v * $u);

    $bg = brand_color($img, BRAND_BG);
    $ink = brand_color($img, BRAND_STROKE);
    $w = 2; // grosor del trazo

    // Cupula: media circunferencia superior, centro (12,12), radio 11
    imagefilledarc($img, $x(12), $y(12), $d(2 * (11 + $w / 2)), $d(2 * (11 + $w / 2)), 180, 360, $ink, IMG_ARC_PIE);
    imagefilledarc($img, $x(12), $y(12), $d(2 * (11 - $w / 2)), $d(2 * (11 - $w / 2)), 180, 360, $bg, IMG_ARC_PIE);

    // Baston: de (12,12) a (12,19)
    imagefilledrectangle($img, $x(12 - $w / 2), $y(12), $x(12 + $w / 2), $y(19), $ink);

    // Mango: media circunferencia inferior, centro (14,19), radio 2
    imagefilledarc($img, $x(14), $y(19), $d(2 * (2 + $w / 2)), $d(2 * (2 + $w / 2)), 0, 180, $ink, IMG_ARC_PIE);
    imagefilledarc($img, $x(14), $y(19), $d(2 * (2 - $w / 2)), $d(2 * (2 - $w / 2)), 0, 180, $bg, IMG_ARC_PIE);

    // Puntas redondeadas
    foreach ([[1, 12], [23, 12], [12, 12], [12, 19], [16, 19]] as [$px, $py]) {
        imagefilledellipse($img, $x($px), $y($py), $d($w), $d($w), $ink);
    }
}

/** Rectangulo relleno con esquinas redondeadas. */
function brand_rounded_rect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
{
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
    }
}

/**
 * Genera un icono cuadrado de $size px.
 *  'any'      -> cuadro redondeado con esquinas transparentes
 *  'full'     -> fondo completo (Apple / tiles / splash)
 *  'maskable' -> fondo completo, logo dentro de la zona segura de Android
 * Dibuja a 4x y reduce para obtener bordes suaves.
 */
function brand_icon(int $size, string $mode): GdImage
{
    $ss = 4;
    $big = $size * $ss;
    $canvas = imagecreatetruecolor($big, $big);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    imagealphablending($canvas, true);

    $bg = brand_color($canvas, BRAND_BG);
    if ($mode === 'any') {
        brand_rounded_rect($canvas, 0, 0, $big - 1, $big - 1, (int) ($big * 0.1875), $bg);
    } else {
        imagefilledrectangle($canvas, 0, 0, $big - 1, $big - 1, $bg);
    }

    $logo = $big * ($mode === 'maskable' ? 0.5 : 0.62);
    brand_draw_logo($canvas, ($big - $logo) / 2, ($big - $logo) / 2, $logo);

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
    return $out;
}
