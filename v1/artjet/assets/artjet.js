(() => {
    'use strict';

    document.addEventListener('click', event => {
        const back = event.target.closest('.artjet-card-back');
        const trigger = event.target.closest('[data-artjet-flip]')
            || (back && !event.target.closest('a, button') ? back : null);
        if (!trigger) return;

        const card = trigger.closest('[data-artjet-card]');
        if (!card) return;

        const flipped = card.classList.toggle('is-flipped');
        const front = card.querySelector('.artjet-card-front');
        const reverse = card.querySelector('.artjet-card-back');
        if (front) front.setAttribute('aria-expanded', String(flipped));
        if (front) front.inert = flipped;
        if (reverse) reverse.inert = !flipped;

        if (flipped) {
            card.querySelector('.artjet-card-close')?.focus({ preventScroll: true });
        } else {
            front?.focus({ preventScroll: true });
        }
    });
})();
