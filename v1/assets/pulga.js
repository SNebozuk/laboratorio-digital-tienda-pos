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

    const markup = '<div class="pulga" role="img" aria-label="Pulga, la gata de Laboratorio Digital"><svg viewBox="0 0 120 80" aria-hidden="true"><g class="pulga-cat"><path class="pulga-tail" d="M88 54c22-13 27 7 14 14-8 4-14-2-7-7"/><ellipse class="pulga-body" cx="69" cy="54" rx="31" ry="18"/><path class="pulga-head" d="M18 42 21 14l16 13c8-4 19-4 28 0l16-13 3 28c4 23-12 32-33 32S14 65 18 42z"/><path class="pulga-ear" d="m24 25 1-5 9 8m40 0 9-8 1 6"/><path class="pulga-stripes" d="m39 27 5 8m7-10 2 10m10-10-2 10m10-8-5 8M70 48l-7 8m17-5-8 8"/><ellipse class="pulga-eye-white" cx="38" cy="47" rx="8" ry="10"/><ellipse class="pulga-eye-white" cx="63" cy="47" rx="8" ry="10"/><ellipse class="pulga-eye" cx="39" cy="49" rx="4.7" ry="6.5"/><ellipse class="pulga-eye" cx="62" cy="49" rx="4.7" ry="6.5"/><circle class="pulga-eye-shine" cx="40" cy="46" r="1.7"/><circle class="pulga-eye-shine" cx="63" cy="46" r="1.7"/><ellipse class="pulga-blush" cx="28" cy="58" rx="5" ry="2.4"/><ellipse class="pulga-blush" cx="73" cy="58" rx="5" ry="2.4"/><path class="pulga-nose" d="M49 56h5l-2.5 3z"/><path class="pulga-mouth" d="M51.5 59q-4 4-7 0m7 0q4 4 7 0"/><path class="pulga-whiskers" d="m46 57-15-4m15 8-15 2m12-9-11-7m18 10 15-4m-15 8 15 2m-12-9 11-7"/><path class="pulga-leg" d="M57 66v6m19-6v6"/></g></svg></div>';

    const randomDelay = () => Math.round(baseFrequency * (.6 + Math.random() * 1.0));
    const isObstructed = (x, y) => {
        const element = document.elementFromPoint(x, y);
        return element && element.closest('button, a, input, textarea, select, .product-card, .product-modal, .modal, .toast, .order-panel, .store-header, .search-wrap');
    };
    const pickSpot = (side = '') => {
        const spots = [
            { side: 'left', x: 34, y: innerHeight * .72 },
            { side: 'right', x: innerWidth - 34, y: innerHeight * .70 },
            { side: 'left', x: 34, y: innerHeight * .38 },
            { side: 'right', x: innerWidth - 34, y: innerHeight * .40 },
        ].filter(spot => (!side || spot.side === side) && !isObstructed(spot.x, spot.y));
        return spots[Math.floor(Math.random() * spots.length)] || null;
    };
    const forcedSpot = side => ({ side, y: innerHeight * .74 });
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
    const show = (side = '', force = false, walking = false) => {
        if (!enabled || document.hidden || document.querySelector('.modal.is-open, .product-modal:not([hidden])')) return schedule();
        const spot = pickSpot(side) || (force && side ? forcedSpot(side) : null);
        if (!spot) return schedule();
        active = true;
        host = document.createElement('div');
        host.className = `pulga-host pulga-from-${spot.side}${walking ? ' pulga-walking' : ''}${animationsEnabled ? '' : ' pulga-still'}`;
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
        setTimeout(() => hide(false), walking ? 3900 : (animationsEnabled ? 5200 : 3000));
    };

    if (enabled) {
        document.addEventListener('visibilitychange', () => { if (document.hidden) hide(false); else schedule(); });
        window.addEventListener('pagehide', () => clearTimeout(timer));
        window.addEventListener('keydown', event => {
            if (!event.shiftKey || event.ctrlKey || event.altKey || event.metaKey || !['Digit1', 'Digit2', 'Digit3', 'Digit4', 'Digit0'].includes(event.code)) return;
            if (event.target.matches('input, textarea, select, [contenteditable="true"]')) return;
            event.preventDefault();
            const wasActive = active;
            if (event.code === 'Digit0') return hide(true);
            const side = ['Digit1', 'Digit3'].includes(event.code) ? 'left' : 'right';
            const walking = ['Digit3', 'Digit4'].includes(event.code);
            if (wasActive) hide(false);
            setTimeout(() => show(side, true, walking), wasActive ? 170 : 0);
        });
        schedule();
    }
})();
