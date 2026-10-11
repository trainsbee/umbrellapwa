<?php
declare(strict_types=1);

/**
 * Hoja "Instalar app". La controla assets/js/pwa/install.js, que muestra
 * solo la seccion de la plataforma detectada:
 *   prompt     -> Chrome / Edge / Samsung (Android, Windows, macOS, ChromeOS)
 *   ios        -> iPhone / iPad (Safari y otros navegadores en iOS)
 *   mac-safari -> Safari en macOS (Agregar al Dock)
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
        <button type="button" class="btn-secondary" data-install-dismiss>Ahora no</button>
        <button type="button" class="btn-primary" data-install-confirm>Instalar</button>
      </div>
    </div>

    <div data-install-platform="ios" hidden>
      <ol class="install-steps">
        <li>Toca el boton <strong>Compartir</strong> <span aria-hidden="true">&#x2B06;&#xFE0E;</span> en la barra del navegador.</li>
        <li>Elige <strong>"Agregar a inicio"</strong>.</li>
        <li>Toca <strong>"Agregar"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn-primary" data-install-dismiss>Entendido</button>
      </div>
    </div>

    <div data-install-platform="mac-safari" hidden>
      <ol class="install-steps">
        <li>En la barra de menu abre <strong>Archivo</strong>.</li>
        <li>Elige <strong>"Agregar al Dock"</strong>.</li>
      </ol>
      <div class="install-sheet-actions">
        <button type="button" class="btn-primary" data-install-dismiss>Entendido</button>
      </div>
    </div>
  </div>
</div>
