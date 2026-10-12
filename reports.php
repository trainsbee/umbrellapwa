<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/leaderboard-data.php';

$user = require_login();

$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <p class="page-subtitle">Live, automated reports of the operation.</p>
</div>

<div class="report-links">
  <a class="spotlight" href="closing-report.php">
    <span class="spotlight-icon"><?= icon('target', 18) ?></span>
    <span class="spotlight-body">
      <span class="spotlight-title">Closing Report <span class="spotlight-badge">Automated</span></span>
      <span class="spotlight-text">Close rate by agent, Honduras and United States</span>
    </span>
    <span class="spotlight-arrow"><?= icon('chevron-right', 18) ?></span>
  </a>
  <?php if (can_view_leaderboard($user)): ?>
    <a class="spotlight spotlight--alt" href="leaderboard.php">
      <span class="spotlight-icon"><?= icon('award', 18) ?></span>
      <span class="spotlight-body">
        <span class="spotlight-title">Leader Board <span class="spotlight-badge">Live</span></span>
        <span class="spotlight-text">Sales and opportunities by segment and agent</span>
      </span>
      <span class="spotlight-arrow"><?= icon('chevron-right', 18) ?></span>
    </a>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
