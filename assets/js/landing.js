// Landing page: header background on scroll, section reveal, sticky install
// bar and the behavior of the "Install app" buttons.
(function () {
  'use strict';

  var header = document.querySelector('.landing-header');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Fade sections in as they enter the viewport
  var items = document.querySelectorAll('.reveal');
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

  // Sticky bar: visible once the hero button is off screen, except over the
  // final section (which has its own button).
  var bar = document.querySelector('[data-landing-sticky]');
  var heroBtn = document.getElementById('heroInstall');
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

  // Install: opens the platform's install sheet; if the browser cannot
  // install apps, explain what to do.
  var note = document.querySelector('[data-landing-note]');
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-landing-install]')) return;
    var api = window.pwaInstall;
    if (api && api.isStandalone()) { location.href = 'login.php'; return; }
    if (api && api.open()) return;
    if (note) {
      note.textContent = 'Your browser cannot install apps. Open this page in Chrome, Edge or Safari to install it, or continue in the browser.';
      note.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });

  // Live panel: every few seconds a new (illustrative) event comes in.
  var feed = document.querySelector('[data-landing-feed]');
  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (feed && !reduced) {
    // [icon, type, title, details, action]
    var EVENT_TYPES = [
      ['user-plus', 'lead', 'New lead', ['Web form', 'Referral campaign', 'Inbound call', 'Product landing page'], 'Assign'],
      ['edit-3', 'signing', 'Client signing', ['in progress'], 'Follow'],
      ['check-circle', 'signed', 'Client signed', ['completed'], 'View'],
      ['shield', 'compliance', 'Compliance completed', ['Verification approved'], 'Review'],
    ];
    var NAMES = ['Andrea M.', 'Carlos R.', 'Lucía P.', 'Jorge A.', 'Sofía V.', 'Daniel H.', 'Valeria T.', 'Miguel S.'];
    var contractNumber = 4822;
    var pick = function (list) { return list[Math.floor(Math.random() * list.length)]; };

    var stamp = function (li) { li.setAttribute('data-ts', String(Date.now())); };
    feed.querySelectorAll('li').forEach(function (li, i) { li.setAttribute('data-ts', String(Date.now() - (i + 1) * 9000)); });

    var refreshTimes = function () {
      feed.querySelectorAll('li').forEach(function (li) {
        var secs = Math.round((Date.now() - Number(li.getAttribute('data-ts'))) / 1000);
        li.querySelector('.landing-event-time').textContent = secs < 3 ? 'now' : (secs < 60 ? secs + 's ago' : Math.round(secs / 60) + 'm ago');
      });
    };

    var addEvent = function () {
      var t = pick(EVENT_TYPES);
      var details = t[1] === 'lead' ? pick(NAMES) + ' · ' + pick(t[3])
        : (t[1] === 'compliance' ? t[3][0] + ' · #' + (contractNumber - 3) : 'Contract #' + (contractNumber++) + ' · ' + t[3][0]);
      var li = document.createElement('li');
      li.className = 'landing-event event-' + t[1];
      li.innerHTML = '<span class="landing-event-icon"><i data-feather="' + t[0] + '" width="15" height="15"></i></span>' +
        '<span class="landing-event-text"><strong>' + t[2] + '</strong><small>' + details + '</small></span>' +
        '<span class="landing-event-side"><span class="landing-event-time">now</span><span class="landing-event-action">' + t[4] + '</span></span>';
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
