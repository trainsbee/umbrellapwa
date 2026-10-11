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
</head>
<body>
