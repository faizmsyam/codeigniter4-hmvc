/* FMS PWA service worker: cache hanya untuk client yang berjalan sebagai PWA. */
const CACHE_VERSION = 'fms-pwa-v3';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = './offline.html';
const APP_SHELL = [
  './offline.html',
  './assets/fms/pwa/icon-192.png',
  './assets/fms/pwa/icon-512.png',
  './assets/fms/pwa/icon-maskable-192.png',
  './assets/fms/pwa/icon-maskable-512.png'
];

/* Context disimpan per client, bukan global, agar tab browser dan PWA terisolasi. */
const pwaClients = new Map();

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(APP_SHELL)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(CACHE_VERSION)).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

function isCacheableResponse(response) {
  return response && response.ok && (response.type === 'basic' || response.type === 'cors');
}

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  /* Browser biasa selalu network-only. Tidak ada cache/offline interception. */
  if (!pwaClients.get(event.clientId)) return;

  const url = new URL(request.url);
  const scopeUrl = new URL(self.registration.scope);
  const scopePath = scopeUrl.pathname.endsWith('/') ? scopeUrl.pathname : `${scopeUrl.pathname}/`;
  const apiPath = `${scopePath}api/`;
  if (url.origin !== self.location.origin || url.pathname.startsWith(apiPath)) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  const cacheableDestination = ['style', 'script', 'image', 'font'].includes(request.destination);
  if (!cacheableDestination) return;

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request)
        .then((response) => {
          if (isCacheableResponse(response)) {
            const clone = response.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(request, clone));
          }
          return response;
        })
        .catch(() => Response.error());
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data === 'SKIP_WAITING') {
    self.skipWaiting();
    return;
  }

  if (event.data && event.data.type === 'FMS_PWA_CONTEXT' && event.source && event.source.id) {
    pwaClients.set(event.source.id, event.data.active === true);
  }
});

self.addEventListener('messageerror', (event) => {
  if (event.source && event.source.id) pwaClients.delete(event.source.id);
});
