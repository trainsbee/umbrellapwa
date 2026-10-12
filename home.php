<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();

$pageTitle = 'Home';
$activeNav = 'home';
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state">
  <?= icon('home', 22) ?>
  <h2>Home</h2>
  <p>Your operation overview will appear here.</p>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
