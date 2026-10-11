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
        <h2 id="installSheetTitle">Instalar <?= e(APP_NAME) ?></h2>
        <p>Acceso directo desde tu pantalla de inicio y funciona como app.</p>
      </div>
    </div>

    <div data-install-platform="prompt" hidden>
      <div class="install-sheet-actions">
        <button type="button" class="btn" data-install-dismiss>Ahora no</button>
        <button type="button" class="btn btn--primary" data-install-confirm>Instalar</button>
      </div>
    </div>

    <div data-install-platform="ios" hidden>
      <ol class="install-steps">
        <li>Toca el botón <strong>Compartir</strong> <span aria-hidden="true">&#x2B06;&#xFE0E;</span> en la barra del navegador.</li>
        <li>Elige <strong>"Agregar a inicio"</strong>.</li>
        <li>Toca <strong>"Agregar"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn btn--primary" data-install-dismiss>Entendido</button>
      </div>
    </div>

    <div data-install-platform="mac-safari" hidden>
      <ol class="install-steps">
        <li>En la barra de menú abre <strong>Archivo</strong>.</li>
        <li>Elige <strong>"Agregar al Dock"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn btn--primary" data-install-dismiss>Entendido</button>
      </div>
    </div>
  </div>
</div>
