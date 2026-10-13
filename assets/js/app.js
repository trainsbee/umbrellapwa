// App JavaScript entry point.
(function () {
  'use strict';

  // Icons: <i data-feather="name"> -> SVG (see includes/icons.php)
  if (window.feather) window.feather.replace();

  // Show / hide password: <button data-toggle-password="inputId">
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-toggle-password'));
    if (!input) return;
    var visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    btn.classList.toggle('is-visible', visible);
    btn.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
  });

  // Sync: [data-sync] reloads the current page with fresh data (pages are
  // never cached, see sw.js) and checks for a new app version on the way.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-sync]');
    if (!btn || btn.classList.contains('is-syncing')) return;
    var label = btn.querySelector('span:last-child');

    if (!navigator.onLine) {
      btn.classList.add('is-offline');
      if (label) label.textContent = 'Offline';
      setTimeout(function () {
        btn.classList.remove('is-offline');
        if (label) label.textContent = 'Sync';
      }, 2000);
      return;
    }

    btn.classList.add('is-syncing');
    if (label) label.textContent = 'Syncing';
    var update = Promise.resolve();
    if ('serviceWorker' in navigator) {
      update = navigator.serviceWorker.getRegistration()
        .then(function (reg) { return reg && reg.update(); })
        .catch(function () {});
    }
    // Never wait more than 3 s for the update check.
    Promise.race([update, new Promise(function (r) { setTimeout(r, 3000); })])
      .then(function () { window.location.reload(); });
  });

  // Light / dark theme: any [data-theme-toggle] switches it.
  var THEME_KEY = 'umbrella-theme';
  var THEME_COLORS = { dark: '#0f1012', light: '#f6f6f7' };

  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
  }

  function applyTheme(theme) {
    if (theme === 'light') {
      document.documentElement.setAttribute('data-theme', 'light');
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', THEME_COLORS[theme]);
    document.querySelectorAll('[data-theme-toggle]').forEach(function (el) {
      el.setAttribute('aria-checked', theme === 'dark' ? 'true' : 'false');
    });
    try { localStorage.setItem(THEME_KEY, theme); } catch (e) {}
  }

  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-theme-toggle]')) return;
    e.preventDefault();
    applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
  });

  document.querySelectorAll('[data-theme-toggle]').forEach(function (el) {
    el.setAttribute('aria-checked', currentTheme() === 'dark' ? 'true' : 'false');
  });

  // Sidebar: [data-sidebar-open] opens it; [data-sidebar-close], the
  // backdrop and the Escape key close it.
  var root = document.documentElement;
  var lastFocus = null;

  function setSidebar(open) {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    root.classList.toggle('sidebar-is-open', open);
    document.querySelectorAll('[data-sidebar-open]').forEach(function (el) {
      el.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    if (open) {
      lastFocus = document.activeElement;
      var closeBtn = sidebar.querySelector('.sidebar-close-btn');
      if (closeBtn) setTimeout(function () { closeBtn.focus(); }, 50);
    } else if (lastFocus) {
      lastFocus.focus();
      lastFocus = null;
    }
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('[data-sidebar-open]')) { setSidebar(true); return; }
    if (e.target.closest('[data-sidebar-close]')) setSidebar(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && root.classList.contains('sidebar-is-open')) setSidebar(false);
  });

  // Notice shown when the connection drops while the app is open.
  function setOfflineBanner(offline) {
    var banner = document.getElementById('connectionNotice');
    if (!offline) { if (banner) banner.remove(); return; }
    if (banner || document.querySelector('.empty-state--offline')) return;
    banner = document.createElement('div');
    banner.id = 'connectionNotice';
    banner.className = 'connection-notice';
    banner.setAttribute('role', 'status');
    banner.innerHTML = '<i data-feather="wifi-off" width="14" height="14"></i><span>You are offline. Connect to the internet to see your data.</span>';
    document.body.appendChild(banner);
    if (window.feather) window.feather.replace();
  }
  window.addEventListener('offline', function () { setOfflineBanner(true); });
  window.addEventListener('online', function () { setOfflineBanner(false); });
  if (!navigator.onLine) setOfflineBanner(true);

  // <input data-autosubmit> submits its form as soon as the value changes
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-autosubmit]') && e.target.form) e.target.form.submit();
  });

  // In-page tabs: [data-tabs] buttons with data-tab="x" show [data-tab-panel="x"]
  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-tabs] [data-tab]');
    if (!tab) return;
    var key = tab.getAttribute('data-tab');
    tab.closest('[data-tabs]').querySelectorAll('[data-tab]').forEach(function (t) {
      t.classList.toggle('active', t === tab);
      t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
    });
    document.querySelectorAll('[data-tab-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-tab-panel') !== key;
    });
  });

  // <input data-filter-list="#list"> hides items whose data-filter-text does not match
  document.addEventListener('input', function (e) {
    var input = e.target.closest('[data-filter-list]');
    if (!input) return;
    var term = input.value.trim().toLowerCase();
    document.querySelectorAll(input.getAttribute('data-filter-list') + ' [data-filter-text]').forEach(function (item) {
      item.hidden = term !== '' && item.getAttribute('data-filter-text').indexOf(term) === -1;
    });
  });
})();
