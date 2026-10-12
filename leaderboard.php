<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/leaderboard-data.php';
require_once __DIR__ . '/includes/date-range.php';

$user = require_login();
if (!can_view_leaderboard($user)) {
    http_response_code(403);
}

[$from, $to] = date_range_params();
$lb = can_view_leaderboard($user) ? leaderboard($from, $to) : null;

/** Share of $part in $whole, one decimal (50 when there is nothing, like Umbrella's meters). */
function share(int $part, int $whole): float
{
    return $whole > 0 ? round(100 * $part / $whole, 1) : 50.0;
}

$pageTitle = 'Leader Board';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <span class="page-eyebrow"><?= icon('award', 12) ?> Live leader board</span>
  <h1 class="page-title">Leader Board</h1>
  <p class="page-subtitle">Sales and opportunities by segment · <?= e(date_label($from, $to)) ?></p>
</div>

<?php if ($lb === null): ?>
  <section class="empty-state">
    <?= icon('lock', 22) ?>
    <h2>No access</h2>
    <p>The Leader Board is available to Admin and Leaderboard roles.</p>
  </section>
<?php else: $t = $lb['totals']; ?>

<?php $action = 'leaderboard.php'; $hidden = []; require __DIR__ . '/includes/range-filter.php'; ?>

<!-- Pipeline -->
<div class="metric-grid">
  <div class="metric">
    <span class="metric-top"><span class="metric-icon"><?= icon('layers', 16) ?></span></span>
    <span class="metric-label">Total pipeline</span>
    <span class="metric-value"><?= $t['pipeline'] ?></span>
    <span class="metric-sub">Sales + opportunities</span>
  </div>
  <div class="metric">
    <span class="metric-top"><span class="metric-icon"><?= icon('dollar-sign', 16) ?></span></span>
    <span class="metric-label">Average debt</span>
    <span class="metric-value metric-value--sm"><?= e(money_exact($t['average_debt'])) ?></span>
    <span class="metric-sub">Total <?= e(money_exact(round($t['debt'], 2))) ?></span>
  </div>
  <div class="metric metric--success">
    <span class="metric-top">
      <span class="metric-icon"><?= icon('trending-up', 16) ?></span>
      <span class="metric-percent"><?= share($t['sales'], $t['pipeline']) ?>%</span>
    </span>
    <span class="metric-label">Sales</span>
    <span class="metric-value"><?= $t['sales'] ?></span>
    <span class="metric-sub"><?= $t['incomplete_compliance'] > 0 ? $t['incomplete_compliance'] . ' did not complete compliance' : 'All passed compliance' ?></span>
  </div>
  <div class="metric metric--danger">
    <span class="metric-top">
      <span class="metric-icon"><?= icon('users', 16) ?></span>
      <span class="metric-percent"><?= share($t['opportunities'], $t['pipeline']) ?>%</span>
    </span>
    <span class="metric-label">Opportunities</span>
    <span class="metric-value"><?= $t['opportunities'] ?></span>
    <span class="metric-sub"><?= $t['opportunities_no_sales'] > 0 ? 'With sales ' . $t['opportunities_with_sales'] . ' · no sales ' . $t['opportunities_no_sales'] : 'Left by agents who sold' ?></span>
  </div>
</div>

<!-- Boards by segment -->
<?php if ($lb['boards'] === []): ?>
  <section class="empty-state">
    <?= icon('award', 22) ?>
    <h2>No sales or opportunities</h2>
    <p>Nothing to rank for <?= e(date_label($from, $to)) ?>.</p>
  </section>
