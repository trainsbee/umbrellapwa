<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Oportunidades';
$activeNav = 'oportunidades';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="vacio">
  <?= icon('users', 22) ?>
  <h2>Oportunidades</h2>
  <p>Aquí irán las oportunidades.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
