/**
 * Flujo de instalacion de la PWA en todas las plataformas.
 *
 *  - Chrome / Edge / Samsung Internet: usan el evento beforeinstallprompt
 *    y el boton "Instalar" abre el dialogo nativo.
 *  - iPhone / iPad: no hay dialogo nativo; se muestran instrucciones
 *    (Compartir -> Agregar a inicio).
 *  - Safari en macOS: instrucciones (Archivo -> Agregar al Dock).
 *
 * Cualquier elemento con [data-pwa-install] abre la hoja manualmente y
 * se muestra solo cuando la instalacion es posible.
 */
(function () {
  'use strict';

  var DISMISS_KEY = 'umbrella-install-dismissed-at';
  var DISMISS_DAYS = 7;
  var AUTO_SHOW_DELAY = 1500;

  var deferredPrompt = null;
  var platform = null;

  function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
      window.matchMedia('(display-mode: window-controls-overlay)').matches ||
      window.navigator.standalone === true;
  }

  function detectManualPlatform() {
    var ua = navigator.userAgent;
    // iPadOS 13+ se identifica como Mac, pero tiene pantalla tactil.
    var isIOS = /iphone|ipad|ipod/i.test(ua) ||
      (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1);
    if (isIOS) return 'ios';

    var isMacSafari = /Macintosh/.test(ua) && /Safari\//.test(ua) &&
      !/Chrome|Chromium|CriOS|Edg|OPR|Firefox|FxiOS/.test(ua);
    if (isMacSafari) return 'mac-safari';

    return null;
  }

  function storageGet(key) {
    try { return localStorage.getItem(key); } catch (e) { return null; }
  }
  function storageSet(key, value) {
    try { localStorage.setItem(key, value); } catch (e) {}
  }

  function recentlyDismissed() {
    var at = parseInt(storageGet(DISMISS_KEY) || '0', 10);
    return at && (Date.now() - at) < DISMISS_DAYS * 864e5;
  }

  function sheet() {
    return document.getElementById('installSheet');
  }

  function setInstallButtonsVisible(visible) {
    document.querySelectorAll('[data-pwa-install]').forEach(function (el) {
      el.hidden = !visible;
    });
  }

  function openSheet() {
    var el = sheet();
    if (!el || !platform) return;
    el.querySelectorAll('[data-install-platform]').forEach(function (section) {
      section.hidden = section.getAttribute('data-install-platform') !== platform;
    });
    el.hidden = false;
    document.documentElement.classList.add('install-sheet-open');
  }

  function closeSheet(remember) {
    var el = sheet();
    if (el) el.hidden = true;
    document.documentElement.classList.remove('install-sheet-open');
    if (remember) storageSet(DISMISS_KEY, String(Date.now()));
  }

  function setPlatform(value) {
    platform = value;
    setInstallButtonsVisible(!!platform);
    if (platform && !recentlyDismissed()) {
      setTimeout(openSheet, AUTO_SHOW_DELAY);
    }
  }

  if (isStandalone()) return;

  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    setPlatform('prompt');
  });

  window.addEventListener('appinstalled', function () {
    deferredPrompt = null;
    platform = null;
    setInstallButtonsVisible(false);
    closeSheet(false);
  });

  document.addEventListener('DOMContentLoaded', function () {
    var el = sheet();
    if (!el) return;

    el.querySelectorAll('[data-install-dismiss]').forEach(function (btn) {
      btn.addEventListener('click', function () { closeSheet(true); });
    });

    var confirmBtn = el.querySelector('[data-install-confirm]');
    if (confirmBtn) {
      confirmBtn.addEventListener('click', function () {
        if (!deferredPrompt) { closeSheet(false); return; }
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then(function (choice) {
          if (choice.outcome !== 'accepted') storageSet(DISMISS_KEY, String(Date.now()));
        }).finally(function () {
          deferredPrompt = null;
        });
        closeSheet(false);
      });
    }

    // Clic fuera del panel o tecla Escape cierran la hoja.
    el.addEventListener('click', function (e) {
      if (e.target === el) closeSheet(true);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !el.hidden) closeSheet(true);
    });

    document.querySelectorAll('[data-pwa-install]').forEach(function (btn) {
      btn.addEventListener('click', openSheet);
    });

    setInstallButtonsVisible(!!platform);
    if (!platform) {
      var manual = detectManualPlatform();
      if (manual) setPlatform(manual);
    }
  });
})();
