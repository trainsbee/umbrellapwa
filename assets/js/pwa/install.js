/**
 * PWA install flow for every platform.
 *
 *  - Chrome / Edge / Samsung Internet: use the beforeinstallprompt event and
 *    the "Install" button opens the native dialog.
 *  - iPhone / iPad: there is no native dialog; instructions are shown
 *    (Share -> Add to Home Screen).
 *  - Safari on macOS: instructions (File -> Add to Dock).
 *
 * Any [data-pwa-install] element opens the sheet manually and is only shown
 * when installing is possible.
 *
 * <body data-install-auto="off"> stops the sheet from opening by itself (the
 * landing page has its own buttons). API for other pages: window.pwaInstall.
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
    // iPadOS 13+ reports itself as a Mac, but it has a touch screen.
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
    var autoOff = document.body && document.body.getAttribute('data-install-auto') === 'off';
    if (platform && !autoOff && !recentlyDismissed()) {
      setTimeout(openSheet, AUTO_SHOW_DELAY);
    }
  }

  window.pwaInstall = {
    isStandalone: isStandalone,
    isAvailable: function () { return !!platform; },
    open: function () { openSheet(); return !!platform; },
  };

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

    // Clicking outside the panel or pressing Escape closes the sheet.
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
