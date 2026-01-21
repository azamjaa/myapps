/**
 * Service Worker for MyApps KEDA PWA
 * Strategy: Network-First for HTML, Cache-First for Assets
 * 
 * @version 3.0 (Sidebar Layout Fix)
 */

const CACHE_NAME = 'myapps-keda-v3.0-sidebar'; // Updated version to force refresh

// Static assets (CSS, Images, Fonts) - Cache First
const STATIC_ASSETS = [
    '/myapps/image/keda.png',
    '/myapps/image/background.jpg',
    '/myapps/image/mawar.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    // 'https://cdn.tailwindcss.com', // Removed - not recommended for production
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing v3.0...');
    self.skipWaiting(); // Force activate new SW immediately
    
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating v3.0...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[ServiceWorker] Removing old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim(); // Take control of all clients immediately
});

// Fetch event
self.addEventListener('fetch', (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) return;
    
    // Skip POST requests
    if (event.request.method !== 'GET') return;

    const requestUrl = new URL(event.request.url);

    // STRATEGY 1: Network First (For HTML pages - ensure latest layout)
    // Checks network -> if fails, check cache -> if fails, show nothing (no offline fallback)
    if (requestUrl.pathname.endsWith('.php') || requestUrl.pathname.endsWith('/')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Update cache with new version
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // If offline, do not serve anything
                    return Promise.reject('Offline, no fallback');
                })
        );
        return;
    }

    // STRATEGY 2: Cache First (For Static Assets - Images, CSS, JS)
    // Checks cache -> if fails, check network
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request).then((fetchResponse) => {
                if (fetchResponse && fetchResponse.status === 200) {
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, fetchResponse.clone());
                    });
                }
                return fetchResponse;
            });
        })
    );
});
