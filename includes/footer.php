<?php
declare(strict_types=1);

/**
 * Document closing: global scripts and </body></html>.
 * Optional before including: $pageScripts (array) extra scripts for the page.
 */
?>
<?php require __DIR__ . '/install-sheet.php'; ?>
<script src="<?= e(asset('assets/js/vendor/feather.min.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/pwa/register-sw.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/pwa/install.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
<?php if (($presence = function_exists('presence_client_config') && !empty($user['id']) ? presence_client_config($user) : null) !== null): ?>
<script type="application/json" id="presenceConfig"><?= json_encode($presence, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) ?></script>
<script src="<?= e(asset('assets/js/pwa/presence.js')) ?>" defer></script>
<?php endif; ?>
<?php foreach ($pageScripts ?? [] as $script): ?>
<script src="<?= e(asset($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
