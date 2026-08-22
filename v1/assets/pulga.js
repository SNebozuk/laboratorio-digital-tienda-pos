(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('app-data')?.textContent || '{}');
    const settings = app.pulga || {};
    const enabled = ['1', 'true', 'on'].includes(String(settings.enabled || '1'));
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const animationsEnabled = ['1', 'true', 'on'].includes(String(settings.animations_enabled || '1')) && !reducedMotion.matches;
    const baseFrequency = Math.min(120, Math.max(45, Number(settings.frequency_seconds) || 75)) * 1000;
    let timer = 0;
    let active = false;
    let host;

    const markup = '<div class="pulga" role="img" aria-label="Pulga, la gata de Laboratorio Digital"><svg viewBox="0 0 120 80" aria-hidden="true"><g class="pulga-cat"><path class="pulga-tail" d="M91 56c21-10 23 9 8 13-8 2-11-4-4-7"/><path class="pulga-body" d="M36 57c4-17 17-25 38-22 16 2 26 11 27 24l-8 9H40z"/><path class="pulga-head" d="M20 28l4-18 13 12c11-5 24-1 30 9l13-11 1 19c7 18-6 31-26 30-23-1-40-18-35-41z"/><path class="pulga-ear" d="M25 27l2-10 8 8M68 30l9-7v11"/><path class="pulga-chest" d="M47 48c6-4 14-4 20 1l-3 14H49z"/><circle class="pulga-eye" cx="42" cy="40" r="2.8"/><circle class="pulga-eye" cx="62" cy="40" r="2.8"/><path class="pulga-nose" d="M50 48h5l-3 3z"/><path class="pulga-mouth" d="M52 51q-4 4-7 0m7 0q4 4 8 0"/><path class="pulga-whiskers" d="M49 49l-17-4m17 8l-18 3m25-7l16-5m-16 9l17 3"/><path class="pulga-leg" d="M49 64v7m27-7v7"/></g></svg></div>';

    const randomDelay = () => Math.round(baseFrequency * (.6 + Math.random() * 1.0));
    const isObstructed = (x, y) => {
        const element = document.elementFromPoint(x, y);
        return element && element.closest('button, a, input, textarea, select, .product-card, .product-modal, .modal, .toast, .order-panel, .store-header, .search-wrap');
    };
    const pickSpot = () => {
        const spots = [
            { side: 'left', x: 34, y: innerHeight * .72 },
            { side: 'right', x: innerWidth - 34, y: innerHeight * .70 },
            { side: 'left', x: 34, y: innerHeight * .38 },
            { side: 'right', x: innerWidth - 34, y: innerHeight * .40 },
        ].filter(spot => !isObstructed(spot.x, spot.y));
        return spots[Math.floor(Math.random() * spots.length)] || null;
    };
    const schedule = () => {
        clearTimeout(timer);
        if (enabled && !document.hidden) timer = setTimeout(show, randomDelay());
    };
    const hide = (escaping = false) => {
        if (!host || !active) return;
        active = false;
        host.classList.toggle('is-escaping', escaping);
        const delay = escaping && animationsEnabled ? 420 : 160;
        setTimeout(() => { host?.remove(); host = undefined; schedule(); }, delay);
    };
    const show = () => {
        if (!enabled || document.hidden || document.querySelector('.modal.is-open, .product-modal:not([hidden])')) return schedule();
        const spot = pickSpot();
        if (!spot) return schedule();
        active = true;
        host = document.createElement('div');
        host.className = `pulga-host pulga-from-${spot.side}${animationsEnabled ? '' : ' pulga-still'}`;
        host.style.top = `${Math.round(spot.y - 28)}px`;
        host.innerHTML = markup;
        document.body.append(host);
        host.addEventListener('pointerdown', event => { event.preventDefault(); hide(true); }, { once: true });
        const fleeIfNear = event => {
            if (!active || event.pointerType === 'touch') return;
            const rect = host.getBoundingClientRect();
            const dx = Math.max(rect.left - event.clientX, 0, event.clientX - rect.right);
            const dy = Math.max(rect.top - event.clientY, 0, event.clientY - rect.bottom);
            if (Math.hypot(dx, dy) < 68) hide(true);
        };
        window.addEventListener('pointermove', fleeIfNear, { passive: true });
        setTimeout(() => window.removeEventListener('pointermove', fleeIfNear), 8000);
        setTimeout(() => hide(false), animationsEnabled ? 5200 : 3000);
    };

    if (enabled) {
        document.addEventListener('visibilitychange', () => { if (document.hidden) hide(false); else schedule(); });
        window.addEventListener('pagehide', () => clearTimeout(timer));
        schedule();
    }
})();
