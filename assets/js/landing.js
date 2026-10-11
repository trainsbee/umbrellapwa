// Landing: cabecera con fondo al bajar, aparicion de secciones, boton fijo
// de instalar y accion de los botones "Instalar app".
(function () {
  'use strict';

  var header = document.querySelector('.landing-cabecera');
  if (header) {
    var onScroll = function () { header.classList.toggle('con-fondo', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Aparicion suave al entrar en pantalla
  var items = document.querySelectorAll('.revelar');
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    items.forEach(function (el) { el.classList.add('visible'); });
  } else {
    var revealer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('visible');
        revealer.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach(function (el) { revealer.observe(el); });
  }

  // Boton fijo: visible cuando el boton principal ya no esta en pantalla,
  // salvo en la seccion final (que ya tiene su propio boton).
  var bar = document.querySelector('[data-landing-fijo]');
  var heroBtn = document.getElementById('instalarHeroe');
  var finalSection = document.querySelector('.landing-final');
  if (bar && heroBtn && 'IntersectionObserver' in window) {
    var heroVisible = true;
    var finalVisible = false;
    var update = function () {
      var show = !heroVisible && !finalVisible;
      bar.classList.toggle('visible', show);
      bar.setAttribute('aria-hidden', show ? 'false' : 'true');
      bar.querySelectorAll('button').forEach(function (b) { b.tabIndex = show ? 0 : -1; });
    };
    new IntersectionObserver(function (entries) {
      heroVisible = entries[0].isIntersecting;
      update();
    }).observe(heroBtn);
    if (finalSection) {
      new IntersectionObserver(function (entries) {
        finalVisible = entries[0].isIntersecting;
        update();
      }, { threshold: 0.4 }).observe(finalSection);
    }
  }

  // Instalar: abre la hoja de instalacion de la plataforma; si el navegador
  // no permite instalar, explica que hacer.
  var note = document.querySelector('[data-landing-nota]');
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-landing-install]')) return;
    var api = window.pwaInstall;
    if (api && api.isStandalone()) { location.href = 'login.php'; return; }
    if (api && api.open()) return;
    if (note) {
      note.textContent = 'Tu navegador no permite instalar apps. Ábrela en Chrome, Edge o Safari para instalarla, o continúa en el navegador.';
      note.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });
})();
