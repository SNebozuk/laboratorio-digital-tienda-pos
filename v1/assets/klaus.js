(() => {
    'use strict';

    const scriptUrl = document.currentScript?.src || 'assets/klaus.js';
    const assetUrl = name => scriptUrl.replace(/klaus\.js(?:\?.*)?$/, name);
    const bark = new Audio(assetUrl('klaus-bark.mp3'));
    const panting = new Audio(assetUrl('klaus_happy_pantin.mp3'));
    const timers = new WeakMap();
    let lastBarkAt = 0;

    bark.volume = .38;
    panting.volume = .22;

    const play = (audio, restart = true) => {
        if (restart) audio.currentTime = 0;
        audio.play().catch(() => {});
    };

    const barkOnce = () => {
        if (Date.now() - lastBarkAt < 7000) return false;
        lastBarkAt = Date.now();
        play(bark);
        return true;
    };

    const pant = (duration = 1900) => {
        panting.currentTime = 0;
        play(panting, false);
        window.setTimeout(() => { panting.pause(); panting.currentTime = 0; }, duration);
    };

    const pose = (element, name) => {
        const image = element?.querySelector?.('.klaus-image, .pos-klaus-image, .admin-klaus-image');
        if (!image) return;
        image.src = assetUrl(`klaus_${name}.png`);
        image.dataset.klausPose = name;
    };

    const pet = element => {
        if (!element) return;
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
            barkOnce();
            onPet?.(element);
        });
    };

    window.Klaus = Object.freeze({ assetUrl, barkOnce, pant, pet, pose, attach });
})();
