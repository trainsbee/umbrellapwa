<?php
declare(strict_types=1);

/**
 * Main app sections, used by the sidebar and the bottom navigation bar.
 * Key => [label, Feather icon, file].
 */
const NAV_ITEMS = [
    'home'          => ['Home',          'home',        'home.php'],
    'sales'         => ['Sales',         'trending-up', 'sales.php'],
    'opportunities' => ['Opportunities', 'users',       'opportunities.php'],
    'reports'       => ['Reports',       'bar-chart-2', 'reports.php'],
];

/** Avatar initials: "Umbrella User" -> "UU". */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out;
}
