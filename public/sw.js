const CACHE_NAME = 'zaptask-v1';
const STATIC_CACHE = 'zaptask-static-v1';
const DYNAMIC_CACHE = 'zaptask-dynamic-v1';

// Files to cache for offline functionality
const STATIC_FILES = [
  '/',
  '/dashboard',
  '/projects',
  '/settings',
  '/favicon.ico',
  '/zap_icon.png',
  '/manifest.json'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
  /\/api\/todos/,
  /\/api\/projects/,
  /\/api\/notifications/,
  /\/api\/users/
];

// Install event - cache static files
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => {
        console.log('Caching static files...');
        return cache.addAll(STATIC_FILES);
      })
      .then(() => {
        console.log('Static files cached successfully');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('Failed to cache static files:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip external requests
  if (url.origin !== location.origin) {
    return;
  }

  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        // Return cached version if available
        if (cachedResponse) {
          console.log('Serving from cache:', request.url);
          return cachedResponse;
        }

        // Fetch from network
        return fetch(request)
          .then((response) => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            // Clone the response for caching
            const responseToCache = response.clone();

            // Determine cache strategy based on request type
            if (isStaticFile(request)) {
              // Cache static files in static cache
              caches.open(STATIC_CACHE)
                .then((cache) => {
                  cache.put(request, responseToCache);
                });
            } else if (isApiRequest(request)) {
              // Cache API responses in dynamic cache with TTL
              caches.open(DYNAMIC_CACHE)
                .then((cache) => {
                  // Set expiration timestamp
                  const expirationTime = Date.now() + (5 * 60 * 1000); // 5 minutes
                  const headers = new Headers(responseToCache.headers);
                  headers.set('sw-cache-timestamp', expirationTime.toString());
                  
                  // Create a new response with the timestamp header
                  const modifiedResponse = new Response(responseToCache.body, {
                    status: responseToCache.status,
                    statusText: responseToCache.statusText,
                    headers: headers
                  });
                  
                  cache.put(request, modifiedResponse);
                });
            }

            return response;
          })
          .catch((error) => {
            console.error('Fetch failed:', error);
            
            // Return offline page for navigation requests
            if (request.mode === 'navigate') {
              return caches.match('/offline.html') || new Response(
                '<html><body><h1>Offline</h1><p>Please check your internet connection.</p></body></html>',
                { headers: { 'Content-Type': 'text/html' } }
              );
            }
            
            throw error;
          });
      })
  );
});

// Background sync for offline actions
self.addEventListener('sync', (event) => {
  console.log('Background sync triggered:', event.tag);
  
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  console.log('Push notification received');
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from ZapTask',
    icon: '/zap_icon.png',
    badge: '/zap_icon.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View',
        icon: '/zap_icon.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/zap_icon.png'
      }
    ]
  };

  event.waitUntil(
    self.registration.showNotification('ZapTask', options)
  );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
  console.log('Notification clicked:', event.action);
  
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/dashboard')
    );
  }
});

// Helper functions
function isStaticFile(request) {
  const url = new URL(request.url);
  return url.pathname.endsWith('.html') ||
         url.pathname.endsWith('.css') ||
         url.pathname.endsWith('.js') ||
         url.pathname.endsWith('.png') ||
         url.pathname.endsWith('.jpg') ||
         url.pathname.endsWith('.jpeg') ||
         url.pathname.endsWith('.gif') ||
         url.pathname.endsWith('.svg') ||
         url.pathname.endsWith('.ico') ||
         url.pathname === '/';
}

function isApiRequest(request) {
  const url = new URL(request.url);
  return url.pathname.startsWith('/api/');
}

async function doBackgroundSync() {
  try {
    // Get pending offline actions from IndexedDB
    const pendingActions = await getPendingActions();
    
    for (const action of pendingActions) {
      try {
        await syncAction(action);
        await removePendingAction(action.id);
      } catch (error) {
        console.error('Failed to sync action:', action, error);
      }
    }
    
    console.log('Background sync completed');
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

async function getPendingActions() {
  // This would integrate with your IndexedDB implementation
  // For now, return empty array
  return [];
}

async function syncAction(action) {
  // Sync individual action to server
  const response = await fetch(action.url, {
    method: action.method,
    headers: action.headers,
    body: action.body
  });
  
  if (!response.ok) {
    throw new Error(`Sync failed: ${response.status}`);
  }
  
  return response;
}

async function removePendingAction(actionId) {
  // Remove action from IndexedDB after successful sync
  console.log('Removing pending action:', actionId);
}

// Cache management
async function cleanExpiredCache() {
  const cache = await caches.open(DYNAMIC_CACHE);
  const requests = await cache.keys();
  
  for (const request of requests) {
    const response = await cache.match(request);
    const timestamp = response.headers.get('sw-cache-timestamp');
    
    if (timestamp && Date.now() > parseInt(timestamp)) {
      await cache.delete(request);
      console.log('Removed expired cache entry:', request.url);
    }
  }
}

// Clean expired cache every 10 minutes
setInterval(cleanExpiredCache, 10 * 60 * 1000);
