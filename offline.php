<?php
declare(strict_types=1);

/**
 * Offline view. The service worker caches it and serves it in place of any
 * page when there is no connection: the app shell (menu, dark mode,
 * navigation) is shown but no data.
 * Since it is served at the URL the user requested, the script below sets
 * the title and highlights the matching section.
 */

require_once __DIR__ . '/includes/config.php';

$pageTitle = 'Offline';
$activeNav = '';
// Generic identity: this page is cached on the device, so it never shows who was signed in
$user = ['name' => 'Umbrella', 'roles' => []];
require __DIR__ . '/includes/shell-top.php';
?>
<section class="empty-state empty-state--offline">
  <?= icon('wifi-off', 22) ?>
  <h2>You are offline</h2>
  <p>Connect to the internet to see your data.</p>
  <button type="button" class="btn btn--primary" data-retry><?= icon('refresh-cw', 14) ?> Try again</button>
</section>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var page = location.pathname.split('/').pop() || 'home.php';
    document.querySelectorAll('.bottomnav-item[href], .sidebar .row[href]').forEach(function (link) {
      if (link.getAttribute('href') !== page) return;
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
      var label = link.querySelector('span');
      var title = document.querySelector('.topbar-title');
      if (label && title && link.classList.contains('bottomnav-item')) title.textContent = label.textContent;
    });
    document.querySelector('[data-retry]').addEventListener('click', function () { location.reload(); });
    window.addEventListener('online', function () { location.reload(); });
  });
</script>
<?php require __DIR__ . '/includes/shell-bottom.php'; ?>
