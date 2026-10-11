<?php
declare(strict_types=1);

/**
 * Apertura del documento: <head> completo y <body>.
 * Variables opcionales antes del include:
 *   $pageTitle (string) titulo de la pagina
 */

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="<?= e(APP_LANG) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e(APP_DESCRIPTION) ?>">

<!-- PWA: manifest y color de la barra del sistema -->
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="<?= e(THEME_COLOR) ?>">
<meta name="color-scheme" content="dark light">
<meta name="application-name" content="<?= e(APP_NAME) ?>">
<meta name="mobile-web-app-capable" content="yes">

<!-- Favicons (navegadores de escritorio) -->
<link rel="icon" href="assets/icons/favicon.ico" sizes="32x32">
<link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">

<!-- iOS / iPadOS / macOS Safari -->
<link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_SHORT_NAME) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<?php require __DIR__ . '/splash-links.php'; ?>

<!-- Windows (tiles del menu inicio) -->
<meta name="msapplication-TileColor" content="<?= e(THEME_COLOR) ?>">
<meta name="msapplication-TileImage" content="assets/icons/icon-144.png">
<meta name="msapplication-tap-highlight" content="no">
</head>
<body>
