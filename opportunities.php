<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Opportunities';
$activeNav = 'opportunities';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('users', 22) ?>
  <h2>Opportunities</h2>
  <p>Your opportunities will appear here.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
