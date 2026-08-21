(() => {
    'use strict';

    const timers = new WeakMap();

    const pet = element => {
        if (!element || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        window.clearTimeout(timers.get(element));
        element.classList.remove('is-petted');
        void element.offsetWidth;
        element.classList.add('is-petted');
        timers.set(element, window.setTimeout(() => element.classList.remove('is-petted'), 950));
    };

    const attach = (root, selector, onPet) => {
        root.addEventListener('pointerdown', event => {
            const element = event.target.closest(selector);
            if (!element) return;
            pet(element);
            onPet?.(element);
        });
    };

    window.Klaus = Object.freeze({ pet, attach });
})();
