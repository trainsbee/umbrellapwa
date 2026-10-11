// Punto de entrada del JS de la app.
(function () {
  'use strict';

  // Iconos: <i data-feather="nombre"> -> SVG (ver includes/icons.php)
  if (window.feather) window.feather.replace();

  // Mostrar / ocultar contrasena: <button data-toggle-password="idDelInput">
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    var input = document.getElementById(btn.getAttribute('data-toggle-password'));
    if (!input) return;
    var visible = input.type === 'password';
    input.type = visible ? 'text' : 'password';
    btn.classList.toggle('es-visible', visible);
    btn.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });

  // Tema claro / oscuro: cualquier [data-theme-toggle] lo cambia.
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

  // Menu lateral: [data-sidebar-open] lo abre; [data-sidebar-close], el
  // fondo oscuro y la tecla Escape lo cierran.
  var root = document.documentElement;
  var lastFocus = null;

  function setSidebar(open) {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    root.classList.toggle('sidebar-abierto', open);
    document.querySelectorAll('[data-sidebar-open]').forEach(function (el) {
      el.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    if (open) {
      lastFocus = document.activeElement;
      var closeBtn = sidebar.querySelector('.sidebar-cerrar');
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
    if (e.key === 'Escape' && root.classList.contains('sidebar-abierto')) setSidebar(false);
  });

  // Aviso cuando se pierde la conexion con la app abierta.
  function setOfflineBanner(offline) {
    var banner = document.getElementById('avisoConexion');
    if (!offline) { if (banner) banner.remove(); return; }
    if (banner || document.querySelector('.vacio--offline')) return;
    banner = document.createElement('div');
    banner.id = 'avisoConexion';
    banner.className = 'aviso-conexion';
    banner.setAttribute('role', 'status');
    banner.innerHTML = '<i data-feather="wifi-off" width="14" height="14"></i><span>Sin conexión. Conéctate a internet para ver los datos.</span>';
    document.body.appendChild(banner);
    if (window.feather) window.feather.replace();
  }
  window.addEventListener('offline', function () { setOfflineBanner(true); });
  window.addEventListener('online', function () { setOfflineBanner(false); });
  if (!navigator.onLine) setOfflineBanner(true);
})();
