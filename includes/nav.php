<?php
declare(strict_types=1);

/**
 * Secciones principales de la app. Las usan el menu lateral y la barra
 * de navegacion inferior. Clave => [titulo, icono Feather, archivo].
 */
const NAV_ITEMS = [
    'inicio'        => ['Inicio',        'home',        'inicio.php'],
    'ventas'        => ['Ventas',        'trending-up', 'ventas.php'],
    'oportunidades' => ['Oportunidades', 'users',       'oportunidades.php'],
    'reportes'      => ['Reportes',      'bar-chart-2', 'reportes.php'],
];

// Usuario de muestra hasta conectar el login con Supabase.
const DEMO_USER = ['nombre' => 'Usuario Umbrella', 'rol' => 'Sin rol asignado'];

/** Iniciales para el avatar: "Usuario Umbrella" -> "UU". */
function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out;
}
