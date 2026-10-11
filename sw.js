/**
 * Service worker de UmbrellaApp.
 *
 * Estrategias:
 *  - Paginas (navegacion): red primero. Si no hay conexion -> offline.html.
 *    Las paginas PHP NO se guardan en cache para no dejar datos privados
 *    en el dispositivo.
 *  - Assets estaticos (css, js, iconos, fuentes, imagenes): se sirven desde
 *    cache y se actualizan en segundo plano (stale-while-revalidate).
 *
 * Al publicar cambios en assets, sube CACHE_VERSION para que los
 * usuarios reciban la nueva version.
 */

const CACHE_VERSION = 'v2';
const STATIC_CACHE = 'umbrella-static-' + CACHE_VERSION;
const OFFLINE_URL = 'offline.html';

const PRECACHE = [
  OFFLINE_URL,
  'manifest.webmanifest',
  'assets/css/app.css',
  'assets/js/app.js',
  'assets/js/vendor/feather.min.js',
  'assets/js/pwa/register-sw.js',
  'assets/js/pwa/install.js',
  'assets/icons/favicon.svg',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'assets/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) =>
      // cache: 'reload' evita copiar versiones viejas del cache HTTP
      cache.addAll(PRECACHE.map((url) => new Request(url, { cache: 'reload' })))
    )
  );
  // No se llama skipWaiting() aqui: la pagina pregunta al usuario antes
  // de activar una version nueva (ver assets/js/pwa/register-sw.js).
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(
      keys
        .filter((key) => key.startsWith('umbrella-') && key !== STATIC_CACHE)
        .map((key) => caches.delete(key))
    );
    if (self.registration.navigationPreload) {
      await self.registration.navigationPreload.enable();
    }
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  if (req.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  if (req.mode === 'navigate') {
    event.respondWith(networkFirstPage(event));
    return;
  }

  if (/\.(css|js|png|jpg|jpeg|svg|webp|ico|woff2?|webmanifest)$/.test(url.pathname)) {
    event.respondWith(staleWhileRevalidate(event));
  }
});

async function networkFirstPage(event) {
  try {
    const preloaded = await event.preloadResponse;
    if (preloaded) return preloaded;
    return await fetch(event.request);
  } catch (err) {
    const cache = await caches.open(STATIC_CACHE);
    return (await cache.match(OFFLINE_URL)) || Response.error();
  }
}

async function staleWhileRevalidate(event) {
  const cache = await caches.open(STATIC_CACHE);
  // ignoreSearch: los assets llevan ?v=APP_VERSION en el HTML
  const cached = await cache.match(event.request, { ignoreSearch: true });
  const network = fetch(event.request)
    .then((res) => {
      if (res.ok) cache.put(event.request, res.clone());
      return res;
    })
    .catch(() => cached);

  if (cached) {
    event.waitUntil(network);
    return cached;
  }
  return network;
}
