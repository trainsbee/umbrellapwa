<?php
declare(strict_types=1);

/**
 * General app configuration.
 * Secrets (API keys, database) do NOT belong here: put them in
 * includes/config.local.php, which is git-ignored.
 */

const APP_NAME        = 'UmbrellaApp';
const APP_SHORT_NAME  = 'Umbrella';
const APP_DESCRIPTION = 'UmbrellaApp - gestión interna';
const APP_VERSION     = '0.3.0';
const APP_LANG        = 'es';

// Brand colors (must match manifest.webmanifest)
const THEME_COLOR      = '#0f1012';
const BACKGROUND_COLOR = '#0f1012';

require_once __DIR__ . '/icons.php';

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/** Escapes text for HTML output. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Asset URL with ?v=<last modified time>. Whenever the file changes its URL
 * changes too, so neither the browser nor the service worker can serve a
 * stale copy.
 */
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : APP_VERSION;
    return $path . '?v=' . $version;
}
