<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();

$pageTitle = 'Left Behind';
$activeNav = 'left-behind';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('users', 22) ?>
  <h2>Left Behind</h2>
  <p>Leads left behind will appear here.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
