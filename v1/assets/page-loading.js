(() => {
    'use strict';
    document.documentElement.classList.add('page-loading');
    const pageLoaded = new Promise(resolve => {
        if (document.readyState === 'complete') resolve();
        else window.addEventListener('load', resolve, { once: true });
    });
    let finishing = false;
    window.LDPageLoader = {
        async finish() {
            if (finishing) return;
            finishing = true;
            try {
                await pageLoaded;
                if (document.fonts) await document.fonts.ready;
                const images = [...document.images].filter(image => {
                    const rect = image.getBoundingClientRect();
                    return rect.width > 0 && rect.height > 0 && rect.bottom > 0
                        && rect.right > 0 && rect.top < window.innerHeight && rect.left < window.innerWidth;
                });
                await Promise.allSettled(images.map(image => {
                    image.loading = 'eager';
                    return image.decode();
                }));
                await new Promise(resolve => requestAnimationFrame(resolve));
            } finally {
                document.documentElement.classList.remove('page-loading');
                document.getElementById('page-loading')?.remove();
            }
        },
    };
    // Un error de un recurso no debe dejar la pantalla bloqueada.
    window.addEventListener('error', () => window.LDPageLoader.finish(), { once: true });
})();
