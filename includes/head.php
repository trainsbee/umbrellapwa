<?php
declare(strict_types=1);

/**
 * Document opening: full <head> and <body>.
 * Optional variables before including:
 *   $pageTitle (string) page title
 *   $pageCss   (array)  extra stylesheets for the page
 *   $bodyClass (string) <body> classes
 *   $bodyAttrs (array)  extra <body> attributes, e.g. ['data-install-auto' => 'off']
 */

$docTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME;
?>
<!DOCTYPE html>
<html lang="<?= e(APP_LANG) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($docTitle) ?></title>
<meta name="description" content="<?= e(APP_DESCRIPTION) ?>">

<!-- PWA: manifest and system bar color -->
<link rel="manifest" href="manifest.webmanifest">
<meta name="theme-color" content="<?= e(THEME_COLOR) ?>">
<meta name="color-scheme" content="dark light">
<script>
  // Apply the saved theme before first paint (avoids a flash).
  try {
    if (localStorage.getItem('umbrella-theme') === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
      document.querySelector('meta[name="theme-color"]').setAttribute('content', '#f6f6f7');
    }
  } catch (e) {}
</script>
<meta name="application-name" content="<?= e(APP_NAME) ?>">
<meta name="mobile-web-app-capable" content="yes">

<!-- Favicons (desktop browsers) -->
<link rel="icon" href="assets/icons/favicon.ico" sizes="32x32">
<link rel="icon" href="assets/icons/favicon.svg" type="image/svg+xml">

<!-- iOS / iPadOS / macOS Safari -->
<link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(APP_SHORT_NAME) ?>">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<?php require __DIR__ . '/splash-links.php'; ?>

<!-- Windows (Start menu tiles) -->
<meta name="msapplication-TileColor" content="<?= e(THEME_COLOR) ?>">
<meta name="msapplication-TileImage" content="assets/icons/icon-144.png">
<meta name="msapplication-tap-highlight" content="no">

<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<?php foreach ($pageCss ?? [] as $css): ?>
<link rel="stylesheet" href="<?= e(asset($css)) ?>">
<?php endforeach; ?>
</head>
<body<?= !empty($bodyClass) ? ' class="' . e($bodyClass) . '"' : '' ?><?php foreach ($bodyAttrs ?? [] as $attr => $value): ?> <?= e($attr) ?>="<?= e($value) ?>"<?php endforeach; ?>>
