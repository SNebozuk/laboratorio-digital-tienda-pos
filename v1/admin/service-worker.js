const CACHE_NAME = 'laboratorio-digital-admin-v1';
const STATIC_ASSETS = [
    './assets/admin.css', './assets/admin.js', './assets/pwa-install.js',
    '../assets/app.css', '../assets/search-normalizer.js',
    '../assets/pwa-icon-192.png', '../assets/pwa-icon-512.png',
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
    self.skipWaiting();
});
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(
        keys.filter(key => key.startsWith('laboratorio-digital-admin-') && key !== CACHE_NAME).map(key => caches.delete(key))
    )));
    self.clients.claim();
});
self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET' || event.request.mode === 'navigate') return;
    if (new URL(event.request.url).origin !== self.location.origin) return;
    event.respondWith(fetch(event.request).then(response => {
        if (response.ok && ['script', 'style', 'image'].includes(event.request.destination)) {
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, response.clone()));
        }
        return response;
    }).catch(() => caches.match(event.request, { ignoreSearch: true })));
});
