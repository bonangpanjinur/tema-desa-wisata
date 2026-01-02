const CACHE_NAME = 'desa-wisata-v1';
const urlsToCache = [
  '/',
  '/wp-content/themes/tema-desa-wisata/style.css',
  '/wp-content/themes/tema-desa-wisata/assets/css/main.css',
  '/wp-content/themes/tema-desa-wisata/assets/js/main.js'
];

// Install Service Worker
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

// Strategi: Cache First, then Network
self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        if (response) {
          return response;
        }
        return fetch(event.request);
      })
  );
});