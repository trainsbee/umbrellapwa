/**
 * Service worker de UmbrellaApp.
 *
 * Estrategias:
 *  - Paginas (navegacion): siempre de la red. Las paginas NO se guardan en
 *    cache (ni datos privados ni versiones viejas). Sin conexion se muestra
 *    offline.php: la estructura de la app con el aviso "Conectate a internet".
 *  - Assets con ?v= (css y js del HTML, ver asset() en config.php): la URL
 *    cambia cuando cambia el archivo, asi que se tratan como inmutables:
 *    cache si ya esta esa version exacta; si no, red (y se borran las
 *    versiones viejas). Sin conexion se usa la ultima copia guardada.
 *  - Otros assets estaticos (iconos, manifest): cache y actualizacion en
 *    segundo plano (stale-while-revalidate).
 *
 * CACHE_VERSION solo hay que subirlo para cambiar la lista PRECACHE o
 * limpiar todo el cache.
 */

const CACHE_VERSION = 'v5';
const STATIC_CACHE = 'umbrella-static-' + CACHE_VERSION;
const OFFLINE_URL = 'offline.php';

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
    event.respondWith(url.searchParams.has('v') ? versionedAsset(event) : staleWhileRevalidate(event));
  }
});

// La vista offline se renueva en segundo plano mientras hay conexion, para
// que su estructura coincida con la version actual de la app.
const OFFLINE_REFRESH_MS = 10 * 60 * 1000;
let offlineRefreshedAt = 0;

async function networkFirstPage(event) {
  try {
    const preloaded = await event.preloadResponse;
    const res = preloaded || await fetch(event.request);
    if (Date.now() - offlineRefreshedAt > OFFLINE_REFRESH_MS) {
      offlineRefreshedAt = Date.now();
      event.waitUntil(refreshOffline());
    }
    return res;
  } catch (err) {
    const cache = await caches.open(STATIC_CACHE);
    return (await cache.match(OFFLINE_URL)) || Response.error();
  }
}

async function refreshOffline() {
  try {
    const res = await fetch(OFFLINE_URL, { cache: 'reload' });
    if (res.ok) await (await caches.open(STATIC_CACHE)).put(OFFLINE_URL, res);
  } catch (err) {
    // Sin conexion: se queda la copia anterior.
  }
}

async function versionedAsset(event) {
  const cache = await caches.open(STATIC_CACHE);
  const exact = await cache.match(event.request);
  if (exact) return exact;

  try {
    const res = await fetch(event.request);
    if (res.ok) {
      await deleteOtherVersions(cache, event.request.url);
      await cache.put(event.request, res.clone());
    }
    return res;
  } catch (err) {
    // Sin conexion: la ultima copia de ese archivo, sea cual sea su version.
    return (await cache.match(event.request, { ignoreSearch: true })) || Response.error();
  }
}

async function deleteOtherVersions(cache, url) {
  const path = new URL(url).pathname;
  const keys = await cache.keys();
  await Promise.all(
    keys
      .filter((req) => req.url !== url && new URL(req.url).pathname === path)
      .map((req) => cache.delete(req))
  );
}

async function staleWhileRevalidate(event) {
  const cache = await caches.open(STATIC_CACHE);
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
