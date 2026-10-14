/**
 * UmbrellaApp service worker.
 *
 * Strategies:
 *  - Pages (navigation): always from the network. Pages are NOT cached
 *    (no private data, no stale versions). Offline, offline.php is shown:
 *    the app shell with a "connect to the internet" message.
 *  - Assets with ?v= (CSS and JS from the HTML, see asset() in config.php):
 *    the URL changes when the file changes, so they are treated as
 *    immutable: served from cache if that exact version is there, otherwise
 *    from the network (older versions are deleted). Offline, the last saved
 *    copy is used.
 *  - Other static assets (icons, manifest): cached and refreshed in the
 *    background (stale-while-revalidate).
 *
 * Only bump CACHE_VERSION to change the PRECACHE list or wipe the cache.
 */

const CACHE_VERSION = 'v9';
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
      // cache: 'reload' avoids copying stale versions from the HTTP cache
      cache.addAll(PRECACHE.map((url) => new Request(url, { cache: 'reload' })))
    )
  );
  // skipWaiting() is not called here: the page asks the user before
  // activating a new version (see assets/js/pwa/register-sw.js).
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

// The offline view is refreshed in the background while online so its
// layout matches the current version of the app.
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
    // Offline: keep the previous copy.
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
    // Offline: the last copy of that file, whatever its version.
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

/* ---------------------------------------------------------------- push */

// Shows a notification sent by the server (includes/push.php).
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (err) {
    data = { body: event.data ? event.data.text() : '' };
  }
  event.waitUntil(self.registration.showNotification(data.title || 'UmbrellaApp', {
    body: data.body || '',
    // Each alert has its own image (versioned URL from the server)
    icon: data.icon || 'assets/icons/icon-192.png?v=2',
    badge: 'assets/icons/badge-96.png',
    tag: data.tag || undefined,
    timestamp: Date.now(),
    // Button on Android and desktop (iOS ignores it; tapping the notification does the same)
    actions: data.url && data.url.indexOf('client.php') === 0 ? [{ action: 'open', title: 'View client' }] : [],
    data: { url: data.url || 'home.php' },
  }));
});

// Opens (or focuses) the app on the page the notification points to.
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const target = new URL(event.notification.data && event.notification.data.url ? event.notification.data.url : 'home.php', self.registration.scope).href;
  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of windows) {
      if (client.url.startsWith(self.registration.scope) && 'focus' in client) {
        await client.focus();
        return client.navigate(target);
      }
    }
    return self.clients.openWindow(target);
  })());
});

