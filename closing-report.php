<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/closing-report-data.php';
require_once __DIR__ . '/includes/date-range.php';

$user = require_login();

[$from, $to] = date_range_params();
$report = closing_report($from, $to);

$pageTitle = 'Closing Report';
$activeNav = 'reports';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <span class="page-eyebrow"><?= icon('cpu', 12) ?> Automated report</span>
  <h1 class="page-title">Closing Report</h1>
  <p class="page-subtitle">Close rate by agent · <?= e(date_label($from, $to)) ?></p>
</div>

<?php $action = 'closing-report.php'; $hidden = []; require __DIR__ . '/includes/range-filter.php'; ?>

<?php if (!$report['ok']): ?>
  <div class="notice notice--error" role="alert"><?= icon('alert-circle', 14) ?><span><?= e($report['error']) ?></span></div>
<?php else: ?>
  <nav class="tabs" role="tablist" data-tabs>
    <?php $first = true; foreach (CLOSING_REPORT_COUNTRIES as $code => $country): ?>
      <button type="button" class="tab<?= $first ? ' active' : '' ?>" role="tab" aria-selected="<?= $first ? 'true' : 'false' ?>" data-tab="<?= e($code) ?>">
        <?= e($country) ?> <span class="tab-count"><?= count($report['countries'][$code]) ?></span>
      </button>
    <?php $first = false; endforeach; ?>
  </nav>

  <?php $first = true; foreach (CLOSING_REPORT_COUNTRIES as $code => $country): $rows = $report['countries'][$code]; ?>
    <section data-tab-panel="<?= e($code) ?>"<?= $first ? '' : ' hidden' ?>>
      <?php if ($rows === []): ?>
        <section class="empty-state">
          <?= icon('phone-off', 22) ?>
          <h2>No calls recorded</h2>
          <p>No agent in <?= e($country) ?> has call entries for <?= e(date_label($from, $to)) ?>.</p>
        </section>
      <?php else:
          $t = ['closed' => 0, 'left' => 0, 'opportunities' => 0, 'inbound' => 0, 'outbound' => 0, 'calls' => 0, 'duration' => 0];
          foreach ($rows as $r) { foreach ($t as $k => $_) { $t[$k] += $r[$k]; } }
      ?>
        <div class="table-wrap">
          <table class="report-table">
            <thead>
              <tr>
                <th>Agent</th><th>% Close</th><th>% Conv.</th><th>Closed</th><th>Left</th>
                <th>Opp.</th><th>Inb.</th><th>Out.</th><th>Calls</th><th>Talk time</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): $close = percent($r['closed'], $r['opportunities']); $conv = percent($r['closed'], $r['inbound']); ?>
                <tr>
                  <td><?= e($r['agent']) ?></td>
                  <td><span class="pill pill--<?= rate_tone($close) ?>"><?= $close ?>%</span></td>
                  <td><span class="pill pill--<?= rate_tone($conv) ?>"><?= $conv ?>%</span></td>
                  <td><?= $r['closed'] ?></td><td><?= $r['left'] ?></td><td><?= $r['opportunities'] ?></td>
                  <td><?= $r['inbound'] ?></td><td><?= $r['outbound'] ?></td><td><?= $r['calls'] ?></td>
                  <td class="muted"><?= duration_label($r['duration']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <?php $close = percent($t['closed'], $t['opportunities']); $conv = percent($t['closed'], $t['inbound']); ?>
              <tr>
                <td>Total</td>
                <td><span class="pill pill--<?= rate_tone($close) ?>"><?= $close ?>%</span></td>
                <td><span class="pill pill--<?= rate_tone($conv) ?>"><?= $conv ?>%</span></td>
                <td><?= $t['closed'] ?></td><td><?= $t['left'] ?></td><td><?= $t['opportunities'] ?></td>
                <td><?= $t['inbound'] ?></td><td><?= $t['outbound'] ?></td><td><?= $t['calls'] ?></td>
                <td class="muted"><?= duration_label($t['duration']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php $first = false; endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
