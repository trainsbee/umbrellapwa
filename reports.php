<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/leaderboard-data.php';

$user = require_login();
$reportQuery = '';

$pageTitle = 'Reports';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <h1 class="page-title">Reports</h1>
  <p class="page-subtitle">Live, automated reports of the operation.</p>
</div>

<div class="report-links">
  <a class="spotlight" href="closing-report.php<?= $reportQuery ?>">
    <span class="spotlight-top">
      <span class="spotlight-icon"><?= icon('target', 18) ?></span>
      <span class="spotlight-badge"><?= icon('cpu', 11) ?> Automated</span>
    </span>
    <span class="spotlight-title">Closing Report</span>
    <span class="spotlight-text">Check our advanced automated report: close rate by agent for Honduras and the United States.</span>
    <span class="spotlight-cta">View report <?= icon('arrow-right', 14) ?></span>
  </a>
  <?php if (can_view_leaderboard($user)): ?>
    <a class="spotlight spotlight--alt" href="leaderboard.php<?= $reportQuery ?>">
      <span class="spotlight-top">
        <span class="spotlight-icon"><?= icon('award', 18) ?></span>
        <span class="spotlight-badge"><?= icon('activity', 11) ?> Live</span>
      </span>
      <span class="spotlight-title">Leader Board</span>
      <span class="spotlight-text">See who is leading: sales and opportunities by segment and agent, updated live.</span>
      <span class="spotlight-cta">View leader board <?= icon('arrow-right', 14) ?></span>
    </a>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
