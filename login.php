<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// Already signed in: go straight to the app
if (current_user() !== null) {
    header('Location: home.php');
    exit;
}

$username = '';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $result = attempt_login($username, (string) ($_POST['password'] ?? ''));
        if ($result['ok']) {
            login_user($result['user']);
            header('Location: home.php');
            exit;
        }
        $error = $result['error'];
    }
}

$pageTitle = 'Sign in';
require __DIR__ . '/includes/head.php';
?>
<main class="auth">
  <!-- Back to the landing page (hidden inside the installed app, where the landing never shows) -->
  <a class="btn btn--ghost auth-back" href="./"><?= icon('arrow-left', 14) ?> Back to home</a>

  <div class="auth-card">
    <div class="auth-brand">
      <a class="auth-logo" href="./" aria-label="Umbrella home"><?= icon('logo', 22) ?></a>
      <h1>Sign in</h1>
      <p>Use your Umbrella account to continue.</p>
    </div>

    <?php if ($error !== null): ?>
      <div class="notice notice--error" role="alert"><?= icon('alert-circle', 14) ?><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="field">
        <label for="username">Username</label>
        <div class="input-group">
          <?= icon('user', 14, 'input-icon') ?>
          <input class="input" type="text" id="username" name="username" value="<?= e($username) ?>"
                 placeholder="Your Umbrella username" autocomplete="username" autocapitalize="off" spellcheck="false" required autofocus>
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="input-group">
          <?= icon('lock', 14, 'input-icon') ?>
          <input class="input" type="password" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="input-action" data-toggle-password="password" aria-label="Show password">
            <?= icon('eye', 14, 'when-hidden') ?><?= icon('eye-off', 14, 'when-visible') ?>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn--primary btn--block"><?= icon('log-in') ?> Sign in</button>
    </form>

    <button type="button" class="btn btn--ghost btn--block auth-install" data-pwa-install hidden><?= icon('download') ?> Install app</button>
  </div>

  <p class="auth-footer"><?= e(APP_NAME) ?> &middot; v<?= e(APP_VERSION) ?></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
