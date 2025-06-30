// ============================================================================
// SERVICE WORKER FOR AUREUS ANGEL ALLIANCE
// ============================================================================
// Provides caching, offline support, and performance optimizations
// ============================================================================

const CACHE_NAME = 'aureus-angels-v1.0.0';
const STATIC_CACHE = 'aureus-static-v1.0.0';
const API_CACHE = 'aureus-api-v1.0.0';
const IMAGE_CACHE = 'aureus-images-v1.0.0';

// Check if we're in development mode
const isDevelopment = location.hostname === 'localhost' || location.hostname === '127.0.0.1';

// Resources to cache immediately
const STATIC_RESOURCES = [
  '/',
  '/index.html',
  '/manifest.json',
  '/favicon.ico',
  '/logo192.png',
  '/logo512.png',
  '/assets/index.css',
  '/assets/index.js',
  '/wallet-protection-v2.js'
];

// API endpoints to cache
const CACHEABLE_APIS = [
  '/api/auth/profile.php',
  '/api/investments/packages.php',
  '/api/leaderboard/gold-diggers-club.php',
  '/api/translations/get.php'
];

// Cache strategies
const CACHE_STRATEGIES = {
  CACHE_FIRST: 'cache-first',
  NETWORK_FIRST: 'network-first',
  STALE_WHILE_REVALIDATE: 'stale-while-revalidate',
  NETWORK_ONLY: 'network-only',
  CACHE_ONLY: 'cache-only'
};

// Install event - cache static resources
self.addEventListener('install', (event) => {
  console.log('Service Worker installing...');
  
  event.waitUntil(
    Promise.all([
      // Cache static resources
      caches.open(STATIC_CACHE).then((cache) => {
        console.log('Caching static resources...');
        return cache.addAll(STATIC_RESOURCES);
      }),
      
      // Skip waiting to activate immediately
      self.skipWaiting()
    ])
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('Service Worker activating...');
  
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME && 
                cacheName !== STATIC_CACHE && 
                cacheName !== API_CACHE && 
                cacheName !== IMAGE_CACHE) {
              console.log('Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      }),
      
      // Take control of all clients
      self.clients.claim()
    ])
  );
});

// Fetch event - handle all network requests
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension and other non-http requests
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // In development mode, skip service worker caching for localhost requests
  if (isDevelopment && (url.hostname === 'localhost' || url.hostname === '127.0.0.1')) {
    // Let development server handle requests directly
    return;
  }

  // Route requests to appropriate cache strategy
  if (isStaticResource(url)) {
    event.respondWith(handleStaticResource(request));
  } else if (isAPIRequest(url)) {
    event.respondWith(handleAPIRequest(request));
  } else if (isImageRequest(url)) {
    event.respondWith(handleImageRequest(request));
  } else {
    event.respondWith(handleGenericRequest(request));
  }
});

