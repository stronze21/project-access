const CACHE_NAME = 'access-portal-static-v5';
const OFFLINE_URL = '/resident-portal/offline.html';
const APP_SHELL = [
    OFFLINE_URL,
    '/resident-portal/app.css',
    '/resident-portal/app.js',
    '/resident-portal/device.js',
    '/resident-portal/images/appicon.png',
    '/resident-portal/images/access-logo.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(APP_SHELL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('access-portal-static-') && key !== CACHE_NAME)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    const cacheableDestinations = ['style', 'script', 'image', 'font'];

    if (request.method !== 'GET'
        || url.origin !== self.location.origin
        || !url.pathname.startsWith('/resident-portal/')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (!cacheableDestinations.includes(request.destination)) return;

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response.ok) {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
            }
            return response;
        }))
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
    if (event.data?.type === 'CLEAR_RESIDENT_CACHES') {
        event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))));
    }
});
