<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/users-data.php';

$admin = require_admin();

$id = (string) ($_GET['id'] ?? '');
$target = preg_match('/^[a-f0-9-]{36}$/', $id) ? app_user($id) : null;
if ($target === null) {
    http_response_code(404);
}

// Save access and alerts
if ($target !== null && $_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid($_POST['csrf'] ?? null)) {
    $wantsAccess = $target['admin'] || ($_POST['access'] ?? '') === '1';
    if ($wantsAccess) {
        $target['admin'] || grant_app_access($target['id'], $admin['id']);
        $enabled = [];
        foreach (PUSH_EVENTS as $event => $_) {
            $field = str_replace('.', '_', $event);
            if (($_POST['alert'][$field] ?? '') === '1') {
                $scope = (string) ($_POST['scope'][$field] ?? 'all');
                $enabled[$event] = isset(PUSH_SCOPES[$scope]) ? $scope : 'all';
            }
        }
        push_save_rules($target['id'], $enabled);
    } else {
        revoke_app_access($target['id']);
    }
    header('Location: user-detail.php?id=' . rawurlencode($target['id']) . '&saved=1');
    exit;
}

$rules = $target !== null ? push_rules_for($target['id']) : [];

$pageScripts = ['assets/js/user-detail.js'];
$pageTitle = $target['name'] ?? 'User';
$activeNav = '';
require __DIR__ . '/includes/shell-top.php';
?>
<a class="btn btn--ghost back-link" href="users.php"><?= icon('arrow-left', 14) ?> Users &amp; access</a>

<?php if ($target === null): ?>
  <section class="empty-state">
    <?= icon('user-x', 22) ?>
    <h2>User not found</h2>
    <p>This user does not exist or is not active in Umbrella.</p>
  </section>
<?php else: ?>
  <div class="page-header user-header">
    <span class="agent-avatar agent-avatar--lg"><?= e(initials($target['name'])) ?></span>
    <div>
      <h1 class="page-title"><?= e($target['name']) ?></h1>
      <p class="page-subtitle">@<?= e($target['username']) ?> · <?= $target['devices'] ?> <?= $target['devices'] === 1 ? 'device' : 'devices' ?> with notifications on</p>
    </div>
  </div>

  <?php if (isset($_GET['saved'])): ?>
    <div class="notice notice--success" role="status"><?= icon('check', 14) ?><span>Changes saved.</span></div>
  <?php endif; ?>

  <form method="post" action="user-detail.php?id=<?= e($target['id']) ?>" class="user-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <section class="panel">
      <h2 class="panel-title">App access</h2>
      <?php if ($target['admin']): ?>
        <p class="panel-empty">Admins always have access to the app.</p>
      <?php else: ?>
        <label class="setting">
          <span class="setting-text"><strong>Can use the app</strong><small>Without access the user cannot sign in. Removing access also removes their alerts and devices.</small></span>
          <input type="checkbox" class="switch-input" name="access" value="1" data-access-toggle<?= $target['access'] ? ' checked' : '' ?>>
        </label>
      <?php endif; ?>
    </section>

    <section class="panel" data-alerts<?= $target['access'] ? '' : ' hidden' ?>>
      <h2 class="panel-title">Push alerts</h2>
      <p class="panel-hint">Choose which moments of the client pipeline this user is told about, and for whom.</p>
      <?php foreach (PUSH_STAGES as $stage => $stageLabel): ?>
        <p class="alert-stage"><?= e($stageLabel) ?></p>
        <ul class="alert-list">
          <?php foreach (push_events_in($stage) as $event => [$label, $description, $iconName]): $field = str_replace('.', '_', $event); $rule = $rules[$event] ?? null; ?>
            <li class="alert-item alert-item--setting">
              <label class="setting">
                <span class="alert-icon alert-icon--<?= e($event === 'compliance.cancelled' ? 'danger' : $stage) ?>"><?= icon($iconName, 16) ?></span>
                <span class="setting-text"><strong><?= e($label) ?></strong><small><?= e($description) ?><?= ($rule['muted'] ?? false) ? ' · muted by the user' : '' ?></small></span>
                <input type="checkbox" class="switch-input" name="alert[<?= e($field) ?>]" value="1"<?= $rule !== null ? ' checked' : '' ?>>
              </label>
              <select class="input scope-select" name="scope[<?= e($field) ?>]" aria-label="Who: <?= e($label) ?>">
                <?php foreach (PUSH_SCOPES as $scope => $scopeLabel): ?>
                  <option value="<?= e($scope) ?>"<?= ($rule['scope'] ?? 'all') === $scope ? ' selected' : '' ?>><?= e($scopeLabel) ?></option>
                <?php endforeach; ?>
              </select>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </section>

    <button type="submit" class="btn btn--primary btn--block"><?= icon('save', 14) ?> Save changes</button>
  </form>

  <button type="button" class="btn btn--ghost btn--block test-button" data-test-user="<?= e($target['id']) ?>" data-csrf="<?= e(csrf_token()) ?>"<?= $target['devices'] > 0 ? '' : ' disabled' ?>>
    <?= icon('send', 14) ?> Send a test notification
  </button>
  <p class="bot-status" data-test-status<?= $target['devices'] > 0 ? ' hidden' : '' ?>><?= $target['devices'] > 0 ? '' : 'The user has not turned on notifications on any device yet.' ?></p>
<?php endif; ?>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
