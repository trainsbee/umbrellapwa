<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Inicio';
require __DIR__ . '/includes/head.php';
?>
<main class="app">
  <h1><?= e(APP_NAME) ?></h1>
  <p>Base PWA lista. Version <?= e(APP_VERSION) ?>.</p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
