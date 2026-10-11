<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

// Visual base of the login. Authentication (Supabase) comes later; until
// then the button goes straight into the app so it can be reviewed.
$email = '';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: home.php');
    exit;
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
      <div class="field">
        <label for="email">Email</label>
        <div class="input-group">
          <?= icon('mail', 14, 'input-icon') ?>
          <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>"
                 placeholder="you@company.com" autocomplete="username" inputmode="email" autocapitalize="off" required>
        </div>
      </div>

      <div class="field">
        <div class="field-header">
          <label for="password">Password</label>
          <a href="#" class="link-subtle">Forgot your password?</a>
        </div>
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
