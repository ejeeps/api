// Simple Service Worker for PWA Installation Only
// No caching or offline functionality - just enables PWA installation

const SW_VERSION = 'e-jeep-install-v1.0.0';

// Install event - just activate immediately
self.addEventListener('install', event => {
  console.log('Service Worker: Installing for PWA installation support');
  self.skipWaiting();
});

// Activate event - claim clients immediately
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating for PWA installation support');
  event.waitUntil(self.clients.claim());
});

// Fetch event - just pass through to network (no caching)
self.addEventListener('fetch', event => {
  // Simply pass all requests to the network
  // No caching, no offline functionality
  return;
});

// Push notification handling (for future use)
self.addEventListener('push', event => {
  console.log('Service Worker: Push received', event);
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from E-JEEP',
    icon: '/api/assets/icons/icon-192x192.png',
    badge: '/api/assets/icons/icon-72x72.png',
    vibrate: [200, 100, 200],
    data: {
      url: '/api/'
    }
  };

  event.waitUntil(
    self.registration.showNotification('E-JEEP', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked', event);
  
  event.notification.close();
  
  event.waitUntil(
    clients.openWindow('/api/')
  );
});

// Message handling from main thread
self.addEventListener('message', event => {
  console.log('Service Worker: Message received', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: SW_VERSION });
  }
});