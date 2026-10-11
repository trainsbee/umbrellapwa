<?php
declare(strict_types=1);

/**
 * Estructura de las paginas internas: barra superior, menu lateral y
 * apertura de <main>. Se cierra con includes/shell-bottom.php.
 * Variables antes del include:
 *   $pageTitle (string) titulo de la pagina
 *   $activeNav (string) clave de NAV_ITEMS que va marcada
 */

require_once __DIR__ . '/nav.php';

$activeNav = $activeNav ?? '';
$user = DEMO_USER;
$bodyClass = 'con-shell';
require __DIR__ . '/head.php';
?>
<header class="topbar">
  <button type="button" class="btn-circulo topbar-menu" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú">
    <?= icon('menu', 18) ?>
  </button>
  <h1 class="topbar-titulo"><?= e($pageTitle) ?></h1>
  <span class="btn-circulo-espacio" aria-hidden="true"></span>
</header>

<div class="sidebar-fondo" data-sidebar-close></div>

<aside class="sidebar" id="sidebar" aria-label="Menú">
  <div class="sidebar-cabecera">
    <button type="button" class="btn-circulo sidebar-cerrar" data-sidebar-close aria-label="Cerrar menú">
      <?= icon('chevron-left', 18) ?>
    </button>
    <h2>Ajustes</h2>
    <span class="btn-circulo-espacio" aria-hidden="true"></span>
  </div>

  <div class="sidebar-cuerpo">
    <a href="#" class="perfil-tarjeta">
      <span class="avatar"><?= e(initials($user['nombre'])) ?></span>
      <span class="perfil-texto">
        <strong><?= e($user['nombre']) ?></strong>
        <small><?= e($user['rol']) ?></small>
      </span>
      <?= icon('chevron-right', 16, 'fila-flecha') ?>
    </a>

    <p class="seccion-titulo">Navegación</p>
    <nav class="grupo">
      <?php foreach (NAV_ITEMS as $key => [$label, $iconName, $href]): ?>
        <a href="<?= e($href) ?>" class="fila<?= $key === $activeNav ? ' activa' : '' ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>>
          <?= icon($iconName, 18, 'fila-icono') ?>
          <span class="fila-texto"><?= e($label) ?></span>
          <?= icon('chevron-right', 16, 'fila-flecha') ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <p class="seccion-titulo">Otros ajustes</p>
    <div class="grupo">
      <a href="#" class="fila">
        <?= icon('user', 18, 'fila-icono') ?><span class="fila-texto">Datos del perfil</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
      <a href="#" class="fila">
        <?= icon('lock', 18, 'fila-icono') ?><span class="fila-texto">Contraseña</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
      <a href="#" class="fila">
        <?= icon('bell', 18, 'fila-icono') ?><span class="fila-texto">Notificaciones</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
      <button type="button" class="fila" data-theme-toggle role="switch" aria-checked="true">
        <?= icon('moon', 18, 'fila-icono') ?><span class="fila-texto">Modo oscuro</span><span class="switch" aria-hidden="true"></span>
      </button>
    </div>

    <div class="grupo">
      <button type="button" class="fila" data-pwa-install hidden>
        <?= icon('download', 18, 'fila-icono') ?><span class="fila-texto">Instalar app</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </button>
      <a href="#" class="fila">
        <?= icon('info', 18, 'fila-icono') ?><span class="fila-texto">Acerca de la aplicación</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
      <a href="#" class="fila">
        <?= icon('message-square', 18, 'fila-icono') ?><span class="fila-texto">Ayuda / FAQ</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
      <a href="login.php" class="fila fila--peligro">
        <?= icon('log-out', 18, 'fila-icono') ?><span class="fila-texto">Cerrar sesión</span><?= icon('chevron-right', 16, 'fila-flecha') ?>
      </a>
    </div>

    <p class="sidebar-version"><?= e(APP_NAME) ?> &middot; v<?= e(APP_VERSION) ?></p>
  </div>
</aside>

<main class="contenido">
