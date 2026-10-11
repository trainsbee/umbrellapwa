<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

// Base visual del login. La autenticacion (Supabase) se conecta despues.
$email = '';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $error = 'El inicio de sesión todavía no está conectado.';
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/includes/head.php';
?>
<main class="auth">
  <div class="auth-card">
    <div class="auth-brand">
      <span class="auth-logo"><?= icon('logo', 22) ?></span>
      <h1>Inicia sesión</h1>
      <p>Usa tu cuenta de Umbrella para continuar.</p>
    </div>

    <?php if ($error !== null): ?>
      <div class="aviso aviso--error" role="alert"><?= icon('alert-circle', 14) ?><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
      <div class="campo">
        <label for="email">Correo</label>
        <div class="input-grupo">
          <?= icon('mail', 14, 'input-icono') ?>
          <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>"
                 placeholder="tu@correo.com" autocomplete="username" inputmode="email" autocapitalize="off" required>
        </div>
      </div>

      <div class="campo">
        <div class="campo-cabecera">
          <label for="password">Contraseña</label>
          <a href="#" class="enlace-suave">¿Olvidaste tu contraseña?</a>
        </div>
        <div class="input-grupo">
          <?= icon('lock', 14, 'input-icono') ?>
          <input class="input" type="password" id="password" name="password"
                 placeholder="••••••••" autocomplete="current-password" required>
          <button type="button" class="input-accion" data-toggle-password="password" aria-label="Mostrar contraseña">
            <?= icon('eye', 14, 'si-oculta') ?><?= icon('eye-off', 14, 'si-visible') ?>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn--primario btn--bloque"><?= icon('log-in') ?> Iniciar sesión</button>
    </form>

    <button type="button" class="btn btn--fantasma btn--bloque auth-instalar" data-pwa-install hidden><?= icon('download') ?> Instalar app</button>
  </div>

  <p class="auth-pie"><?= e(APP_NAME) ?> &middot; v<?= e(APP_VERSION) ?></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
