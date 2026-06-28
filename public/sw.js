const CACHE_VERSION = 'zaptask-v2';
const STATIC_CACHE = `zaptask-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `zaptask-dynamic-${CACHE_VERSION}`;

const STATIC_FILES = [
  '/favicon.ico',
  '/zap_icon.png',
  '/manifest.json',
];

const API_CACHE_PATTERNS = [
  /\/api\/todos/,
  /\/api\/projects/,
  /\/api\/notifications/,
  /\/api\/users/,
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(STATIC_FILES))
      .then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => Promise.all(
        cacheNames
          .filter((cacheName) => cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE)
          .map((cacheName) => caches.delete(cacheName)),
      ))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const { request } = event;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);

  if (url.origin !== location.origin) {
    return;
  }

  // Never cache HTML, Inertia pages, or Vite build assets — always fetch fresh.
  if (shouldBypassCache(request, url)) {
    event.respondWith(fetch(request));
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        if (response && response.status === 200 && response.type === 'basic' && isStaticFile(request)) {
          const responseToCache = response.clone();
          caches.open(STATIC_CACHE)
            .then((cache) => cache.put(request, responseToCache))
            .catch(() => {});
        }

        return response;
      })
      .catch(() => caches.match(request)),
  );
});

function shouldBypassCache(request, url) {
  if (request.mode === 'navigate') {
    return true;
  }

  if (url.pathname === '/' || url.pathname.startsWith('/build/')) {
    return true;
  }

  if (request.headers.get('X-Inertia') === 'true') {
    return true;
  }

  return false;
}

self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

self.addEventListener('push', (event) => {
  const options = {
    body: event.data ? event.data.text() : 'New notification from ZapTask',
    icon: '/zap_icon.png',
    badge: '/zap_icon.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1,
    },
    actions: [
      {
        action: 'explore',
        title: 'View',
        icon: '/zap_icon.png',
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/zap_icon.png',
      },
    ],
  };

  event.waitUntil(
    self.registration.showNotification('ZapTask', options),
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/dashboard'),
    );
  }
});

function isStaticFile(request) {
  const url = new URL(request.url);

  return url.pathname.endsWith('.png')
    || url.pathname.endsWith('.jpg')
    || url.pathname.endsWith('.jpeg')
    || url.pathname.endsWith('.gif')
    || url.pathname.endsWith('.svg')
    || url.pathname.endsWith('.ico');
}

function isApiRequest(request) {
  const url = new URL(request.url);
  return url.pathname.startsWith('/api/');
}

async function doBackgroundSync() {
  try {
    const pendingActions = await getPendingActions();

    for (const action of pendingActions) {
      try {
        await syncAction(action);
        await removePendingAction(action.id);
      } catch (error) {
        // Ignore individual sync failures.
      }
    }
  } catch (error) {
    // Ignore background sync failures.
  }
}

async function getPendingActions() {
  return [];
}

async function syncAction(action) {
  const response = await fetch(action.url, {
    method: action.method,
    headers: action.headers,
    body: action.body,
  });

  if (!response.ok) {
    throw new Error(`Sync failed: ${response.status}`);
  }

  return response;
}

async function removePendingAction(actionId) {
  // Remove action from IndexedDB after successful sync.
}

async function cleanExpiredCache() {
  const cache = await caches.open(DYNAMIC_CACHE);
  const requests = await cache.keys();

  for (const request of requests) {
    const response = await cache.match(request);
    const timestamp = response?.headers.get('sw-cache-timestamp');

    if (timestamp && Date.now() > parseInt(timestamp, 10)) {
      await cache.delete(request);
    }
  }
}

setInterval(cleanExpiredCache, 10 * 60 * 1000);
