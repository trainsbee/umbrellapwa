<?php
declare(strict_types=1);

/** Cierre de las paginas internas (ver includes/shell-top.php). */
?>
</main>

<nav class="bottomnav" aria-label="Navegación principal">
  <?php foreach (NAV_ITEMS as $key => [$label, $iconName, $href]): ?>
    <a href="<?= e($href) ?>" class="bottomnav-item<?= $key === $activeNav ? ' activa' : '' ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>>
      <?= icon($iconName, 20) ?>
      <span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
  <button type="button" class="bottomnav-item" data-sidebar-open aria-controls="sidebar" aria-expanded="false">
    <?= icon('menu', 20) ?>
    <span>Menú</span>
  </button>
</nav>
<?php require __DIR__ . '/footer.php'; ?>
