<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users-data.php';

$user = require_admin();

$seen = presence_last_seen();
$users = array_values(array_filter(app_users(), fn ($u) => $u['access']));
foreach ($users as &$u) {
    $u['last_seen_at'] = $seen[$u['id']]['last_seen_at'] ?? null;
    $u['device'] = $seen[$u['id']]['device'] ?? null;
}
unset($u);
// Most recent first; people who never opened the app go last
usort($users, fn ($a, $b) => strcmp((string) $b['last_seen_at'], (string) $a['last_seen_at']));

$todayStart = strtotime('today');
$today = count(array_filter($users, fn ($u) => $u['last_seen_at'] !== null && strtotime($u['last_seen_at']) >= $todayStart));
$live = presence_live_enabled();

$pageTitle = 'Active users';
$activeNav = '';
$pageScripts = ['assets/js/active-users.js'];
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <span class="page-eyebrow"><?= icon('lock', 12) ?> Admin</span>
  <h1 class="page-title">Active users</h1>
  <p class="page-subtitle">
    <?php if ($live): ?>
      <span class="presence-count"><span class="presence-dot" aria-hidden="true"></span> <strong data-online-count>0</strong> online now</span> &middot;
    <?php endif; ?>
    <?= $today ?> of <?= count($users) ?> used the app today
  </p>
</div>

<?php if (!$live): ?>
  <p class="notice"><?= icon('info', 14) ?> Live status is off. Add SUPABASE_ANON_KEY to config.local.php to see who is online right now. Last seen still works.</p>
<?php endif; ?>

<input class="input search-input" type="search" placeholder="Search by name or username" aria-label="Search users" data-filter-list="#activeList">

<ul class="records" id="activeList">
  <?php foreach ($users as $u): ?>
    <li class="record" data-filter-text="<?= e(strtolower($u['name'] . ' ' . $u['username'])) ?>"
        data-presence-token="<?= e(presence_token($u['id'])) ?>" data-last-seen="<?= e((string) $u['last_seen_at']) ?>">
      <a class="record-link" href="user-detail.php?id=<?= e($u['id']) ?>">
        <span class="agent-avatar presence-avatar"><?= e(initials($u['name'])) ?></span>
        <span class="record-main">
          <strong class="record-name"><?= e($u['name']) ?></strong>
          <span class="record-meta">@<?= e($u['username']) ?><?= $u['device'] ? ' · ' . e($u['device']) : '' ?><?= $u['devices'] > 0 ? ' · alerts on' : '' ?></span>
        </span>
        <span class="record-side">
          <span class="pill pill--green presence-online" hidden>Online</span>
          <span class="record-meta presence-ago"><?= e(presence_ago($u['last_seen_at'])) ?></span>
        </span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<?php if ($users === []): ?>
  <section class="empty-state">
    <?= icon('users', 22) ?>
    <h2>Nobody has access yet</h2>
    <p>Give access from <a href="users.php?show=all">Users &amp; access</a>.</p>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
