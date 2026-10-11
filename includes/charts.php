<?php
declare(strict_types=1);

/**
 * SVG line charts (decorative, used by the landing page).
 * line_chart_paths() turns a series of values into a smooth curve
 * (Catmull-Rom -> Bezier) plus its filled area.
 */

/**
 * @param float[] $values values from 0 to 100
 * @return array{line: string, area: string, end: array{0: float, 1: float}}
 */
function line_chart_paths(array $values, float $width, float $height, float $padY = 12): array
{
    $n = count($values);
    $pts = [];
    foreach (array_values($values) as $i => $v) {
        $x = $n > 1 ? $i * $width / ($n - 1) : 0;
        $y = $padY + (1 - max(0, min(100, $v)) / 100) * ($height - 2 * $padY);
        $pts[] = [$x, $y];
    }

    $f = fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');
    $d = 'M' . $f($pts[0][0]) . ' ' . $f($pts[0][1]);
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)];
        $p1 = $pts[$i];
        $p2 = $pts[$i + 1];
        $p3 = $pts[min($n - 1, $i + 2)];
        $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $p1[1] + ($p2[1] - $p0[1]) / 6];
        $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $p2[1] - ($p3[1] - $p1[1]) / 6];
        $d .= ' C' . $f($c1[0]) . ' ' . $f($c1[1]) . ', ' . $f($c2[0]) . ' ' . $f($c2[1]) . ', ' . $f($p2[0]) . ' ' . $f($p2[1]);
    }

    $last = $pts[$n - 1];
    $area = $d . ' L' . $f($last[0]) . ' ' . $f($height) . ' L0 ' . $f($height) . ' Z';
    return ['line' => $d, 'area' => $area, 'end' => $last];
}
