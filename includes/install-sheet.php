<?php
declare(strict_types=1);

/**
 * "Install app" sheet. Driven by assets/js/pwa/install.js, which shows only
 * the section for the detected platform:
 *   prompt     -> Chrome / Edge / Samsung (Android, Windows, macOS, ChromeOS)
 *   ios        -> iPhone / iPad (Safari and other iOS browsers)
 *   mac-safari -> Safari on macOS (Add to Dock)
 */
?>
<div class="install-sheet" id="installSheet" role="dialog" aria-modal="true" aria-labelledby="installSheetTitle" hidden>
  <div class="install-sheet-panel">
    <div class="install-sheet-header">
      <img src="assets/icons/icon-192.png" alt="" width="48" height="48">
      <div>
        <h2 id="installSheetTitle">Install <?= e(APP_NAME) ?></h2>
        <p>Quick access from your home screen, and it works like a native app.</p>
      </div>
    </div>

    <div data-install-platform="prompt" hidden>
      <div class="install-sheet-actions">
        <button type="button" class="btn" data-install-dismiss>Not now</button>
        <button type="button" class="btn btn--primary" data-install-confirm>Install</button>
      </div>
    </div>

    <div data-install-platform="ios" hidden>
      <ol class="install-steps">
        <li>Tap the <strong>Share</strong> button <span aria-hidden="true">&#x2B06;&#xFE0E;</span> in the browser bar.</li>
        <li>Choose <strong>"Add to Home Screen"</strong>.</li>
        <li>Tap <strong>"Add"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn btn--primary" data-install-dismiss>Got it</button>
      </div>
    </div>

    <div data-install-platform="mac-safari" hidden>
      <ol class="install-steps">
        <li>In the menu bar, open <strong>File</strong>.</li>
        <li>Choose <strong>"Add to Dock"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn btn--primary" data-install-dismiss>Got it</button>
      </div>
    </div>
  </div>
</div>
