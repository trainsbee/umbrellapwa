<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();

$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('bar-chart-2', 22) ?>
  <h2>Reports</h2>
  <p>Your reports will appear here.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
