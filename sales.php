<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Ventas';
$activeNav = 'sales';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="vacio">
  <?= icon('trending-up', 22) ?>
  <h2>Ventas</h2>
  <p>Aquí irán las ventas.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
