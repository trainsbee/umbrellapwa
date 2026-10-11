<?php
declare(strict_types=1);

/** Closing of internal pages (see includes/shell-top.php). */
?>
</main>

<nav class="bottomnav" aria-label="Main navigation">
  <?php foreach (NAV_ITEMS as $key => [$label, $iconName, $href]): ?>
    <a href="<?= e($href) ?>" class="bottomnav-item<?= $key === $activeNav ? ' active' : '' ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>>
      <?= icon($iconName, 20) ?>
      <span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
  <button type="button" class="bottomnav-item" data-sidebar-open aria-controls="sidebar" aria-expanded="false">
    <?= icon('menu', 20) ?>
    <span>Menu</span>
  </button>
</nav>
<?php require __DIR__ . '/footer.php'; ?>
