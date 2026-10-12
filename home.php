<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sales-data.php';
require_once __DIR__ . '/includes/left-behind-data.php';

$user = require_login();

// Day being viewed (defaults to today, never in the future)
$date = min(date_param('date', today()), today());
$previous = (new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');
$next = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
$isToday = $date === today();

// Both queries run at the same time (and are briefly cached on the server)
$data = supabase_select_many([
    'sales' => sales_summary_path($date),
    'leftBehind' => left_behind_snapshot_path($date),
], cache_ttl($date));
$sales = sales_summary_from_rows($data['sales']);
$leftBehind = left_behind_snapshot_from_rows($data['leftBehind']);
$leftBehindCount = $leftBehind['count'] ?? 0;
$averageDebt = $leftBehind['average_debt'] ?? 0.0;

// Share of the day's leads that closed vs. were left behind
$total = $sales['sales'] + $leftBehindCount;
$closedPercent = $total > 0 ? (int) round($sales['sales'] / $total * 100) : null;

$range = 'from=' . $date . '&to=' . $date;
$firstName = explode(' ', $user['name'])[0] ?: $user['username'];

$pageTitle = 'Home';
$activeNav = 'home';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Hi, <?= e($firstName) ?></h1>
    <p class="page-subtitle">
      <?= $isToday ? 'Today' : e((new DateTimeImmutable($date))->format('l')) ?>, <?= e(date_label($date, $date)) ?>
      <?php if ($leftBehind !== null): ?> · updated <?= e(local_time($leftBehind['updated_at'])) ?><?php endif; ?>
    </p>
  </div>
  <form class="date-nav" method="get" action="home.php">
    <a class="btn btn--icon" href="home.php?date=<?= e($previous) ?>" aria-label="Previous day"><?= icon('chevron-left', 14) ?></a>
    <input class="input" type="date" name="date" value="<?= e($date) ?>" max="<?= e(today()) ?>" data-autosubmit aria-label="Day">
    <?php if ($isToday): ?>
      <span class="btn btn--icon" aria-disabled="true"><?= icon('chevron-right', 14) ?></span>
    <?php else: ?>
      <a class="btn btn--icon" href="home.php?date=<?= e($next) ?>" aria-label="Next day"><?= icon('chevron-right', 14) ?></a>
    <?php endif; ?>
  </form>
</div>

<div class="metric-grid">
  <a class="metric metric--green" href="sales.php?<?= e($range) ?>">
    <span class="metric-label"><?= icon('trending-up', 14) ?> Sales</span>
    <span class="metric-value"><?= $sales['sales'] ?></span>
    <span class="metric-sub">Spanish <?= $sales['spanish'] ?> · English <?= $sales['english'] ?></span>
  </a>
  <a class="metric metric--red" href="left-behind.php?<?= e($range) ?>">
    <span class="metric-label"><?= icon('users', 14) ?> Left Behind</span>
    <span class="metric-value"><?= $leftBehind !== null ? $leftBehindCount : '—' ?></span>
    <span class="metric-sub"><?= $averageDebt > 0 ? 'Avg. debt ' . e(money($averageDebt)) : 'No snapshot for this day' ?></span>
  </a>
</div>

<?php if ($closedPercent !== null): ?>
  <div class="split">
    <div class="split-bar" role="img" aria-label="<?= $closedPercent ?>% closed, <?= 100 - $closedPercent ?>% left behind">
      <span class="split-closed" style="width: <?= $closedPercent ?>%"></span>
    </div>
    <p class="split-legend">
      <span><i class="dot dot--green"></i> <?= $closedPercent ?>% closed</span>
      <span><i class="dot dot--red"></i> <?= 100 - $closedPercent ?>% left behind</span>
      <span class="split-total"><?= $total ?> leads</span>
    </p>
  </div>
<?php endif; ?>

<div class="metric-grid metric-grid--3">
  <a class="metric metric--small" href="sales.php?<?= e($range) ?>&status=SIGNING">
    <span class="metric-label"><?= icon('edit-3', 14) ?> Signing</span>
    <span class="metric-value"><?= $sales['signing'] ?></span>
    <span class="metric-sub">Clients signing now</span>
  </a>
  <a class="metric metric--small" href="sales.php?<?= e($range) ?>&status=THREE_DAYS_WAIT">
    <span class="metric-label"><?= icon('map-pin', 14) ?> California</span>
    <span class="metric-value"><?= $sales['three_day_wait'] ?></span>
    <span class="metric-sub">3-day wait</span>
  </a>
  <a class="metric metric--small" href="sales.php?<?= e($range) ?>&status=SIGNED">
    <span class="metric-label"><?= icon('check-circle', 14) ?> Signed</span>
    <span class="metric-value"><?= $sales['signed'] ?></span>
    <span class="metric-sub">Other states</span>
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