// Check if request is for static resource
function isStaticResource(url) {
  const staticExtensions = ['.js', '.css', '.html', '.ico', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2'];
  return staticExtensions.some(ext => url.pathname.endsWith(ext)) || 
         url.pathname === '/' || 
         url.pathname.startsWith('/assets/');
}

// Check if request is for API
function isAPIRequest(url) {
  return url.pathname.startsWith('/api/');
}

// Check if request is for image
function isImageRequest(url) {
  const imageExtensions = ['.png', '.jpg', '.jpeg', '.gif', '.svg', '.webp', '.avif'];
  return imageExtensions.some(ext => url.pathname.endsWith(ext));
}

// Handle static resources with cache-first strategy
async function handleStaticResource(request) {
  try {
    const cache = await caches.open(STATIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      // Return cached version and update in background
      updateCacheInBackground(cache, request);
      return cachedResponse;
    }
    
    // Fetch from network and cache
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Static resource fetch failed:', error);
    return new Response('Offline', { status: 503 });
  }
}

// Handle API requests with network-first strategy
async function handleAPIRequest(request) {
  const url = new URL(request.url);
  
  // Don't cache sensitive endpoints
  if (isSensitiveAPI(url)) {
    return fetch(request);
  }
  
  try {
    // Try network first
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok && isCacheableAPI(url)) {
      // Cache successful responses
      const cache = await caches.open(API_CACHE);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    // Fallback to cache if network fails
    const cache = await caches.open(API_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      console.log('Serving API from cache:', request.url);
      return cachedResponse;
    }
    
    // Return offline response
    return new Response(JSON.stringify({
      success: false,
      error: 'Network unavailable',
      offline: true
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

// Handle image requests with cache-first strategy
async function handleImageRequest(request) {
  try {
    const cache = await caches.open(IMAGE_CACHE);
    const cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Fetch from network and cache
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('Image fetch failed:', error);
    // Return placeholder image
    return new Response('', { status: 404 });
  }
}

// Handle generic requests
async function handleGenericRequest(request) {
  try {
    // In development, don't interfere with requests to localhost:5173
    const url = new URL(request.url);
    if (url.hostname === 'localhost' && (url.port === '5173' || url.port === '3000')) {
      // Let development server handle these requests directly
      return fetch(request);
    }

    return await fetch(request);
  } catch (error) {
    console.error('Generic request failed:', error);

    // Don't return 503 for development server requests
    const url = new URL(request.url);
    if (url.hostname === 'localhost' && (url.port === '5173' || url.port === '3000')) {
      // Let the error propagate for development
      throw error;
    }

    return new Response('Offline', { status: 503 });
  }
}

// Check if API endpoint is sensitive (shouldn't be cached)
function isSensitiveAPI(url) {
  const sensitiveEndpoints = [
    '/api/auth/login.php',
    '/api/auth/logout.php',
    '/api/payments/',
    '/api/admin/',
    '/api/kyc/upload.php'
  ];
  
  return sensitiveEndpoints.some(endpoint => url.pathname.startsWith(endpoint));
}

// Check if API endpoint is cacheable
function isCacheableAPI(url) {
  return CACHEABLE_APIS.some(endpoint => url.pathname.startsWith(endpoint));
}

// Update cache in background
async function updateCacheInBackground(cache, request) {
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
  } catch (error) {
    console.log('Background cache update failed:', error);
  }
}

// Handle background sync
self.addEventListener('sync', (event) => {
  if (event.tag === 'background-sync') {
    event.waitUntil(doBackgroundSync());
  }
});

// Background sync function
async function doBackgroundSync() {
  try {
    // Sync any pending data
    console.log('Performing background sync...');
    
    // You can add specific sync logic here
    // For example, sync offline form submissions, analytics, etc.
    
  } catch (error) {
    console.error('Background sync failed:', error);
  }
}

// Handle push notifications
self.addEventListener('push', (event) => {
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body,
    icon: '/logo192.png',
    badge: '/logo192.png',
    data: data.data || {},
    actions: data.actions || []
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title, options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  
  const data = event.notification.data;
  const action = event.action;
  
  event.waitUntil(
    clients.openWindow(data.url || '/')
  );
});

// Cache management utilities
self.addEventListener('message', (event) => {
  if (event.data && event.data.type) {
    switch (event.data.type) {
      case 'SKIP_WAITING':
        self.skipWaiting();
        break;
        
      case 'CLEAR_CACHE':
        clearAllCaches();
        break;
        
      case 'GET_CACHE_SIZE':
        getCacheSize().then(size => {
          event.ports[0].postMessage({ size });
        });
        break;
        
      default:
        console.log('Unknown message type:', event.data.type);
    }
  }
});

// Clear all caches
async function clearAllCaches() {
  const cacheNames = await caches.keys();
  await Promise.all(
    cacheNames.map(cacheName => caches.delete(cacheName))
  );
  console.log('All caches cleared');
}

// Get total cache size
async function getCacheSize() {
  const cacheNames = await caches.keys();
  let totalSize = 0;
  
  for (const cacheName of cacheNames) {
    const cache = await caches.open(cacheName);
    const requests = await cache.keys();
    
    for (const request of requests) {
      const response = await cache.match(request);
      if (response) {
        const blob = await response.blob();
        totalSize += blob.size;
      }
    }
  }
  
  return totalSize;
}

console.log('Aureus Angels Service Worker loaded');
