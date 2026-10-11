/**
 * Registers the service worker and announces new versions.
 * A new version waits until the user taps "Update".
 */
(function () {
  'use strict';

  if (!('serviceWorker' in navigator)) return;

  var refreshing = false;
  // On the first visit no SW controls the page; when the new one takes
  // control (clients.claim) there is no need to reload: it is not an update.
  var hadController = !!navigator.serviceWorker.controller;

  function showUpdateToast(worker) {
    if (document.getElementById('pwaUpdateToast')) return;

    var toast = document.createElement('div');
    toast.id = 'pwaUpdateToast';
    toast.className = 'pwa-toast';
    toast.setAttribute('role', 'status');
    toast.innerHTML =
      '<span>A new version is available.</span>' +
      '<button type="button" class="btn btn--primary">Update</button>';

    toast.querySelector('button').addEventListener('click', function () {
      worker.postMessage({ type: 'SKIP_WAITING' });
    });

    document.body.appendChild(toast);
  }

  function watchInstalling(worker) {
    worker.addEventListener('statechange', function () {
      // It is only an "update" if a SW was already controlling the page.
      if (worker.state === 'installed' && navigator.serviceWorker.controller) {
        showUpdateToast(worker);
      }
    });
  }

  window.addEventListener('load', function () {
    navigator.serviceWorker.register('sw.js').then(function (reg) {
      if (reg.waiting && navigator.serviceWorker.controller) {
        showUpdateToast(reg.waiting);
      }
      if (reg.installing) watchInstalling(reg.installing);
      reg.addEventListener('updatefound', function () {
        if (reg.installing) watchInstalling(reg.installing);
      });

      // Check for updates when returning to the app (useful when installed).
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') reg.update();
      });
    }).catch(function (err) {
      console.error('No se pudo registrar el service worker:', err);
    });
  });

  navigator.serviceWorker.addEventListener('controllerchange', function () {
    if (!hadController) { hadController = true; return; }
    if (refreshing) return;
    refreshing = true;
    window.location.reload();
  });
})();
