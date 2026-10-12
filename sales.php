<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sales-data.php';
require_once __DIR__ . '/includes/date-range.php';

$user = require_login();

[$from, $to] = date_range_params();

// Status tabs: "Sales" = SIGNED + THREE_DAYS_WAIT
$tabs = ['' => 'Sales', 'SIGNED' => 'Signed', 'THREE_DAYS_WAIT' => '3-day wait', 'SIGNING' => 'Signing'];
$status = (string) ($_GET['status'] ?? '');
if (!array_key_exists($status, $tabs)) {
    $status = '';
}

$records = sales_records($from, $to, $status !== '' ? $status : null);
$totalDebt = array_sum(array_map(fn ($r) => is_numeric($r['debt']) ? (float) $r['debt'] : 0, $records));

$pageTitle = 'Sales';
$activeNav = 'sales';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <div>
    <h1 class="page-title">Sales</h1>
    <p class="page-subtitle"><?= e(date_label($from, $to)) ?> · <?= count($records) ?> <?= count($records) === 1 ? 'record' : 'records' ?><?php if ($totalDebt > 0): ?> · <?= e(money($totalDebt)) ?> total debt<?php endif; ?></p>
  </div>
</div>

<?php $action = 'sales.php'; $hidden = $status !== '' ? ['status' => $status] : []; require __DIR__ . '/includes/range-filter.php'; ?>

<nav class="tabs" aria-label="Status">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="tab<?= $key === $status ? ' active' : '' ?>" href="sales.php?from=<?= e($from) ?>&to=<?= e($to) ?><?= $key !== '' ? '&status=' . e($key) : '' ?>"<?= $key === $status ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<?php if ($records === []): ?>
  <section class="empty-state">
    <?= icon('trending-up', 22) ?>
    <h2>No records</h2>
    <p>There are no <?= e(strtolower($tabs[$status])) ?> records for <?= e(date_label($from, $to)) ?>.</p>
  </section>
<?php else: ?>
  <ul class="records">
    <?php foreach ($records as $r): ?>
      <li class="record">
        <div class="record-main">
          <a class="record-name record-name--link" href="client.php?id=<?= e($r['client_id']) ?>"><?= e($r['name'] !== '' ? $r['name'] : 'No name') ?></a>
          <?php if ($r['phone'] !== ''): ?><a class="record-phone" href="tel:<?= e(preg_replace('/\D+/', '', $r['phone'])) ?>"><?= e(phone($r['phone'])) ?></a><?php endif; ?>
          <span class="record-meta"><?= e(implode(' · ', array_filter([local_time($r['created_at']), $r['program'], $r['campaign'], ucfirst(strtolower($r['language']))]))) ?></span>
        </div>
        <div class="record-side">
          <span class="record-amount"><?= e(money($r['debt'])) ?></span>
          <span class="pill pill--<?= e(status_tone($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
