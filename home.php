<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sales-data.php';
require_once __DIR__ . '/includes/opportunities-data.php';

$user = require_login();

// Day being viewed (defaults to today, never in the future)
$date = min(date_param('date', today()), today());
$previous = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
$isToday = $date === today();

// Both queries run at the same time, always live
$data = supabase_select_many([
    'sales' => sales_summary_path($date),
    'opportunities' => opportunities_snapshot_path($date),
]);
$sales = sales_summary_from_rows($data['sales']);
$opportunities = opportunities_snapshot_from_rows($data['opportunities']);
$opportunitiesCount = $opportunities['count'] ?? 0;
$averageDebt = $opportunities['average_debt'] ?? 0.0;

// Share of the day's leads that closed vs. are still open opportunities
$total = $sales['sales'] + $opportunitiesCount;
$salesPercent = $total > 0 ? (int) round($sales['sales'] / $total * 100) : null;
$opportunitiesPercent = $salesPercent !== null ? 100 - $salesPercent : null;

$range = 'from=' . $date . '&to=' . $date;
$firstName = explode(' ', $user['name'])[0] ?: $user['username'];

$pageTitle = 'Home';
$activeNav = 'home';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <h1 class="page-title">Hi, <?= e($firstName) ?></h1>
  <p class="page-subtitle">
    <?= $isToday ? 'Today' : e((new DateTimeImmutable($date))->format('l')) ?>, <?= e(date_label($date, $date)) ?>
    <?php if ($opportunities !== null): ?> · updated <?= e(local_time($opportunities['updated_at'])) ?><?php endif; ?>
  </p>
</div>

<a class="spotlight" href="closing-report.php?<?= e($range) ?>">
  <span class="spotlight-top">
    <span class="spotlight-icon"><?= icon('target', 18) ?></span>
    <span class="spotlight-badge"><?= icon('cpu', 11) ?> Automated</span>
  </span>
  <span class="spotlight-title">Closing Report</span>
  <span class="spotlight-text">Check our advanced automated report: close rate by agent for Honduras and the United States.</span>
  <span class="spotlight-cta">View report <?= icon('arrow-right', 14) ?></span>
</a>

<form class="date-nav" method="get" action="home.php">
  <a class="btn btn--icon" href="home.php?date=<?= e($previous) ?>" aria-label="Previous day"><?= icon('chevron-left', 14) ?></a>
  <input class="input" type="date" name="date" value="<?= e($date) ?>" max="<?= e(today()) ?>" data-autosubmit aria-label="Day">
  <?php if ($isToday): ?>
    <span class="btn btn--icon" aria-disabled="true"><?= icon('chevron-right', 14) ?></span>
  <?php else: ?>
    <a class="btn btn--icon" href="home.php?date=<?= e($next) ?>" aria-label="Next day"><?= icon('chevron-right', 14) ?></a>
  <?php endif; ?>
</form>

<div class="metric-grid">
  <a class="metric metric--success" href="sales.php?<?= e($range) ?>">
    <span class="metric-top">
      <span class="metric-icon"><?= icon('trending-up', 16) ?></span>
      <?php if ($salesPercent !== null): ?><span class="metric-percent"><?= $salesPercent ?>%</span><?php endif; ?>
    </span>
    <span class="metric-label">Sales</span>
    <span class="metric-value"><?= $sales['sales'] ?></span>
    <span class="metric-sub">Spanish <?= $sales['spanish'] ?> · English <?= $sales['english'] ?></span>
  </a>
  <a class="metric metric--danger" href="opportunities.php?<?= e($range) ?>">
    <span class="metric-top">
      <span class="metric-icon"><?= icon('users', 16) ?></span>
      <?php if ($opportunitiesPercent !== null): ?><span class="metric-percent"><?= $opportunitiesPercent ?>%</span><?php endif; ?>
    </span>
    <span class="metric-label">Opportunities</span>
    <span class="metric-value"><?= $opportunities !== null ? $opportunitiesCount : '—' ?></span>
    <span class="metric-sub"><?= $averageDebt > 0 ? 'Avg. debt ' . e(money($averageDebt)) : 'No snapshot for this day' ?></span>
  </a>
</div>

<?php if ($salesPercent !== null): ?>
  <p class="metric-note">Out of <?= $total ?> leads: <?= $salesPercent ?>% sales, <?= $opportunitiesPercent ?>% open opportunities.</p>
<?php endif; ?>

<div class="metric-grid metric-grid--3">
  <a class="metric metric--warning metric--small" href="sales.php?<?= e($range) ?>&status=SIGNING">
    <span class="metric-icon"><?= icon('edit-3', 16) ?></span>
    <span class="metric-label">Signing</span>
    <span class="metric-value"><?= $sales['signing'] ?></span>
  </a>
  <a class="metric metric--small" href="sales.php?<?= e($range) ?>&status=THREE_DAYS_WAIT">
    <span class="metric-icon"><?= icon('map-pin', 16) ?></span>
    <span class="metric-label">California</span>
    <span class="metric-value"><?= $sales['three_day_wait'] ?></span>
  </a>
  <a class="metric metric--small" href="sales.php?<?= e($range) ?>&status=SIGNED">
    <span class="metric-icon"><?= icon('check-circle', 16) ?></span>
    <span class="metric-label">Signed</span>
    <span class="metric-value"><?= $sales['signed'] ?></span>
  </a>
</div>

<div class="panel-grid">
  <?php foreach (['Top programs' => $sales['top_programs'], 'Top campaigns' => $sales['top_campaigns']] as $title => $ranking): ?>
    <section class="panel">
      <h2 class="panel-title"><?= e($title) ?></h2>
      <?php if ($ranking === []): ?>
        <p class="panel-empty">No sales on this day.</p>
      <?php else: $max = max($ranking); ?>
        <ul class="ranking">
          <?php foreach ($ranking as $name => $count): ?>
            <li>
              <span class="ranking-row"><span class="ranking-name"><?= e((string) $name) ?></span><span class="ranking-count"><?= $count ?></span></span>
              <span class="ranking-bar"><span style="width: <?= (int) round($count / $max * 100) ?>%"></span></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
