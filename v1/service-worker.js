self.addEventListener('activate', event => {
    event.waitUntil(Promise.all([
        caches.delete('laboratorio-digital-pwa-v2'),
        self.registration.unregister(),
    ]));
});
self.skipWaiting();
