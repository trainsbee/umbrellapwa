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

  // Panel en vivo: cada pocos segundos entra un evento nuevo (ilustrativo).
  var feed = document.querySelector('[data-landing-feed]');
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (feed && !reduced) {
    var TIPOS = [
      ['user-plus', 'lead', 'Nuevo lead', ['Formulario web', 'Campaña de referidos', 'Llamada entrante', 'Landing de producto'], 'Asignar'],
      ['edit-3', 'firmando', 'Cliente firmando', ['en proceso'], 'Seguir'],
      ['check-circle', 'firmado', 'Cliente firmó', ['completado'], 'Ver'],
      ['shield', 'compliance', 'Compliance completado', ['Verificación aprobada'], 'Revisar'],
    ];
    var NOMBRES = ['Andrea M.', 'Carlos R.', 'Lucía P.', 'Jorge A.', 'Sofía V.', 'Daniel H.', 'Valeria T.', 'Miguel S.'];
    var contrato = 4822;
    var pick = function (list) { return list[Math.floor(Math.random() * list.length)]; };

    var stamp = function (li) { li.setAttribute('data-ts', String(Date.now())); };
    feed.querySelectorAll('li').forEach(function (li, i) { li.setAttribute('data-ts', String(Date.now() - (i + 1) * 9000)); });

    var refreshTimes = function () {
      feed.querySelectorAll('li').forEach(function (li) {
        var secs = Math.round((Date.now() - Number(li.getAttribute('data-ts'))) / 1000);
        li.querySelector('.landing-evento-hora').textContent = secs < 3 ? 'ahora' : (secs < 60 ? 'hace ' + secs + ' s' : 'hace ' + Math.round(secs / 60) + ' min');
      });
    };

    var addEvent = function () {
      var t = pick(TIPOS);
      var detalle = t[1] === 'lead' ? pick(NOMBRES) + ' · ' + pick(t[3])
        : (t[1] === 'compliance' ? t[3][0] + ' · #' + (contrato - 3) : 'Contrato #' + (contrato++) + ' · ' + t[3][0]);
      var li = document.createElement('li');
      li.className = 'landing-evento evento-' + t[1];
      li.innerHTML = '<span class="landing-evento-icono"><i data-feather="' + t[0] + '" width="15" height="15"></i></span>' +
        '<span class="landing-evento-texto"><strong>' + t[2] + '</strong><small>' + detalle + '</small></span>' +
        '<span class="landing-evento-lado"><span class="landing-evento-hora">ahora</span><span class="landing-evento-accion">' + t[4] + '</span></span>';
      stamp(li);
      feed.insertBefore(li, feed.firstChild);
      if (window.feather) window.feather.replace();
      while (feed.children.length > 4) feed.removeChild(feed.lastChild);
    };

    var timer = null;
    var start = function () { if (!timer) timer = setInterval(function () { addEvent(); refreshTimes(); }, 3800); };
    var stop = function () { clearInterval(timer); timer = null; };
    refreshTimes();
    setInterval(refreshTimes, 1000);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) { entries[0].isIntersecting ? start() : stop(); }).observe(feed);
    } else {
      start();
    }
  }
})();
