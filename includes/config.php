<?php
declare(strict_types=1);

/**
 * Configuracion general de la app.
 * Los secretos (API keys, BD) NO van aqui: van en includes/config.local.php,
 * que esta en .gitignore.
 */

const APP_NAME        = 'UmbrellaApp';
const APP_SHORT_NAME  = 'Umbrella';
const APP_DESCRIPTION = 'UmbrellaApp - gestión interna';
const APP_VERSION     = '0.3.0';
const APP_LANG        = 'es';

// Colores de marca (deben coincidir con manifest.webmanifest)
const THEME_COLOR      = '#0f1012';
const BACKGROUND_COLOR = '#0f1012';

require_once __DIR__ . '/icons.php';

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/** Escapa texto para HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * URL de un asset con ?v=<fecha de modificacion>. Cada vez que el archivo
 * cambia, su URL cambia sola y ni el navegador ni el service worker
 * pueden servir la copia vieja.
 */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : APP_VERSION;
    return $path . '?v=' . $version;
}
