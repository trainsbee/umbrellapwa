<?php
declare(strict_types=1);

/**
 * Dibujo del logo de UmbrellaApp con GD (carita: dos ojos + sonrisa).
 * Lo usan generate-icons.php y generate-splash.php.
 * Coordenadas en una cuadricula de 512x512.
 */

const BRAND_BG    = [0x1C, 0x1C, 0x1C];
const BRAND_EYES  = [0xEB, 0xEB, 0xEB];
const BRAND_SMILE = [0x7C, 0x5C, 0xFC];

/** Color GD a partir de [r, g, b]. */
function brand_color(GdImage $img, array $rgb): int
{
    return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
}

/**
 * Dibuja la carita dentro de un cuadro de $size px con esquina en ($ox, $oy).
 */
function brand_draw_face(GdImage $img, float $ox, float $oy, float $size): void
{
    $s = $size / 512;
    $p = fn (float $v): int => (int) round($v * $s);
    $bg = brand_color($img, BRAND_BG);
    $eyes = brand_color($img, BRAND_EYES);
    $smile = brand_color($img, BRAND_SMILE);

    // Ojos
    imagefilledrectangle($img, (int) $ox + $p(125), (int) $oy + $p(165), (int) $ox + $p(187), (int) $oy + $p(270), $eyes);
    imagefilledrectangle($img, (int) $ox + $p(325), (int) $oy + $p(165), (int) $ox + $p(387), (int) $oy + $p(270), $eyes);

    // Sonrisa: arco de 10 a 170 grados con puntas redondeadas
    $cx = $ox + $p(256);
    $cy = $oy + $p(307);
    $r = 120;
    $half = 31;
    imagefilledarc($img, (int) $cx, (int) $cy, $p(($r + $half) * 2), $p(($r + $half) * 2), 10, 170, $smile, IMG_ARC_PIE);
    imagefilledarc($img, (int) $cx, (int) $cy, $p(($r - $half) * 2), $p(($r - $half) * 2), 0, 180, $bg, IMG_ARC_PIE);
    foreach ([10, 170] as $deg) {
        $ex = $cx + $p($r * cos(deg2rad($deg)));
        $ey = $cy + $p($r * sin(deg2rad($deg)));
        imagefilledellipse($img, (int) $ex, (int) $ey, $p($half * 2), $p($half * 2), $smile);
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
 *  'full'     -> fondo completo (Apple / tiles), logo a tamano normal
 *  'maskable' -> fondo completo, logo reducido a la zona segura (80%)
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
        brand_draw_face($canvas, 0, 0, $big);
    } elseif ($mode === 'maskable') {
        imagefilledrectangle($canvas, 0, 0, $big - 1, $big - 1, $bg);
        $inner = $big * 0.8;
        brand_draw_face($canvas, ($big - $inner) / 2, ($big - $inner) / 2, $inner);
    } else {
        imagefilledrectangle($canvas, 0, 0, $big - 1, $big - 1, $bg);
        brand_draw_face($canvas, 0, 0, $big);
    }

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
    return $out;
}