<?php endif; ?>
<?php foreach ($lb['boards'] as $board): ?>
  <section class="board">
    <header class="board-header">
      <h2 class="board-title"><?= e($board['title']) ?></h2>
      <span class="board-meta"><?= count($board['rows']) ?> agents · <?= $board['deals'] ?> deals<?= $board['opportunities'] > 0 ? ' · ' . $board['opportunities'] . ' opp.' : '' ?></span>
    </header>
    <div class="table-wrap">
      <table class="report-table board-table">
        <thead><tr><th>Agent</th><th>%</th><th>Deals</th><th>Opp.</th><th>Debt</th></tr></thead>
        <tbody>
          <?php foreach ($board['rows'] as $rank => $row): ?>
            <tr class="<?= $rank < 3 ? 'is-top' : '' ?>">
              <td>
                <span class="agent">
                  <span class="agent-rank"><?= $rank + 1 ?></span>
                  <span class="agent-avatar"><?= e(initials_of($row['agent'])) ?></span>
                  <span class="agent-text">
                    <span class="agent-name"><?= e($row['agent']) ?></span>
                    <?php if ($row['to'] !== []): ?>
                      <span class="agent-tags">
                        <?php foreach (array_slice($row['to'], 0, 3, true) as $toName => $count): ?>
                          <span class="tag">TO · <?= e(short_name((string) $toName)) ?><?= $count > 1 ? ' (' . $count . ')' : '' ?></span>
                        <?php endforeach; ?>
                        <?php if (count($row['to']) > 3): ?><span class="tag">+<?= count($row['to']) - 3 ?> TO</span><?php endif; ?>
                      </span>
                    <?php endif; ?>
                  </span>
                </span>
              </td>
              <td><span class="pill pill--<?= $row['percent'] >= 100 ? 'green' : 'amber' ?>"><?= rtrim(rtrim(number_format($row['percent'], 2), '0'), '.') ?>%</span></td>
              <td><?= $row['deals'] ?></td>
              <td><?= $row['opportunities'] ?></td>
              <td><?= e(money_exact($row['debt'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td>Grand total</td>
            <td><span class="pill pill--accent"><?= rtrim(rtrim(number_format($board['percent'], 2), '0'), '.') ?>%</span></td>
            <td><?= $board['deals'] ?></td>
            <td><?= $board['opportunities'] ?></td>
            <td><?= e(money_exact($board['debt'])) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>
<?php endforeach; ?>

<!-- Top programs and campaigns -->
<div class="panel-grid">
  <?php foreach (['Top programs' => $lb['top_programs'], 'Top campaigns' => $lb['top_campaigns']] as $title => $ranking): ?>
    <section class="panel">
      <h2 class="panel-title"><?= e($title) ?></h2>
      <?php if ($ranking === []): ?>
        <p class="panel-empty">No sales in this range.</p>
      <?php else: $max = max(array_column($ranking, 'sales')); ?>
        <ul class="ranking">
          <?php foreach ($ranking as $name => $item): ?>
            <li>
              <span class="ranking-row">
                <span class="ranking-name"><?= e((string) $name) ?></span>
                <span class="ranking-count"><?= $item['sales'] ?><?php if ($item['incomplete'] > 0): ?> <span class="pill pill--red"><?= $item['incomplete'] ?> no compliance</span><?php endif; ?></span>
              </span>
              <span class="ranking-bar"><span style="width: <?= (int) round($item['sales'] / $max * 100) ?>%"></span></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  <?php endforeach; ?>
</div>

<!-- Period totals -->
<section class="panel totals-panel">
  <h2 class="panel-title">Period totals</h2>
  <dl class="totals">
    <?php foreach ([
        'Total sales' => $t['sales'], 'Total debt' => money_exact(round($t['debt'], 2)),
        'Sales without Other Leads' => $t['primary_sales'], 'Debt without Other Leads' => money_exact(round($t['primary_debt'], 2)),
        'Other Leads sales' => $t['other_leads_sales'], 'Other Leads debt' => money_exact(round($t['other_leads_debt'], 2)),
        'On the boards' => $t['classified'], 'Unclassified' => $t['other_sales'],
    ] as $label => $value): ?>
      <div><dt><?= e($label) ?></dt><dd><?= e((string) $value) ?></dd></div>
    <?php endforeach; ?>
  </dl>
</section>

<!-- Signed sales -->
<section class="board">
  <header class="board-header">
    <h2 class="board-title">Signed sales</h2>
    <span class="board-meta"><?= count($lb['signed']) ?> records · newest first</span>
  </header>
  <?php if ($lb['signed'] === []): ?>
    <p class="panel-empty">No signed sales in this range.</p>
  <?php else: ?>
    <ul class="records">
      <?php foreach ($lb['signed'] as $s): ?>
        <li class="record">
          <div class="record-main">
            <strong class="record-name"><?= e($s['client'] !== '' ? $s['client'] : 'No name') ?></strong>
            <?php if ($s['phone'] !== ''): ?><a class="record-phone" href="tel:<?= e(preg_replace('/\D+/', '', $s['phone'])) ?>"><?= e(phone($s['phone'])) ?></a><?php endif; ?>
            <span class="record-meta"><?= e(implode(' · ', array_filter([local_time($s['created_at']), $s['agent'], $s['to'] !== '' ? 'TO ' . $s['to'] : '', $s['program'], $s['campaign'], $s['segment']]))) ?></span>
          </div>
          <div class="record-side">
            <span class="record-amount"><?= e(money_exact($s['debt'])) ?></span>
            <span class="pill pill--<?= e(status_tone($s['status'])) ?>"><?= e(status_label($s['status'])) ?></span>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
