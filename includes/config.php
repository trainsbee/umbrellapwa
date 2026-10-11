<?php
declare(strict_types=1);

/**
 * Configuracion general de la app.
 * Los secretos (API keys, BD) NO van aqui: van en includes/config.local.php,
 * que esta en .gitignore.
 */

const APP_NAME        = 'UmbrellaApp';
const APP_SHORT_NAME  = 'Umbrella';
const APP_DESCRIPTION = 'UmbrellaApp - gestion interna';
const APP_VERSION     = '0.1.0';
const APP_LANG        = 'es';

// Colores de marca (deben coincidir con manifest.webmanifest)
const THEME_COLOR      = '#1C1C1C';
const BACKGROUND_COLOR = '#1C1C1C';

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/** Escapa texto para HTML. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** URL de un asset con ?v= para romper cache al cambiar de version. */
function asset(string $path): string
{
    return $path . '?v=' . APP_VERSION;
}
