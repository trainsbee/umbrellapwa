<?php
declare(strict_types=1);

/**
 * Draws the UmbrellaApp logo with GD: the umbrella from the 'logo' icon in
 * includes/icons.php, as a white line on a violet diagonal gradient.
 *   <path d="M23 12a11 11 0 0 0-22 0"/>   (canopy)
 *   <path d="M12 12v7a2 2 0 0 0 4 0"/>    (shaft and handle)
 * Used by generate-icons.php and generate-splash.php.
 */

const BRAND_BG     = [0x0F, 0x10, 0x12];
const BRAND_STROKE = [0x7C, 0x74, 0xFF];

// Icon gradient: top-left -> bottom-right (same stops as --brand-gradient in app.css)
const BRAND_GRADIENT_FROM = [0xA4, 0x8C, 0xFF];
const BRAND_GRADIENT_TO   = [0x4C, 0x3B, 0xE0];

/** GD color from [r, g, b]. */
function brand_color(GdImage $img, array $rgb): int
{
    return imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
}

/**
 * Draws the umbrella (same SVG as LOGO_PATHS in includes/icons.php) centered
 * in a $size px box whose corner is at ($ox, $oy). Icon coordinates use a
 * 24x24 grid with a $w-unit stroke:
 *   <path d="M23 12a11 11 0 0 0-22 0"/>   (canopy)
 *   <path d="M12 12v7a2 2 0 0 0 4 0"/>    (shaft and handle)
 * $bgRgb carves the inside of the arcs.
 */
function brand_draw_logo(GdImage $img, float $ox, float $oy, float $size, array $inkRgb = BRAND_STROKE, array $bgRgb = BRAND_BG, float $w = 2): void
{
    $u = $size / 24;
    // The drawing spans y 0 to 22: shift it down 1 unit to center it.
    $x = fn (float $v): int => (int) round($ox + $v * $u);
    $y = fn (float $v): int => (int) round($oy + ($v + 1) * $u);
    $d = fn (float $v): int => (int) round($v * $u);

    $bg = brand_color($img, $bgRgb);
    $ink = brand_color($img, $inkRgb);

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

/**
 * White-on-black mask of the logo, $size px, with the logo $scale of the box.
 * Brightness = coverage, so it can be used as an alpha channel.
 */
function brand_logo_mask(int $size, float $scale, float $stroke = 2): GdImage
{
    $ss = 4;
    $big = $size * $ss;
    $canvas = imagecreatetruecolor($big, $big);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 0, 0, 0));
    $logo = $big * $scale;
    brand_draw_logo($canvas, ($big - $logo) / 2, ($big - $logo) / 2, $logo, [255, 255, 255], [0, 0, 0], $stroke);

    $small = imagecreatetruecolor($size, $size);
    imagecopyresampled($small, $canvas, 0, 0, 0, 0, $size, $size, $big, $big);
    return $small;
}

/** Color of the diagonal gradient at ($x, $y) in a $size box. @return array{int, int, int} */
function brand_gradient_at(int $x, int $y, int $size): array
{
    $t = ($x + $y) / max(1, 2 * ($size - 1));
    $rgb = [];
    foreach ([0, 1, 2] as $i) {
        $rgb[] = (int) round(BRAND_GRADIENT_FROM[$i] + (BRAND_GRADIENT_TO[$i] - BRAND_GRADIENT_FROM[$i]) * $t);
    }
    return $rgb;
}

/**
 * Generates a square icon of $size px: white umbrella on the brand gradient.
 *  'any'      -> rounded tile with transparent corners
 *  'full'     -> full background (Apple / tiles / splash)
 *  'maskable' -> full background, logo inside Android's safe zone
 */
function brand_icon(int $size, string $mode): GdImage
{
    // Thicker stroke at small sizes so the umbrella stays readable
    $stroke = $size <= 32 ? 2.6 : 2.2;
    $mask = brand_logo_mask($size, $mode === 'maskable' ? 0.5 : 0.6, $stroke);
    $radius = $mode === 'any' ? $size * 0.225 : 0;

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            [$r, $g, $b] = brand_gradient_at($x, $y, $size);
            // Soft light from the top-left corner
            $glow = max(0.0, 1 - hypot($x, $y) / ($size * 0.9)) * 0.18;
            $r += (255 - $r) * $glow; $g += (255 - $g) * $glow; $b += (255 - $b) * $glow;
            // White logo on top
            $a = (imagecolorat($mask, $x, $y) & 0xFF) / 255;
            $r = $r + (255 - $r) * $a; $g = $g + (255 - $g) * $a; $b = $b + (255 - $b) * $a;
            $alpha = $radius > 0 ? brand_corner_coverage($x, $y, $size, $radius) : 1.0;
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, (int) round($r), (int) round($g), (int) round($b), 127 - (int) round($alpha * 127)));
        }
    }
    return $out;
}

/** Anti-aliased coverage (0..1) of pixel ($x, $y) inside a rounded square. */
function brand_corner_coverage(int $x, int $y, int $size, float $radius): float
{
    $cx = min(max($x + 0.5, $radius), $size - $radius);
    $cy = min(max($y + 0.5, $radius), $size - $radius);
    $distance = hypot($x + 0.5 - $cx, $y + 0.5 - $cy);
    return max(0.0, min(1.0, $radius - $distance + 0.5));
}

/**
 * Monochrome notification badge (Android status bar): a white umbrella on a
 * transparent background. Drawn white-on-black at 4x, then the brightness
 * becomes the alpha channel.
 */
function brand_badge(int $size): GdImage
{
    $mask = brand_logo_mask($size, 0.8);
    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    for ($x = 0; $x < $size; $x++) {
        for ($y = 0; $y < $size; $y++) {
            $level = imagecolorat($mask, $x, $y) & 0xFF;
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 255, 255, 255, 127 - intdiv($level * 127, 255)));
        }
    }
    return $out;
}
