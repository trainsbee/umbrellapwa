/**
 * Registra el service worker y avisa cuando hay una version nueva.
 * La version nueva queda en espera hasta que el usuario toca "Actualizar".
 */
(function () {
  'use strict';

  if (!('serviceWorker' in navigator)) return;

  var refreshing = false;

  function showUpdateToast(worker) {
    if (document.getElementById('pwaUpdateToast')) return;

    var toast = document.createElement('div');
    toast.id = 'pwaUpdateToast';
    toast.className = 'pwa-toast';
    toast.setAttribute('role', 'status');
    toast.innerHTML =
      '<span>Hay una nueva versión disponible.</span>' +
      '<button type="button" class="btn btn--primario">Actualizar</button>';

    toast.querySelector('button').addEventListener('click', function () {
      worker.postMessage({ type: 'SKIP_WAITING' });
    });

    document.body.appendChild(toast);
  }

  function watchInstalling(worker) {
    worker.addEventListener('statechange', function () {
      // Solo es "actualizacion" si ya habia un SW controlando la pagina.
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

      // Revisar actualizaciones al volver a la app (util en PWA instalada).
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') reg.update();
      });
    }).catch(function (err) {
      console.error('No se pudo registrar el service worker:', err);
    });
  });

  navigator.serviceWorker.addEventListener('controllerchange', function () {
    if (refreshing) return;
    refreshing = true;
    window.location.reload();
  });
})();
