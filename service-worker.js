const CACHE_NAME = 'ejeep-pwa-v1';
const STATIC_ASSETS = [
    '/api/',
    '/api/index.php',
    '/api/assets/style/index.css',
    '/api/assets/style/login.css',
    '/api/assets/style/dashboard.css',
    '/api/assets/script/index/index.js',
    '/api/assets/script/driver/dashboard.js',
    '/api/assets/script/passenger/dashboard.js',
    '/api/assets/script/passenger/live-tracker.js'
];

// Install event - cache static assets
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS);
        }).catch(function (err) {
            console.error('Cache installation failed:', err);
        })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (cacheNames) {
            return Promise.all(
                cacheNames.map(function (cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', function (event) {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then(function (response) {
            // Return cached response if found
            if (response) {
                return response;
            }

            // Otherwise fetch from network
            return fetch(event.request).then(function (networkResponse) {
                // Don't cache non-successful responses
                if (!networkResponse || networkResponse.status !== 200) {
                    return networkResponse;
                }

                // Clone the response before caching
                var responseToCache = networkResponse.clone();

                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(event.request, responseToCache);
                });

                return networkResponse;
            }).catch(function () {
                // Network failed - return offline fallback for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match('/api/index.php');
                }
            });
        })
    );
});
