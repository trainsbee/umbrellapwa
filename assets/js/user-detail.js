// User detail (admin): shows the alerts only when the user has access, and
// sends a test notification to the user's devices.
(function () {
  'use strict';

  var accessToggle = document.querySelector('[data-access-toggle]');
  var alerts = document.querySelector('[data-alerts]');
  if (accessToggle && alerts) {
    accessToggle.addEventListener('change', function () { alerts.hidden = !accessToggle.checked; });
  }

  var testBtn = document.querySelector('[data-test-user]');
  var statusEl = document.querySelector('[data-test-status]');
  if (!testBtn) return;
  testBtn.addEventListener('click', function () {
    testBtn.disabled = true;
    fetch('api/push-test.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': testBtn.getAttribute('data-csrf') },
      body: JSON.stringify({ user_id: testBtn.getAttribute('data-test-user') })
    }).then(function (r) { return r.json(); }).then(function (res) {
      statusEl.hidden = false;
      statusEl.textContent = res.ok ? 'Test sent to ' + res.sent + ' of ' + res.devices + ' devices.' : (res.error || 'The test could not be sent.');
      statusEl.className = 'bot-status bot-status--' + (res.ok ? 'ok' : 'error');
    }).finally(function () { testBtn.disabled = false; });
  });
})();
