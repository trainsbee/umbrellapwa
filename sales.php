<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Sales';
$activeNav = 'sales';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('trending-up', 22) ?>
  <h2>Sales</h2>
  <p>Your sales will appear here.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
