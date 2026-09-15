(() => {
    'use strict';

    const prompt = document.getElementById('pwa-install-prompt');
    const confirm = document.getElementById('pwa-install-confirm');
    const later = document.getElementById('pwa-install-later');
    const iosHelp = document.getElementById('pwa-install-ios-help');
    if (!prompt || !confirm || !later || !('serviceWorker' in navigator)) return;

    const DISMISS_KEY = 'laboratorio-digital:pwa-install-dismissed-until';
    let deferredPrompt = null;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isSafari = isIos && /safari/i.test(navigator.userAgent) && !/crios|fxios|edgios/i.test(navigator.userAgent);
    const isDismissed = () => Number(localStorage.getItem(DISMISS_KEY) || 0) > Date.now();
    const close = (defer = false) => {
        prompt.hidden = true;
        if (defer) localStorage.setItem(DISMISS_KEY, String(Date.now() + (7 * 24 * 60 * 60 * 1000)));
    };
    const show = () => {
        if (!isStandalone && !isDismissed()) prompt.hidden = false;
    };

    navigator.serviceWorker.register('./service-worker.js').catch(() => {
        // La tienda funciona normalmente si el navegador no puede registrar la PWA.
    });

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredPrompt = event;
        window.setTimeout(show, 900);
    });

    if (isSafari && !isStandalone) {
        iosHelp.hidden = false;
        confirm.textContent = 'VER CÓMO INSTALAR';
        window.setTimeout(show, 1200);
    }

    later.addEventListener('click', () => close(true));
    confirm.addEventListener('click', async () => {
        if (isSafari) {
            iosHelp.hidden = false;
            return;
        }
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const choice = await deferredPrompt.userChoice;
        deferredPrompt = null;
        close(choice.outcome !== 'accepted');
    });
    window.addEventListener('appinstalled', () => close());
})();
