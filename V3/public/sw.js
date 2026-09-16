/**
 * Service Worker for God's Family Church PWA
 * Caches core static assets and provides offline fallback for mobile devices.
 */

const CACHE_NAME = 'godsfam-cms-v1';
const ASSETS_TO_CACHE = [
  './',
  'assets/css/theme.css',
  'assets/css/main.css',
  'assets/css/enhanced.css',
  'assets/images/logo.png',
  'manifest.json'
];

// Install Event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[ServiceWorker] Pre-caching offline static assets');
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => self.skipWaiting())
  );
});

// Activate Event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[ServiceWorker] Clearing old cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event (Stale-while-revalidate for static assets, network-first for pages)
self.addEventListener('fetch', (event) => {
  // Only handle GET requests
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Cache static assets (CSS, JS, images, fonts)
  if (url.origin === location.origin && (url.pathname.includes('/assets/') || url.pathname.endsWith('.json'))) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          // Fetch update in background
          fetch(event.request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              caches.open(CACHE_NAME).then((cache) => cache.put(event.request, networkResponse));
            }
          }).catch(() => {/* ignore network errors offline */});
          return cachedResponse;
        }
        return fetch(event.request);
      })
    );
    return;
  }

  // Network first for PHP dynamic pages
  event.respondWith(
    fetch(event.request).catch(() => {
      return caches.match(event.request).then((response) => {
        if (response) return response;
        return caches.match('dashboard.php');
      });
    })
  );
});
