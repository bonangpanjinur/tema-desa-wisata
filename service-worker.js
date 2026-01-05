const CACHE_NAME = 'desa-wisata-v1';
const OFFLINE_URL = '/offline/'; // Pastikan Anda membuat page slug 'offline' atau sesuaikan logic di functions

// File yang WAJIB ada di cache saat install pertama kali
const FILES_TO_CACHE = [
  '/',
  '/offline/', // Halaman offline fallback
  '/wp-content/themes/tema-desa-wisata/style.css',
  '/wp-content/themes/tema-desa-wisata/assets/css/main.css',
  '/wp-content/themes/tema-desa-wisata/assets/js/main.js',
  '/wp-content/themes/tema-desa-wisata/assets/images/logo.png' // Sesuaikan path logo
];

// 1. Install Service Worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[ServiceWorker] Pre-caching offline page');
      return cache.addAll(FILES_TO_CACHE);
    })
  );
  self.skipWaiting();
});

// 2. Activate Service Worker (Bersihkan cache lama jika ada update versi)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(
        keyList.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[ServiceWorker] Removing old cache', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// 3. Fetch Event (Strategi: Network First, lalu Cache, lalu Offline Page)
// Cocok untuk Desa Wisata yang kontennya dinamis (booking/stok produk) tapi butuh fallback
self.addEventListener('fetch', (event) => {
  // Abaikan request selain GET (seperti POST saat checkout/login)
  if (event.request.method !== 'GET') {
    return;
  }

  // Abaikan request ke admin area
  if (event.request.url.indexOf('/wp-admin/') !== -1) {
    return;
  }

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Jika berhasil ambil dari network, simpan salinannya ke cache
        // agar nanti bisa dibuka offline
        if (response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseClone);
          });
        }
        return response;
      })
      .catch(() => {
        // Jika Network gagal (Offline), cari di Cache
        return caches.match(event.request).then((response) => {
          if (response) {
            return response;
          }
          // Jika tidak ada di cache (misal halaman baru), tampilkan halaman Offline khusus
          if (event.request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
        });
      })
  );
});