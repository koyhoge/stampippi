// Stampippi minimal Service Worker
// 方針: HTMLや認証付きページはキャッシュしない（ネットワーク優先で素通り）。
// 静的アセット（アイコン類・manifest・CSS/JS）だけを stale-while-revalidate 気味に扱う。
const CACHE = 'stampippi-static-v1';
const STATIC_PATHS = [
  '/favicon.ico',
  '/favicon.svg',
  '/apple-touch-icon.png',
  '/icon-48x48.png',
  '/icon-192x192.png',
  '/icon-512x512.png',
  '/manifest.json',
  '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(STATIC_PATHS)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  const isStatic = url.origin === self.location.origin &&
    (STATIC_PATHS.includes(url.pathname) || url.pathname.startsWith('/build/assets/'));

  if (!isStatic) return; // それ以外は通常のネットワーク取得（キャッシュしない）

  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request).then((response) => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      });
      return cached || network;
    })
  );
});
