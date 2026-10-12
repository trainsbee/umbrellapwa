// Notifications page: turns push on/off for this device and sends a test.
(function () {
  'use strict';

  var root = document.querySelector('[data-push]');
  if (!root) return;
  var statusEl = root.querySelector('[data-push-status]');
  var enableBtn = root.querySelector('[data-push-enable]');
  var disableBtn = root.querySelector('[data-push-disable]');
  var testBtn = root.querySelector('[data-push-test]');
  var vapidKey = root.getAttribute('data-vapid');
  var csrf = root.getAttribute('data-csrf');

  var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) || (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1);
  var isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  function show(el, visible) { el.hidden = !visible; }
  function setStatus(text, tone) {
    statusEl.textContent = text;
    statusEl.className = 'push-status' + (tone ? ' push-status--' + tone : '');
  }

  function api(url, method, body) {
    return fetch(url, {
      method: method,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(body || {})
    }).then(function (r) { return r.json(); });
  }

  // VAPID public key (base64url) -> Uint8Array for pushManager.subscribe
  function keyBytes(base64) {
    var padded = (base64 + '===='.slice((base64.length % 4) || 4)).replace(/-/g, '+').replace(/_/g, '/');
    var raw = atob(padded);
    var bytes = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
    return bytes;
  }

  function render(subscription) {
    var on = !!subscription && Notification.permission === 'granted';
    show(enableBtn, !on);
    show(disableBtn, on);
    show(testBtn, on);
    if (Notification.permission === 'denied') {
      show(enableBtn, false);
      setStatus('Notifications are blocked for this app. Allow them in your browser or phone settings.', 'error');
    } else if (on) {
      setStatus('Notifications are on for this device.', 'ok');
    } else {
      setStatus('Notifications are off for this device.');
    }
  }

  if (!vapidKey) {
    setStatus('Notifications are not configured on the server yet.', 'error');
    return;
  }
  if (isIOS && !isStandalone) {
    setStatus('On iPhone and iPad, install the app first (Share → Add to Home Screen) and open it from your home screen.', 'warn');
    return;
  }
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
    setStatus('This browser does not support push notifications.', 'error');
    return;
  }

  // Drops any stale subscription (e.g. made with another key) and retries once
  function subscribe() {
    var options = { userVisibleOnly: true, applicationServerKey: keyBytes(vapidKey) };
    return registration.pushManager.subscribe(options).catch(function () {
      return registration.pushManager.getSubscription().then(function (old) {
        return old ? old.unsubscribe() : null;
      }).then(function () {
        return registration.pushManager.subscribe(options);
      });
    });
  }

  function friendlyError(err) {
    var message = (err && err.message) || '';
    if (/push service/i.test(message) || (err && err.name === 'AbortError')) {
      if (navigator.brave) {
        return 'Brave blocks push by default. Turn on Settings → Privacy and security → "Use Google services for push messaging", restart Brave and try again.';
      }
      return 'Your browser could not reach its push service. Turn off any VPN or blocker for this site, or try Chrome, Edge or Safari, then try again.';
    }
    return message || 'Notifications could not be turned on.';
  }

  var registration = null;
  navigator.serviceWorker.ready.then(function (reg) {
    registration = reg;
    return reg.pushManager.getSubscription();
  }).then(render);

  enableBtn.addEventListener('click', function () {
    enableBtn.disabled = true;
    setStatus('Waiting for permission…');
    Notification.requestPermission().then(function (permission) {
      if (permission !== 'granted') throw new Error('Permission was not granted.');
      return subscribe();
    }).then(function (subscription) {
      return api('api/push-subscription.php', 'POST', { subscription: subscription.toJSON() }).then(function (res) {
        if (!res.ok) throw new Error(res.error);
        render(subscription);
      });
    }).catch(function (err) {
      setStatus(friendlyError(err), 'error');
    }).finally(function () { enableBtn.disabled = false; });
  });

  disableBtn.addEventListener('click', function () {
    disableBtn.disabled = true;
    registration.pushManager.getSubscription().then(function (subscription) {
      if (!subscription) return null;
      var endpoint = subscription.endpoint;
      return subscription.unsubscribe().then(function () {
        return api('api/push-subscription.php', 'DELETE', { endpoint: endpoint });
      });
    }).then(function () { render(null); })
      .finally(function () { disableBtn.disabled = false; });
  });

  testBtn.addEventListener('click', function () {
    testBtn.disabled = true;
    api('api/push-test.php', 'POST', {}).then(function (res) {
      setStatus(res.ok ? 'Test sent. It should arrive in a few seconds.' : (res.error || 'The test could not be sent.'), res.ok ? 'ok' : 'error');
    }).finally(function () { testBtn.disabled = false; });
  });
})();
