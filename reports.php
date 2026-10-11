<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Reportes';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('bar-chart-2', 22) ?>
  <h2>Reportes</h2>
  <p>Aquí irán los reportes.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
