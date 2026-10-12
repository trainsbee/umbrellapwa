<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/push.php';

$user = require_login();

// The user's own mute switch for an alert an admin enabled
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $event = (string) ($_POST['event'] ?? '');
    if (isset(PUSH_EVENTS[$event]) && isset(push_rules_for($user['id'])[$event])) {
        $saved = push_set_muted($user['id'], $event, ($_POST['muted'] ?? '') === '1');
    }
    header('Location: notifications.php' . ($saved ? '?saved=1' : ''));
    exit;
}

$rules = push_rules_for($user['id']);
$pageScripts = ['assets/js/push.js'];
$pageTitle = 'Notifications';
$activeNav = '';
require __DIR__ . '/includes/shell-top.php';
?>
<div class="page-header">
  <h1 class="page-title">Notifications</h1>
  <p class="page-subtitle">Get a push alert the moment something happens.</p>
</div>

<!-- This device -->
<section class="panel push-device" data-push
         data-vapid="<?= e(push_configured() ? VAPID_PUBLIC_KEY : '') ?>" data-csrf="<?= e(csrf_token()) ?>">
  <div class="push-device-head">
    <span class="metric-icon"><?= icon('smartphone', 16) ?></span>
    <div>
      <h2 class="panel-title">This device</h2>
      <p class="push-status" data-push-status>Checking…</p>
    </div>
  </div>
  <div class="push-actions">
    <button type="button" class="btn btn--primary btn--block" data-push-enable hidden><?= icon('bell', 14) ?> Turn on notifications</button>
    <button type="button" class="btn btn--block" data-push-disable hidden><?= icon('bell-off', 14) ?> Turn off on this device</button>
    <button type="button" class="btn btn--ghost btn--block" data-push-test hidden><?= icon('send', 14) ?> Send me a test</button>
  </div>
</section>

<!-- Alerts an admin enabled for this user -->
<section class="panel">
  <h2 class="panel-title">Your alerts</h2>
  <?php if ($rules === []): ?>
    <p class="panel-empty">An administrator has not assigned you any alerts yet.</p>
  <?php else: ?>
    <ul class="alert-list">
      <?php foreach (PUSH_EVENTS as $event => [$label, $description, $iconName]): if (!isset($rules[$event])) { continue; } $muted = $rules[$event]['muted']; ?>
        <li class="alert-item">
          <span class="alert-icon"><?= icon($iconName, 16) ?></span>
          <span class="alert-text">
            <strong><?= e($label) ?></strong>
            <small><?= e(PUSH_SCOPES[$rules[$event]['scope']] ?? '') ?> · <?= e($description) ?></small>
          </span>
          <form method="post" action="notifications.php">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="event" value="<?= e($event) ?>">
            <input type="hidden" name="muted" value="<?= $muted ? '0' : '1' ?>">
            <button type="submit" class="toggle" role="switch" aria-checked="<?= $muted ? 'false' : 'true' ?>" aria-label="<?= e($label) ?>"><span class="switch" aria-hidden="true"></span></button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
