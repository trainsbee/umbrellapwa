<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users-data.php';

$user = require_admin();

$filter = (string) ($_GET['show'] ?? 'access');
$users = app_users();
$withAccess = array_values(array_filter($users, fn ($u) => $u['access']));
$list = $filter === 'all' ? $users : $withAccess;

$pageTitle = 'Users & access';
$activeNav = '';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <span class="page-eyebrow"><?= icon('lock', 12) ?> Admin</span>
  <h1 class="page-title">Users &amp; access</h1>
  <p class="page-subtitle"><?= count($withAccess) ?> of <?= count($users) ?> active users can use the app. Admins always can.</p>
</div>

<nav class="tabs" aria-label="Show">
  <a class="tab<?= $filter !== 'all' ? ' active' : '' ?>" href="users.php">With access <span class="tab-count"><?= count($withAccess) ?></span></a>
  <a class="tab<?= $filter === 'all' ? ' active' : '' ?>" href="users.php?show=all">All users <span class="tab-count"><?= count($users) ?></span></a>
</nav>

<input class="input search-input" type="search" placeholder="Search by name or username" aria-label="Search users" data-filter-list="#userList">

<ul class="records" id="userList">
  <?php foreach ($list as $u): ?>
    <li class="record" data-filter-text="<?= e(strtolower($u['name'] . ' ' . $u['username'])) ?>">
      <a class="record-link" href="user-detail.php?id=<?= e($u['id']) ?>">
        <span class="agent-avatar"><?= e(initials($u['name'])) ?></span>
        <span class="record-main">
          <strong class="record-name"><?= e($u['name']) ?></strong>
          <span class="record-meta">@<?= e($u['username']) ?><?= $u['alerts'] > 0 ? ' · ' . $u['alerts'] . ' alerts' : '' ?><?= $u['devices'] > 0 ? ' · ' . $u['devices'] . ' devices' : '' ?></span>
        </span>
        <span class="record-side">
          <?php if ($u['admin']): ?>
            <span class="pill pill--accent">Admin</span>
          <?php elseif ($u['access']): ?>
            <span class="pill pill--green">Access</span>
          <?php else: ?>
            <span class="pill pill--neutral">No access</span>
          <?php endif; ?>
        </span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
<?php if ($list === []): ?>
  <section class="empty-state">
    <?= icon('users', 22) ?>
    <h2>Nobody has access yet</h2>
    <p>Open <a href="users.php?show=all">All users</a> and give access to the people who should use the app.</p>
  </section>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
