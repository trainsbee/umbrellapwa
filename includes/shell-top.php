<?php
declare(strict_types=1);

/**
 * Internal page layout: top bar, sidebar and the opening <main>.
 * Closed by includes/shell-bottom.php.
 * Variables before including:
 *   $pageTitle (string) page title
 *   $activeNav (string) NAV_ITEMS key to highlight
 *   $user      (array)  signed-in user (pages get it from require_login())
 */

require_once __DIR__ . '/nav.php';
require_once __DIR__ . '/auth.php';

$activeNav = $activeNav ?? '';
$user = $user ?? current_user() ?? ['name' => 'Umbrella', 'roles' => []];
$username = (string) ($user['username'] ?? '');
$bodyClass = 'has-shell';
require __DIR__ . '/head.php';
?>
<header class="topbar">
  <button type="button" class="btn-circle topbar-menu" data-sidebar-open aria-controls="sidebar" aria-expanded="false" aria-label="Open menu">
    <?= icon('menu', 18) ?>
  </button>
  <h1 class="topbar-title"><?= e($pageTitle) ?></h1>
  <span class="btn-circle-spacer" aria-hidden="true"></span>
</header>

<div class="sidebar-backdrop" data-sidebar-close></div>

<aside class="sidebar" id="sidebar" aria-label="Menu">
  <div class="sidebar-header">
    <button type="button" class="btn-circle sidebar-close-btn" data-sidebar-close aria-label="Close menu">
      <?= icon('chevron-left', 18) ?>
    </button>
    <h2>Settings</h2>
    <span class="btn-circle-spacer" aria-hidden="true"></span>
  </div>

  <div class="sidebar-body">
    <a href="#" class="profile-card">
      <span class="avatar"><?= e(initials($user['name'])) ?></span>
      <span class="profile-text">
        <strong><?= e($user['name']) ?></strong>
        <?php if ($username !== ''): ?><small>@<?= e($username) ?></small><?php endif; ?>
      </span>
      <?= icon('chevron-right', 16, 'row-arrow') ?>
    </a>

    <p class="section-title">Navigation</p>
    <nav class="group">
      <?php foreach (NAV_ITEMS as $key => [$label, $iconName, $href]): ?>
        <a href="<?= e($href) ?>" class="row<?= $key === $activeNav ? ' active' : '' ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>>
          <?= icon($iconName, 18, 'row-icon') ?>
          <span class="row-label"><?= e($label) ?></span>
          <?= icon('chevron-right', 16, 'row-arrow') ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <p class="section-title">Other settings</p>
    <div class="group">
      <a href="#" class="row">
        <?= icon('user', 18, 'row-icon') ?><span class="row-label">Profile details</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </a>
      <a href="#" class="row">
        <?= icon('lock', 18, 'row-icon') ?><span class="row-label">Password</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </a>
      <a href="#" class="row">
        <?= icon('bell', 18, 'row-icon') ?><span class="row-label">Notifications</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </a>
      <button type="button" class="row" data-theme-toggle role="switch" aria-checked="true">
        <?= icon('moon', 18, 'row-icon theme-icon-dark') ?><?= icon('sun', 18, 'row-icon theme-icon-light') ?><span class="row-label">Dark mode</span><span class="switch" aria-hidden="true"></span>
      </button>
    </div>

    <div class="group">
      <button type="button" class="row" data-pwa-install hidden>
        <?= icon('download', 18, 'row-icon') ?><span class="row-label">Install app</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </button>
      <a href="#" class="row">
        <?= icon('info', 18, 'row-icon') ?><span class="row-label">About the app</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </a>
      <a href="#" class="row">
        <?= icon('message-square', 18, 'row-icon') ?><span class="row-label">Help / FAQ</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </a>
      <button type="submit" form="signOutForm" class="row row--danger">
        <?= icon('log-out', 18, 'row-icon') ?><span class="row-label">Sign out</span><?= icon('chevron-right', 16, 'row-arrow') ?>
      </button>
    </div>

    <!-- Sign out is a POST with a CSRF token; the button above submits it -->
    <form id="signOutForm" method="post" action="logout.php" hidden>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    </form>

    <p class="sidebar-version"><?= e(APP_NAME) ?> &middot; v<?= e(APP_VERSION) ?></p>
  </div>
</aside>

<main class="content">
