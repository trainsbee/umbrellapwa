<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/opportunities-data.php';
require_once __DIR__ . '/includes/date-range.php';

$user = require_login();

[$from, $to] = date_range_params();

$records = opportunities_records($from, $to);
$debts = array_values(array_filter(array_map(fn ($r) => is_numeric($r['debt']) ? (float) $r['debt'] : null, $records), fn ($d) => $d !== null));
$averageDebt = $debts !== [] ? array_sum($debts) / count($debts) : 0.0;

$pageTitle = 'Opportunities';
$activeNav = 'opportunities';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Opportunities</h1>
    <p class="page-subtitle"><?= e(date_label($from, $to)) ?> · <?= count($records) ?> <?= count($records) === 1 ? 'lead' : 'leads' ?><?php if ($averageDebt > 0): ?> · avg. debt <?= e(money($averageDebt)) ?><?php endif; ?></p>
  </div>
</div>

<?php $action = 'opportunities.php'; $hidden = []; require __DIR__ . '/includes/range-filter.php'; ?>

<?php if ($records === []): ?>
  <section class="empty-state">
    <?= icon('users', 22) ?>
    <h2>No opportunities</h2>
    <p>There is no snapshot for <?= e(date_label($from, $to)) ?>.</p>
  </section>
<?php else: ?>
  <ul class="records">
    <?php foreach ($records as $r): ?>
      <li class="record">
        <div class="record-main">
          <strong class="record-name"><?= e($r['name'] !== '' ? $r['name'] : 'No name') ?></strong>
          <?php if ($r['phone'] !== ''): ?><a class="record-phone" href="tel:<?= e(preg_replace('/\D+/', '', $r['phone'])) ?>"><?= e(phone($r['phone'])) ?></a><?php endif; ?>
        </div>
        <div class="record-side">
          <span class="record-amount"><?= e(money($r['debt'])) ?></span>
          <?php if ($r['status'] !== ''): ?><span class="pill pill--neutral"><?= e(status_label($r['status'])) ?></span><?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
