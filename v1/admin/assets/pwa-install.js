(() => {
    'use strict';
    if (!('serviceWorker' in navigator)) return;

    const installButton = document.getElementById('admin-pwa-install-button');
    let deferredPrompt = null;
    navigator.serviceWorker.register('./service-worker.js', { updateViaCache: 'none' }).catch(() => {});

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredPrompt = event;
        if (installButton) installButton.hidden = false;
    });
    installButton?.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        installButton.hidden = true;
    });
    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        if (installButton) installButton.hidden = true;
    });
})();
