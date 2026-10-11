<?php
declare(strict_types=1);

/**
 * Main app sections, used by the sidebar and the bottom navigation bar.
 * Key => [label, Feather icon, file].
 */
const NAV_ITEMS = [
    'home'          => ['Inicio',        'home',        'home.php'],
    'sales'         => ['Ventas',        'trending-up', 'sales.php'],
    'opportunities' => ['Oportunidades', 'users',       'opportunities.php'],
    'reports'       => ['Reportes',      'bar-chart-2', 'reports.php'],
];

// Sample user until the login is connected to Supabase.
const DEMO_USER = ['name' => 'Usuario Umbrella', 'role' => 'Sin rol asignado'];

/** Avatar initials: "Usuario Umbrella" -> "UU". */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out;
}
