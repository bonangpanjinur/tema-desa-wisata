const CACHE_NAME = 'desa-wisata-cache-v1';
const OFFLINE_URL = 'offline.php'; // Pastikan file ini bisa diakses langsung via URL

// File statis yang wajib di-cache (App Shell)
const urlsToCache = [
    './',
    'style.css',
    'assets/css/main.css',
    'assets/js/main.js',
    OFFLINE_URL
];

// 1. Install Event: Cache file statis
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// 2. Activate Event: Hapus cache lama jika ada update versi
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// 3. Fetch Event: Strategi Network First, Fallback to Cache, Fallback to Offline Page
self.addEventListener('fetch', function(event) {
    
    // Jangan cache request ke wp-admin, wp-login, atau metode POST (checkout/login)
    if (event.request.url.indexOf('/wp-admin') !== -1 || 
        event.request.url.indexOf('/wp-login.php') !== -1 || 
        event.request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                // Jika ada koneksi internet, kembalikan respon asli dan simpan salinannya di cache
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }
                var responseToCache = response.clone();
                caches.open(CACHE_NAME)
                    .then(function(cache) {
                        cache.put(event.request, responseToCache);
                    });
                return response;
            })
            .catch(function() {
                // Jika offline, coba cari di cache
                return caches.match(event.request)
                    .then(function(response) {
                        if (response) {
                            return response;
                        }
                        // Jika tidak ada di cache (halaman baru), tampilkan halaman offline
                        if (event.request.mode === 'navigate') {
                            return caches.match(OFFLINE_URL);
                        }
                    });
            })
    );
});
