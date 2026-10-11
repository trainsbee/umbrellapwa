<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Inicio';
$activeNav = 'home';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('home', 22) ?>
  <h2>Inicio</h2>
  <p>Aquí irá el resumen de la app.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
