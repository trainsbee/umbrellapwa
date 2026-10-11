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
})();
