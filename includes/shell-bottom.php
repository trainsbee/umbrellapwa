<?php
declare(strict_types=1);

/** Closing of internal pages (see includes/shell-top.php). */
?>
</main>

<nav class="bottomnav" aria-label="Main navigation">
  <?php $navMiddle = intdiv(count(NAV_ITEMS), 2); $navIndex = 0; ?>
  <?php foreach (NAV_ITEMS as $key => [$label, $iconName, $href]): ?>
    <?php if ($navIndex++ === $navMiddle): ?>
      <button type="button" class="bottomnav-item bottomnav-sync" data-sync aria-label="Sync data">
        <span class="bottomnav-sync-circle"><?= icon('refresh-cw', 20) ?></span>
        <span>Sync</span>
      </button>
    <?php endif; ?>
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
