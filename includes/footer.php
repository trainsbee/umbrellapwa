<?php
declare(strict_types=1);

/** Cierre del documento: scripts globales y </body></html>. */
?>
<?php require __DIR__ . '/install-sheet.php'; ?>
<script src="<?= e(asset('assets/js/vendor/feather.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/pwa/register-sw.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/pwa/install.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
