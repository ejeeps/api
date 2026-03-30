const CACHE_NAME = 'ejeep-pwa-v3';
// Only pre-cache static assets — never cache PHP/HTML entry points (session-dependent).
const STATIC_ASSETS = [
    '/api/assets/style/index.css',
    '/api/assets/style/login.css',
    '/api/assets/style/dashboard.css',
    '/api/assets/script/index/index.js',
    '/api/assets/script/driver/dashboard.js',
    '/api/assets/script/passenger/dashboard.js',
    '/api/assets/script/passenger/ai-assistant.js'
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

function shouldCacheAsset(url) {
    return url.pathname.indexOf('/api/assets/') !== -1;
}

// Fetch event
self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET') {
        return;
    }

    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    var url = new URL(event.request.url);

    // Document navigations must always hit the network. Cache-first here returned a stale
    // logged-out home page after login; the session cookie only applied on the next request.
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(function () {
                return caches.match('/api/index.php');
            })
        );
        return;
    }

    // Static assets: cache first, then network
    event.respondWith(
        caches.match(event.request).then(function (response) {
            if (response) {
                return response;
            }

            return fetch(event.request).then(function (networkResponse) {
                if (!networkResponse || networkResponse.status !== 200) {
                    return networkResponse;
                }

                if (shouldCacheAsset(url)) {
                    var responseToCache = networkResponse.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(event.request, responseToCache);
                    });
                }

                return networkResponse;
            });
        })
    );
});
