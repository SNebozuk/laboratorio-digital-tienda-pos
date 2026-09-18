self.addEventListener('activate', event => {
    event.waitUntil(Promise.all([
        caches.keys().then(keys => Promise.all(
            keys.filter(key => key.startsWith('laboratorio-digital-admin-')).map(key => caches.delete(key))
        )),
        self.registration.unregister(),
    ]));
});
self.skipWaiting();
