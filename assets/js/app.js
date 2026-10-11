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
})();
