<?php
declare(strict_types=1);

/**
 * Vista sin conexion. El service worker la guarda y la muestra en lugar
 * de cualquier pagina cuando no hay internet: se ve la estructura de la
 * app (menu, modo oscuro, navegacion) pero ningun dato.
 * Como se sirve en la URL que pidio el usuario, el script de abajo pone el
 * titulo y marca la seccion correspondiente.
 */

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Sin conexión';
$activeNav = '';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="vacio vacio--offline">
  <?= icon('wifi-off', 22) ?>
  <h2>Sin conexión</h2>
  <p>Conéctate a internet para ver los datos.</p>
  <button type="button" class="btn btn--primario" data-reintentar><?= icon('refresh-cw', 14) ?> Reintentar</button>
</section>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var page = location.pathname.split('/').pop() || 'home.php';
    document.querySelectorAll('.bottomnav-item[href], .sidebar .fila[href]').forEach(function (link) {
      if (link.getAttribute('href') !== page) return;
      link.classList.add('activa');
      link.setAttribute('aria-current', 'page');
      var label = link.querySelector('span');
      var title = document.querySelector('.topbar-titulo');
      if (label && title && link.classList.contains('bottomnav-item')) title.textContent = label.textContent;
    });
    document.querySelector('[data-reintentar]').addEventListener('click', function () { location.reload(); });
    window.addEventListener('online', function () { location.reload(); });
  });
</script>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
