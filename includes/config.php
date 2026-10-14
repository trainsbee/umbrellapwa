<?php
declare(strict_types=1);

/**
 * General app configuration.
 * Secrets (API keys, database) do NOT belong here: put them in
 * includes/config.local.php, which is git-ignored.
 */

const APP_NAME        = 'UmbrellaApp';
const APP_SHORT_NAME  = 'Umbrella';
const APP_DESCRIPTION = 'UmbrellaApp - your operation in real time';
const APP_VERSION     = '0.4.0';
const APP_LANG        = 'en';

// Business time zone: "today" and every date range are computed here (the
// operation runs on Honduras time, same as the Umbrella CRM)
const APP_TIMEZONE = 'America/Tegucigalpa';
date_default_timezone_set(APP_TIMEZONE);

// Brand colors (must match manifest.webmanifest)
const THEME_COLOR      = '#0f1012';
const BACKGROUND_COLOR = '#0f1012';

// Never show PHP errors to visitors outside local development (they leak
// server paths); they still go to the server error log.
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1', '::1'], true);
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');

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
