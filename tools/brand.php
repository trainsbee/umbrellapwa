<?php
declare(strict_types=1);

/**
 * Draws the UmbrellaApp logo with GD: the umbrella from the 'logo' icon in
 * includes/icons.php, stroked in the accent color on a dark background.
 *   <path d="M23 12a11 11 0 0 0-22 0"/>   (canopy)
 *   <path d="M12 12v7a2 2 0 0 0 4 0"/>    (shaft and handle)
 * Used by generate-icons.php and generate-splash.php.
 */

const BRAND_BG     = [0x0F, 0x10, 0x12];
const BRAND_STROKE = [0x7C, 0x74, 0xFF];

/** GD color from [r, g, b]. */
function brand_color(GdImage $img, array $rgb): int
{
    return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
}

/**
 * Draws the umbrella centered in a $size px box whose corner is at ($ox, $oy).
 * Icon coordinates use a 24x24 grid (2-unit stroke).
 */
function brand_draw_logo(GdImage $img, float $ox, float $oy, float $size, array $inkRgb = BRAND_STROKE, array $bgRgb = BRAND_BG): void
{
    $u = $size / 24;
    // The drawing spans y 0 to 22: shift it down 1 unit to center it.
    $x = fn (float $v): int => (int) round($ox + $v * $u);
    $y = fn (float $v): int => (int) round($oy + ($v + 1) * $u);
    $d = fn (float $v): int => (int) round($v * $u);

    $bg = brand_color($img, $bgRgb);
    $ink = brand_color($img, $inkRgb);
    $w = 2; // stroke width

    // Canopy: upper half circle, center (12,12), radius 11
    imagefilledarc($img, $x(12), $y(12), $d(2 * (11 + $w / 2)), $d(2 * (11 + $w / 2)), 180, 360, $ink, IMG_ARC_PIE);
    imagefilledarc($img, $x(12), $y(12), $d(2 * (11 - $w / 2)), $d(2 * (11 - $w / 2)), 180, 360, $bg, IMG_ARC_PIE);

    // Shaft: from (12,12) to (12,19)
    imagefilledrectangle($img, $x(12 - $w / 2), $y(12), $x(12 + $w / 2), $y(19), $ink);

    // Handle: lower half circle, center (14,19), radius 2
    imagefilledarc($img, $x(14), $y(19), $d(2 * (2 + $w / 2)), $d(2 * (2 + $w / 2)), 0, 180, $ink, IMG_ARC_PIE);
    imagefilledarc($img, $x(14), $y(19), $d(2 * (2 - $w / 2)), $d(2 * (2 - $w / 2)), 0, 180, $bg, IMG_ARC_PIE);

    // Round caps
    foreach ([[1, 12], [23, 12], [12, 12], [12, 19], [16, 19]] as [$px, $py]) {
        imagefilledellipse($img, $x($px), $y($py), $d($w), $d($w), $ink);
    }
}

/** Filled rectangle with rounded corners. */
function brand_rounded_rect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
{
    imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
        imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
    }
}

/**
 * Generates a square icon of $size px.
 *  'any'      -> rounded tile with transparent corners
 *  'full'     -> full background (Apple / tiles / splash)
 *  'maskable' -> full background, logo inside Android's safe zone
 * Draws at 4x and scales down for smooth edges.
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

/**
 * Monochrome notification badge (Android status bar): a white umbrella on a
 * transparent background. Drawn white-on-black at 4x, then the brightness
 * becomes the alpha channel.
 */
function brand_badge(int $size): GdImage
{
    $ss = 4;
    $big = $size * $ss;
    $canvas = imagecreatetruecolor($big, $big);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 0, 0, 0));
    $logo = $big * 0.8;
    brand_draw_logo($canvas, ($big - $logo) / 2, ($big - $logo) / 2, $logo, [255, 255, 255], [0, 0, 0]);

    $small = imagecreatetruecolor($size, $size);
    imagecopyresampled($small, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $level = imagecolorat($small, $x, $y) & 0xFF;
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, 127 - intdiv($level * 127, 255)));
        }
    }
    return $out;
}

