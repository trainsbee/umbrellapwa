// Active users: marks who is online right now (events from pwa/presence.js)
// and keeps them at the top of the list.
(function () {
  'use strict';

  var list = document.getElementById('activeList');
  if (!list) return;
  var counter = document.querySelector('[data-online-count]');

  window.addEventListener('presence:change', function (e) {
    var online = e.detail.online;
    var rows = Array.prototype.slice.call(list.querySelectorAll('[data-presence-token]'));
    var count = 0;

    rows.forEach(function (row) {
      var isOnline = online.has(row.getAttribute('data-presence-token'));
      if (isOnline) count++;
      row.classList.toggle('is-online', isOnline);
      row.querySelector('.presence-online').hidden = !isOnline;
      row.querySelector('.presence-ago').hidden = isOnline;
    });

    // Online first, then by last seen (the server order)
    rows.sort(function (a, b) {
      var d = b.classList.contains('is-online') - a.classList.contains('is-online');
      return d || b.getAttribute('data-last-seen').localeCompare(a.getAttribute('data-last-seen'));
    }).forEach(function (row) { list.appendChild(row); });

    if (counter) counter.textContent = count;
  });
})();
