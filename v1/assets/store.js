(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('app-data').textContent);
    const design = app.design && typeof app.design === 'object' ? app.design : {};
    const designColors = {
        color_background: '--bg',
        color_surface: '--panel',
        color_secondary: '--panel-2',
        color_text: '--text',
        color_accent: '--accent',
    };
    Object.entries(designColors).forEach(([key, variable]) => {
        const color = String(design[key] || '');
        if (/^#[0-9a-f]{6}$/i.test(color)) document.documentElement.style.setProperty(variable, color);
    });
    const cartMaintenanceEnabled = app.cart_maintenance_enabled === true || String(app.cart_maintenance_enabled) === '1';
    let products = Array.isArray(app.products) ? app.products : [];
    let categoryTree = Array.isArray(app.categories) ? app.categories : [];
    let tutorials = Array.isArray(app.tutorials) ? app.tutorials : [];
    let featuredProductIds = Array.isArray(app.featured_product_ids)
        ? app.featured_product_ids.map(Number).filter(Number.isFinite)
        : [];
    const initialUrl = new URL(window.location.href);
    const linkedProductId = (() => {
        const value = Number(initialUrl.searchParams.get('producto'));
        return Number.isFinite(value) && value > 0 ? value : null;
    })();
    const initialSearchQuery = String(initialUrl.searchParams.get('buscar') || '').trim();
    const state = {
        category: '',
        showAll: true,
        query: initialSearchQuery,
        searchActive: initialSearchQuery.length >= 3,
        openedProductId: linkedProductId,
        remoteSearchIds: new Set(),
        cart: new Map(),
        order: null,
        changedAvailability: new Set(),
        reducedAvailability: new Set(),
    };

    let codeSearchController = null;
    let codeSearchRequest = 0;
    let codeSearchTimer = null;
    let catalogLoaded = products.length > 0;
    let catalogLoading = null;
    let cartRestored = false;
    const collapsedCategories = new Set();
    const CART_STORAGE_KEY = 'laboratorio-digital:public-cart:v1';
    const KLAUS_REWARD_SHOWN_STORAGE_KEY = 'laboratorio-digital:klaus-reward-shown:v1';
    const KLAUS_REWARD_DRAWN_STORAGE_KEY = 'laboratorio-digital:klaus-reward-drawn:v1';
    const KLAUS_AWAKE_STORAGE_KEY = 'laboratorio-digital:klaus-awake:v1';
    const CUSTOMER_STORAGE_KEY = 'laboratorio-digital:checkout-customer:v1';
    const ORDER_COMPLETE_STORAGE_KEY = 'laboratorio-digital:completed-order:v1';
    const CART_HISTORY_KEY = 'laboratorio-digital:mobile-cart-open';
    const PRODUCT_VIEW_STORAGE_KEY = 'laboratorio-digital:product-view:v2';
    const PRODUCT_VIEWS = new Set(['list', 'catalog', 'minimal']);
    let alwaysUseProductView = false;
    let productView = (() => {
        try {
            const saved = window.localStorage.getItem(PRODUCT_VIEW_STORAGE_KEY);
            alwaysUseProductView = PRODUCT_VIEWS.has(saved);
            return alwaysUseProductView ? saved : 'list';
        } catch (_) {
            return 'list';
        }
    })();
    const returnedFromQuote = initialUrl.searchParams.has('volver-del-cotizador');
    if (returnedFromQuote) {
        initialUrl.searchParams.delete('volver-del-cotizador');
        window.history.replaceState(window.history.state, '', initialUrl.href);
    }
    const isMobileStorefront = () => window.matchMedia('(max-width: 900px)').matches;

    const elements = {
        categories: document.getElementById('category-list'),
        categoryPanel: document.querySelector('.category-panel'),
        categoryMenu: document.getElementById('catalog-menu-button'),
        categoryBackdrop: document.getElementById('category-backdrop'),
        categoryToggle: document.getElementById('category-toggle'),
        categoryBreadcrumb: document.getElementById('category-breadcrumb'),
        search: document.getElementById('product-search'),
        closeSearch: document.getElementById('search-close'),
        results: document.getElementById('catalog-results'),
        productViewSwitcher: document.getElementById('product-view-switcher'),
        cartLines: document.getElementById('cart-lines'),
        cartSummaryMeta: document.getElementById('cart-summary-meta'),
        cartTotal: document.getElementById('cart-total'),
        cartSubtotal: document.getElementById('cart-subtotal'),
        cartDiscount: document.getElementById('cart-discount'),
        cartRewards: document.getElementById('cart-rewards'),
        mobileKlausHost: document.getElementById('mobile-klaus-host'),
        checkout: document.getElementById('checkout-button'),
        mobileCart: document.getElementById('cart-mobile'),
        mobileCartCount: document.getElementById('cart-mobile-count'),
        orderPanel: document.getElementById('order-panel'),
        closeMobileCart: document.getElementById('close-cart-mobile'),
        modal: document.getElementById('modal'),
        modalContent: document.getElementById('modal-content'),
        toast: document.getElementById('toast'),
    };

    const money = cents => new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        maximumFractionDigits: 0,
    }).format(cents / 100);
    const rewards = app.rewards || {};
    const rewardOn = key => ['1', 'true', 'on', true].includes(rewards[key]);
    document.body.classList.toggle('reward-cart-animation', rewardOn('reward_cart_animation_enabled') && rewardOn('reward_microinteractions_enabled'));
    const rewardText = (key, values = {}) => String(rewards[key] || '').replace(/{{(faltan|porcentaje)}}/g, (_, name) => values[name] ?? '');
    let surpriseUnlocked = false;
    let surpriseChecked = false;
    let klausDiscountUnlocked = Boolean(app.klaus_discount_unlocked);
    let klausRewardChecked = Boolean(app.klaus_reward_checked) || (() => {
        try { return window.localStorage.getItem(KLAUS_REWARD_DRAWN_STORAGE_KEY) === '1'; } catch (_) { return false; }
    })();
    let klausDiscountAcknowledged = false;
    let klausRewardShown = window.sessionStorage.getItem(KLAUS_REWARD_SHOWN_STORAGE_KEY) === '1';
    let klausAwake = window.sessionStorage.getItem(KLAUS_AWAKE_STORAGE_KEY) === '1';
    let klausDiscountPending = false;
    let klausReaction = '';
    let klausTimer = null;
    const KLAUS_NORMAL_POSES = ['browsing_play_ball', 'browsing_play_bow', 'browsing_roll_over'];
    let klausPose = 'browsing_play_bow';
    let klausPoseTimer = 0;
    let klausSleepTimer = 0;

    function setKlausPose(pose, duration = 0) {
        klausPose = pose;
        window.clearTimeout(klausPoseTimer);
        renderCart();
        if (duration) {
            klausPoseTimer = window.setTimeout(() => setKlausPose(KLAUS_NORMAL_POSES[Math.floor(Math.random() * KLAUS_NORMAL_POSES.length)]), duration);
        }
    }

    function keepKlausAwake() {
        window.clearTimeout(klausSleepTimer);
        if (klausPose === 'idle_sleeping') setKlausPose('browsing_play_bow');
        klausSleepTimer = window.setTimeout(() => setKlausPose('idle_sleeping'), 25000);
    }
    function cartDiscount(subtotal, units) {
        const quantityPercent = rewardOn('reward_quantity_enabled') && units >= Number(rewards.reward_quantity_units || 20) ? Number(rewards.reward_quantity_percent || 3) : 0;
        const surprisePercent = surpriseUnlocked && rewardOn('reward_surprise_enabled') ? Number(rewards.reward_surprise_percent || 5) : 0;
        const basePercent = Math.max(quantityPercent, surprisePercent);
        const percent = basePercent + (klausDiscountUnlocked ? 2 : 0);
        const cents = Math.round(subtotal * percent / 100);
        const klausCents = klausDiscountUnlocked ? Math.round(subtotal * 2 / 100) : 0;
        const baseCents = Math.max(0, cents - klausCents);
        const breakdown = [];
        if (klausCents) breakdown.push({ type: 'klaus', label: 'Regalo de Klaus (2%)', cents: klausCents });
        if (baseCents && quantityPercent >= surprisePercent) breakdown.push({ type: 'quantity', label: `Por completar ${Number(rewards.reward_quantity_units || 20)} productos (${quantityPercent}%)`, cents: baseCents });
        if (baseCents && surprisePercent > quantityPercent) breakdown.push({ type: 'surprise', label: `Sorpresa del carrito (${surprisePercent}%)`, cents: baseCents });
        return { percent, type: klausDiscountUnlocked ? 'klaus' : (surprisePercent >= quantityPercent && surprisePercent ? 'surprise' : (quantityPercent ? 'quantity' : '')), cents, quantityPercent, breakdown };
    }

    function klausGiftIconMarkup() {
        return '<svg class="klaus-gift-icon" aria-hidden="true" viewBox="0 0 42 32"><path d="M8 17c0-9 7-14 14-14s13 5 13 14l-3 10H11z" fill="#d4a15f" stroke="#68411d" stroke-width="1.7" stroke-linejoin="round"/><path d="M12 10C3 10 4 20 10 22l4-11zM29 10c8-5 10 5 5 11l-5-8z" fill="#ad743b" stroke="#68411d" stroke-width="1.7" stroke-linejoin="round"/><ellipse cx="21" cy="21" rx="7" ry="5" fill="#efcc98"/><circle cx="17" cy="16" r="1.3" fill="#322015"/><circle cx="25" cy="16" r="1.3" fill="#322015"/><path d="M19 20h4l-2 2z" fill="#322015"/></svg>';
    }

    function discountSummaryMarkup(discount) {
        if (discount.cents) {
            const lines = discount.breakdown.map(item => `${item.type === 'klaus' ? klausGiftIconMarkup() : ''}${item.label}: -${money(item.cents)}`);
            return `${lines.join('<br>')}<br><b class="discount-total">Te ganaste ${money(discount.cents)} en total de descuento</b>`;
        }
        return klausDiscountUnlocked
            ? `${klausGiftIconMarkup()}Regalo de Klaus (2%): se aplicará al agregar productos`
            : 'Descuento: —';
    }

    function klausMarkup(units, message = '') {
        if (!rewardOn('reward_klaus_enabled')) return '';
        const mood = units >= Number(rewards.reward_quantity_units || 20) ? 'is-thrilled' : (units > 0 || klausAwake) ? 'is-happy' : 'is-sleeping';
        return `<section class="klaus ${mood} ${klausReaction}" aria-label="Klaus, la mascota de Laboratorio Digital"><svg viewBox="0 0 180 120" role="img" aria-hidden="true"><g class="klaus-dog"><path class="klaus-tail" d="M134 78c21-17 32-4 23 10-4 6-10 9-16 9"/><path class="klaus-body" d="M54 79c1-23 18-37 46-37 28 0 45 14 46 37l-9 23H57z"/><path class="klaus-chest" d="M84 55c10 2 19 11 21 24l-7 23H73l5-23c1-11 2-19 6-24z"/><path class="klaus-head" d="M36 31c10-19 39-22 53-4 10 12 7 34-6 45-15 13-40 8-50-8-7-11-4-24 3-33z"/><path class="klaus-ear klaus-ear-left" d="M40 33C20 34 18 52 29 68c6 8 17 5 20-6l4-23z"/><path class="klaus-ear klaus-ear-right" d="M79 34c18-9 27 8 21 24-4 11-14 14-20 5l-5-17z"/><path class="klaus-brow" d="M45 46c4-3 8-3 11-1M68 44c4-3 8-2 10 1"/><ellipse class="klaus-muzzle" cx="61" cy="63" rx="18" ry="13"/><circle class="klaus-eye" cx="51" cy="51" r="3"/><circle class="klaus-eye" cx="74" cy="50" r="3"/><path class="klaus-happy-eye" d="M46 51q5 6 10 0M69 50q5 6 10 0"/><path class="klaus-nose" d="M57 59q5-4 10 0l-5 5z"/><path class="klaus-smile" d="M61 65c3 5 9 6 13 0"/><path class="klaus-tongue" d="M64 67c1 10 9 11 11 2v-3"/><path class="klaus-leg" d="M67 96v13M122 96v13"/><path class="klaus-collar" d="M45 75c11 8 29 8 41-1"/><circle class="klaus-tag" cx="65" cy="80" r="3"/></g></svg><span class="klaus-pet-effects" aria-hidden="true"><b>♥</b><b>✿</b><b>♥</b><b>✿</b><b>♥</b><b>✿</b><b>♥</b><b>✿</b></span><span class="klaus-pet-prompt" aria-hidden="true">¿Me hacés unos mimitos?</span>${units === 0 && !klausAwake ? '<b class="klaus-zzz" aria-hidden="true">Zzz</b>' : ''}<div><strong>KLAUS</strong>${message ? `<span>${escapeHtml(message)}</span>` : `<span>${units === 0 && !klausAwake ? 'Durmiendo hasta tu primer producto' : 'Tu compañero de carrito'}</span>`}</div></section>`;
    }

    function protectKlausFromDarkReader(root) {
        if (!root) return;
        root.querySelectorAll('.klaus').forEach((klaus) => {
            const prompt = klaus.querySelector('.klaus-pet-prompt');
            if (prompt) prompt.innerHTML = 'Hola. soy Klaus<br>me haces mimitos?';
            const currentImage = klaus.querySelector('.klaus-image');
            if (currentImage) {
                currentImage.src = `${app.asset_url}/klaus_${klausPose}.png`;
                return;
            }
            const image = document.createElement('img');
            image.className = 'klaus-image';
            image.src = `${app.asset_url}/klaus_${klausPose}.png`;
            image.alt = '';
            image.setAttribute('aria-hidden', 'true');
            klaus.insertBefore(image, klaus.firstChild);
        });
    }

    function reactKlaus(kind = '') {
        if (!rewardOn('reward_klaus_enabled') || !rewardOn('reward_klaus_animations_enabled')) return false;
        klausReaction = kind || 'is-reacting';
        if (kind.includes('petted')) setKlausPose('touch_bark_hearts');
        else if (kind.includes('celebrating')) setKlausPose('progress_celebrate_20_products', 2200);
        else if (kind) setKlausPose('cart_add_jump', 1000);
        window.clearTimeout(klausTimer);
        klausTimer = window.setTimeout(() => { klausReaction = ''; renderCart(); }, 1400);
        renderCart();
        return true;
    }

    function prepareKlausClink() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return () => {};
        const context = new AudioContext();
        context.resume().catch(() => {});
        return () => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.type = 'triangle';
            oscillator.frequency.setValueAtTime(1047, context.currentTime);
            oscillator.frequency.setValueAtTime(1319, context.currentTime + .12);
            oscillator.frequency.setValueAtTime(1568, context.currentTime + .24);
            gain.gain.setValueAtTime(.001, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(.18, context.currentTime + .015);
            gain.gain.exponentialRampToValueAtTime(.001, context.currentTime + .48);
            oscillator.connect(gain).connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + .5);
        };
    }

    function showKlausRewardDialog() {
        const dialog = document.createElement('section');
        dialog.className = 'klaus-reward-dialog';
        dialog.setAttribute('role', 'dialog');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-label', 'Regalo de Klaus');
        dialog.innerHTML = '<div><span aria-hidden="true">🐾</span><strong>¡Klaus te hizo un regalo!</strong><p><b>Klaus te regala un 2% de descuento en toda tu compra.</b><br>Es acumulable con otras promociones.</p><button type="button" aria-label="Gracias, Klaus">✓ <small>GRACIAS, KLAUS</small></button></div>';
        const close = () => {
            klausDiscountAcknowledged = true;
            dialog.remove();
            renderCart();
        };
        dialog.querySelector('button')?.addEventListener('click', close);
        document.body.append(dialog);
        dialog.querySelector('button')?.focus();
    }

    const fold = value => String(value || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    }[char]));

    const formatDescription = value => escapeHtml(value || '')
        .replace(/\n/g, '<br>');

    const safeImage = value => {
        const url = String(value || '').trim();
        if (url.startsWith('/')) {
            return url;
        }
        return '';
    };

    function syncProductUrl(productId) {
        const url = new URL(window.location.href);
        if (productId === null) {
            url.searchParams.delete('producto');
        } else {
            url.searchParams.set('producto', String(Number(productId)));
        }
        window.history.replaceState({}, '', url);
    }

    function searchShareUrl(query) {
        const url = new URL(window.location.href);
        const term = String(query || '').trim();
        url.searchParams.delete('producto');
        if (term.length >= 3) {
            url.searchParams.set('buscar', term);
        } else {
            url.searchParams.delete('buscar');
        }
        return url.href;
    }

    function syncSearchUrl(query) {
        window.history.replaceState({}, '', searchShareUrl(query));
    }

    const variantIndex = new Map();
    function persistCart() {
        try {
            if (state.cart.size === 0) {
                localStorage.removeItem(CART_STORAGE_KEY);
                return;
            }
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify({
                version: 1,
                updated_at: new Date().toISOString(),
                items: Array.from(state.cart, ([variantId, quantity]) => ({
                    variant_id: Number(variantId),
                    quantity: Number(quantity),
                })),
            }));
        } catch {
            // Algunos navegadores bloquean el almacenamiento local en modo privado.
        }
    }

    function restoreCart(shouldPersist = true) {
        state.cart.clear();
        try {
            const stored = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || 'null');
            if (!stored || stored.version !== 1 || !Array.isArray(stored.items)) {
                return;
            }
            stored.items.forEach(item => {
                const variantId = Number(item?.variant_id);
                const requested = Number(item?.quantity);
                const indexed = variantIndex.get(variantId);
                const maximum = Number(indexed?.variant.available_stock || 0);
                const quantity = Math.max(
                    0,
                    Math.min(maximum, Number.isFinite(requested) ? requested : 0)
                );
                if (quantity > 0) {
                    state.cart.set(variantId, quantity);
                }
            });
            if (shouldPersist) {
                persistCart();
            }
        } catch {
            try {
                localStorage.removeItem(CART_STORAGE_KEY);
            } catch {
                // El carrito sigue funcionando durante la sesión actual.
            }
        }
    }

    function savedCustomer() {
        try {
            const value = JSON.parse(localStorage.getItem(CUSTOMER_STORAGE_KEY) || 'null');
            return value && typeof value === 'object' ? value : {};
        } catch {
            return {};
        }
    }

    function persistCustomer(name, phone, email) {
        try {
            localStorage.setItem(CUSTOMER_STORAGE_KEY, JSON.stringify({ name, phone, email }));
        } catch {
            // El autocompletado nativo sigue funcionando aunque el navegador bloquee storage.
        }
    }

    function rebuildVariantIndex() {
        variantIndex.clear();
        products.forEach(product => {
            product.variants.forEach(variant => {
                variantIndex.set(Number(variant.id), { product, variant });
            });
        });
    }

    rebuildVariantIndex();
    if (catalogLoaded) {
        restoreCart();
        cartRestored = true;
    }

    async function refreshCatalog() {
        if (state.order) {
            return false;
        }
        // Varios eventos pueden solicitar la misma actualización (foco,
        // visibilidad y el temporizador). Se reutiliza una única descarga.
        if (catalogLoading) {
            return catalogLoading;
        }
        catalogLoading = (async () => {
        try {
            const url = new URL(app.api_url, window.location.href);
            url.searchParams.set('action', 'catalog');
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
            });
            const data = await response.json();
            if (!response.ok || !data.ok || !Array.isArray(data.products)) {
                return;
            }
            products = data.products;
            catalogLoaded = true;
            if (Array.isArray(data.categories)) {
                categoryTree = data.categories;
            }
            if (Array.isArray(data.tutorials)) tutorials = data.tutorials;
            if (Array.isArray(data.featured_product_ids)) {
                featuredProductIds = data.featured_product_ids.map(Number).filter(Number.isFinite);
            }
            rebuildVariantIndex();
            if (!cartRestored) {
                restoreCart();
                cartRestored = true;
            }

            let adjusted = false;
            const harmfulChanges = new Set(state.reducedAvailability);
            Array.from(state.cart).forEach(([variantId, quantity]) => {
                const indexed = variantIndex.get(Number(variantId));
                const maximum = Number(indexed?.variant.available_stock || 0);
                if (!indexed || maximum < 1) {
                    harmfulChanges.add(Number(variantId));
                    state.cart.delete(Number(variantId));
                    adjusted = true;
                } else if (quantity > maximum) {
                    harmfulChanges.add(Number(variantId));
                    state.cart.set(Number(variantId), maximum);
                    adjusted = true;
                }
            });
            state.reducedAvailability = harmfulChanges;
            state.changedAvailability = new Set(harmfulChanges);

            renderCategories();
            renderCatalog();
            renderCart();
            if (adjusted) {
                persistCart();
            }
            return adjusted;
        } catch {
            // La próxima actualización vuelve a intentarlo sin interrumpir la compra.
            return false;
        } finally {
            catalogLoading = null;
        }
        })();
        return catalogLoading;
    }

    // Conserva códigos técnicos como 20.1 o 24.1 en lugar de partirlos.
    const searchWords = value => (
        fold(value).match(/[a-z0-9]+(?:[.,][0-9]+)*/g) || []
    );

    function limitedEditDistance(left, right, maximum) {
        if (Math.abs(left.length - right.length) > maximum) {
            return maximum + 1;
        }
        let previous = Array.from({ length: right.length + 1 }, (_, index) => index);
        for (let leftIndex = 1; leftIndex <= left.length; leftIndex += 1) {
            const current = [leftIndex];
            let rowMinimum = current[0];
            for (let rightIndex = 1; rightIndex <= right.length; rightIndex += 1) {
                const substitution = previous[rightIndex - 1]
                    + (left[leftIndex - 1] === right[rightIndex - 1] ? 0 : 1);
                current[rightIndex] = Math.min(
                    previous[rightIndex] + 1,
                    current[rightIndex - 1] + 1,
                    substitution
                );
                rowMinimum = Math.min(rowMinimum, current[rightIndex]);
            }
            if (rowMinimum > maximum) {
                return maximum + 1;
            }
            previous = current;
        }
        return previous[right.length];
    }

    function tokenFieldScore(token, value, weight) {
        const normalized = fold(value);
        if (!normalized) {
            return -1;
        }
        const words = searchWords(normalized);
        if (normalized === token) {
            return weight + 90;
        }
        if (normalized.startsWith(token)) {
            return weight + 65;
        }
        if (words.some(word => word.startsWith(token))) {
            return weight + 50;
        }
        if (normalized.includes(token)) {
            return weight + 35;
        }

        return -1;
    }

    function localSearchScore(product, query) {
        const tokens = searchWords(query);
        if (!tokens.length) {
            return null;
        }
        const fields = [
            [product.name, 150],
            [product.description, 45],
            [product.category?.name, 35],
            ...product.variants.map(variant => [variant.name, 80]),
        ];
        let score = fold(product.name) === fold(query) ? 500 : 0;
        for (const token of tokens) {
            const best = fields.reduce(
                (maximum, [value, weight]) => Math.max(
                    maximum,
                    tokenFieldScore(token, value, weight)
                ),
                -1
            );
            if (best < 0) {
                return null;
            }
            score += best;
        }
        return score;
    }

    function categoryAndDescendantSlugs(slug) {
        const selected = new Set([slug]);
        const collect = node => {
            if (node.slug === slug) {
                const addChildren = child => {
                    selected.add(child.slug);
                    (child.children || []).forEach(addChildren);
                };
                (node.children || []).forEach(addChildren);
                return true;
            }
            return (node.children || []).some(collect);
        };
        categoryTree.some(collect);
        return selected;
    }

    function filteredProducts() {
        const visibleCategories = state.category
            ? categoryAndDescendantSlugs(state.category)
            : null;
        return products.filter(product => (
            !visibleCategories || visibleCategories.has(product.category?.slug)
        ));
    }

    function searchProducts() {
        const query = state.query.trim();
        if (!query) {
            return [];
        }
        return products
            .map(product => {
                const localScore = localSearchScore(product, query);
                const codeMatch = state.remoteSearchIds.has(Number(product.id));
                if (localScore === null && !codeMatch) {
                    return null;
                }
                return {
                    product,
                    score: (localScore || 0) + (codeMatch ? 10000 : 0),
                };
            })
            .filter(Boolean)
            .sort((left, right) => (
                right.score - left.score
                || left.product.name.localeCompare(right.product.name, 'es')
            ))
            .map(result => result.product);
    }

    async function requestCodeMatches(query) {
        codeSearchController?.abort();
        const controller = new AbortController();
        codeSearchController = controller;
        const request = ++codeSearchRequest;
        if (query.trim().length < 3) {
            state.remoteSearchIds = new Set();
            renderCatalog();
            return;
        }
        try {
            const url = new URL(app.api_url, window.location.href);
            url.searchParams.set('action', 'catalog_search');
            url.searchParams.set('q', query.trim());
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                signal: controller.signal,
            });
            const data = await response.json();
            if (request !== codeSearchRequest || query !== state.query) {
                return;
            }
            state.remoteSearchIds = new Set(
                response.ok && data.ok && Array.isArray(data.product_ids)
                    ? data.product_ids.map(Number)
                    : []
            );
            renderCatalog();
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            if (request === codeSearchRequest) {
                state.remoteSearchIds = new Set();
                renderCatalog();
            }
        }
    }

    function scheduleCodeSearch(query) {
        codeSearchController?.abort();
        window.clearTimeout(codeSearchTimer);
        codeSearchRequest += 1;
        state.remoteSearchIds = new Set();
        if (query.trim().length < 3) {
            return;
        }
        codeSearchTimer = window.setTimeout(() => requestCodeMatches(query), 250);
    }

    function productHasStock(product) {
        return product.variants.some(variant => (
            Number(variant.available_stock) > 0
        ));
    }

    function cartQuantity(variantId) {
        return Number(state.cart.get(Number(variantId)) || 0);
    }

    function visibleAvailable(variant) {
        return Math.max(
            0,
            Number(variant.available_stock) - cartQuantity(variant.id)
        );
    }

    function availableLabel(available) {
        const units = Number(available);
        if (units < 1) {
            return 'Agotado';
        }
        if (units === 1) {
            return 'Última unidad';
        }
        return `${units} disponibles`;
    }

    function exactAvailableLabel(available) {
        const units = Number(available);
        return availableLabel(units);
    }

    function setQuantity(variantId, requestedQuantity) {
        if (cartMaintenanceEnabled) {
            toast('El carrito está pausado por mantenimiento. Podés seguir recorriendo el catálogo.');
            return;
        }
        const indexed = variantIndex.get(Number(variantId));
        if (!indexed) {
            return;
        }
        const max = Number(indexed.variant.available_stock);
        const quantity = Math.max(0, Math.min(max, Number(requestedQuantity) || 0));
        const wasEmpty = state.cart.size === 0;
        const unitsBefore = Array.from(state.cart.values()).reduce((sum, value) => sum + Number(value), 0);
        if (quantity > 0) {
            state.cart.set(Number(variantId), quantity);
        } else {
            state.cart.delete(Number(variantId));
        }
        state.reducedAvailability.delete(Number(variantId));
        persistCart();
        renderCatalog();
        renderCart();
        if (quantity > 0 && Number(requestedQuantity) > 0) playCartPop();
        if (quantity > 0 && Number(requestedQuantity) > 0) {
            const units = Array.from(state.cart.values()).reduce((sum, value) => sum + Number(value), 0);
            const target = Number(rewards.reward_quantity_units || 20);
            const celebration = rewardOn('reward_quantity_enabled') && units >= target && unitsBefore < target;
            if (celebration) {
                playRewardFanfare();
                showQuantityRewardDialog(units, target);
            }
            if (celebration) {
                reactKlaus('is-celebrating');
            } else {
                setKlausPose('cart_add_jump', 1000);
                reactKlaus(wasEmpty ? 'is-waking' : 'is-reacting');
            }
        }
        if (quantity > 0 && wasEmpty) checkSurprise();
    }

    async function checkSurprise() {
        if (surpriseChecked || !rewardOn('reward_surprise_enabled')) return;
        surpriseChecked = true;
        try {
            const data = await apiJson({ action: 'reward_surprise' });
            surpriseUnlocked = data.unlocked === true;
            renderCart();
            if (surpriseUnlocked) {
                toast(rewards.reward_surprise_text || '🎁 ¡Sorpresa! Ganaste un descuento en este carrito.');
                reactKlaus('is-celebrating');
            }
        } catch { /* La compra continúa sin beneficio si no se puede verificar. */ }
    }

    if (state.cart.size) checkSurprise();

    function playCartPop() {
        if (!rewardOn('reward_cart_sound_enabled') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.frequency.setValueAtTime(420, context.currentTime);
            oscillator.frequency.exponentialRampToValueAtTime(680, context.currentTime + .07);
            gain.gain.setValueAtTime(.025, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(.001, context.currentTime + .1);
            oscillator.connect(gain).connect(context.destination); oscillator.start(); oscillator.stop(context.currentTime + .1);
        } catch { /* El feedback visual sigue disponible. */ }
    }

    function playRewardFanfare() {
        if (!rewardOn('reward_cart_sound_enabled') || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        try {
            const context = new (window.AudioContext || window.webkitAudioContext)();
            [523, 659, 784, 1047].forEach((frequency, index) => {
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                const start = context.currentTime + index * .11;
                oscillator.type = 'triangle';
                oscillator.frequency.setValueAtTime(frequency, start);
                gain.gain.setValueAtTime(.001, start);
                gain.gain.exponentialRampToValueAtTime(.06, start + .015);
                gain.gain.exponentialRampToValueAtTime(.001, start + .28);
                oscillator.connect(gain).connect(context.destination);
                oscillator.start(start); oscillator.stop(start + .3);
            });
        } catch { /* La celebración visual sigue disponible. */ }
    }

    function showQuantityRewardDialog(units, target) {
        const percent = Number(rewards.reward_quantity_percent || 3);
        openModal(`
            <section class="quantity-reward-dialog" aria-labelledby="quantity-reward-title">
                <span aria-hidden="true">🎉</span>
                <h2 id="quantity-reward-title">¡Siii, lo lograste!</h2>
                <p>Desbloqueaste <strong>${percent}% de descuento</strong> por sumar ${target} unidades.</p>
                <div class="reward-progress is-complete">
                    <div><strong><b>${Math.max(units, target)}</b> / ${target} unidades</strong><span>Beneficio desbloqueado</span></div>
                    <i aria-label="100% completado"><b style="width:100%"></b></i>
                    <small>100% del beneficio</small>
                </div>
                <button class="primary-button" type="button" data-close-modal>¡Excelente!</button>
            </section>
        `);
        window.setTimeout(() => elements.modalContent.querySelector('[data-close-modal]')?.focus(), 0);
    }

    function renderCategories() {
        const fallback = new Map();
        products.forEach(product => {
            const category = product.category || { name: 'Sin categoría', slug: 'sin-categoria' };
            fallback.set(category.slug, { ...category, children: [] });
        });
        const roots = categoryTree.length ? categoryTree : Array.from(fallback.values());
        const findPath = (nodes, slug, parents = []) => {
            for (const node of nodes) {
                const path = [...parents, node];
                if (node.slug === slug) return path;
                const found = findPath(Array.isArray(node.children) ? node.children : [], slug, path);
                if (found) return found;
            }
            return null;
        };
        const renderNode = (node, depth = 0) => {
            if (node.active === false) return '';
            const children = Array.isArray(node.children) ? node.children : [];
            const hasChildren = children.some(child => child.active !== false);
            const isExpanded = hasChildren && !collapsedCategories.has(node.slug);
            const nested = children.map(child => renderNode(child, depth + 1)).join('');
            return `
                <div class="category-node ${hasChildren ? 'has-children' : ''}">
                    <div class="category-row">
                        <button
                            class="category-button category-depth-${Math.min(depth, 4)} ${state.category === node.slug ? 'active' : ''}"
                            type="button"
                            ${hasChildren ? `data-category-toggle="${escapeHtml(node.slug)}" aria-expanded="${isExpanded}"` : `data-category="${escapeHtml(node.slug)}"`}
                            aria-label="${depth > 0 ? 'Subcategoría' : 'Categoría'} ${escapeHtml(node.name)}"
                        ><span class="category-branch" aria-hidden="true">${depth > 0 ? '↳' : ''}</span>${escapeHtml(node.name)}</button>
                        ${hasChildren ? `<button class="category-expand" type="button" data-category-toggle="${escapeHtml(node.slug)}" aria-expanded="${isExpanded}" aria-label="${isExpanded ? 'Ocultar' : 'Mostrar'} subcategorías de ${escapeHtml(node.name)}">${isExpanded ? '−' : '+'}</button>` : ''}
                    </div>
                    ${nested ? `<div class="category-children" ${isExpanded ? '' : 'hidden'}>${nested}</div>` : ''}
                </div>
            `;
        };
        elements.categories.innerHTML = `
            <button class="category-button ${state.category === '' ? 'active' : ''}" type="button" data-category="">Todos los productos</button>
            ${roots.map(node => renderNode(node)).join('')}
        `;
        const path = state.category ? findPath(roots, state.category) : null;
        elements.categoryBreadcrumb.innerHTML = path
            ? `<button type="button" data-category="">Todos los productos</button><span aria-hidden="true">›</span><span>${escapeHtml(path.map(node => node.name).join(' › '))}</span>`
            : '<button type="button" data-category="">Todos los productos</button>';
    }

    function productImage(product, className) {
        const image = safeImage(product.image_path);
        return image
            ? `<button
                    class="product-image-button"
                    type="button"
                    data-image-preview="${Number(product.id)}"
                    aria-label="Ampliar imagen de ${escapeHtml(product.name)}"
                ><img
                    class="${className}"
                    src="${escapeHtml(image)}"
                    alt="${escapeHtml(product.name)}"
                    loading="lazy"
                    decoding="async"
                ></button>`
            : `<div class="${className}-placeholder">SIN FOTO</div>`;
    }

    function priceRange(product) {
        const prices = product.variants.map(variant => Number(variant.price_cents));
        const minimum = Math.min(...prices);
        const maximum = Math.max(...prices);
        return minimum === maximum
            ? money(minimum)
            : `${money(minimum)} a ${money(maximum)}`;
    }

    function variantDisplayName(product, variant) {
        const name = String(variant?.name || '').trim();
        const normalized = name
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
        return product.variants.length === 1 && normalized === 'unica'
            ? ''
            : name;
    }

    function quantityLabel(product, variant) {
        const variantName = variantDisplayName(product, variant);
        return `Cantidad de ${product.name}${variantName ? ` ${variantName}` : ''}`;
    }

    function quantityControl(product, variant, extraClass = '') {
        const quantity = cartQuantity(variant.id);
        const available = visibleAvailable(variant);
        return `
            <div class="quantity-control ${extraClass}">
                <button
                    type="button"
                    data-quantity="${Number(variant.id)}"
                    data-value="${quantity - 1}"
                    ${cartMaintenanceEnabled || quantity < 1 ? 'disabled' : ''}
                    aria-label="Quitar una unidad"
                >−</button>
                <input
                    type="number"
                    min="0"
                    max="${Number(variant.available_stock)}"
                    value="${quantity}"
                    inputmode="numeric"
                    data-quantity-input="${Number(variant.id)}"
                    aria-label="${escapeHtml(quantityLabel(product, variant))}"
                    ${cartMaintenanceEnabled ? 'disabled' : ''}
                >
                <button
                    type="button"
                    data-quantity="${Number(variant.id)}"
                    data-value="${quantity + 1}"
                    ${cartMaintenanceEnabled || available < 1 ? 'disabled' : ''}
                    aria-label="Agregar una unidad"
                >+</button>
            </div>
        `;
    }

    function productSummary(product) {
        const hasVariants = product.variants.length > 1;
        const singleVariant = product.variants[0];
        const availability = hasVariants
            ? `${product.variants.length} variantes`
            : exactAvailableLabel(visibleAvailable(singleVariant));
        return `
            <article class="catalog-product-summary unified-product-row ${product.variants.some(variant => state.changedAvailability.has(Number(variant.id))) ? 'availability-changed' : ''} ${product.variants.some(variant => state.reducedAvailability.has(Number(variant.id))) ? 'availability-conflict' : ''}" role="listitem">
                <div class="unified-product-media">${productImage(product, 'summary-product-image')}</div>
                <button
                    class="summary-product-title"
                    type="button"
                    data-open-product="${Number(product.id)}"
                >
                    <strong>${escapeHtml(product.name)}</strong>
                    <small>${escapeHtml(product.category?.name || 'Sin categoría')}</small>
                </button>
                <button
                    class="summary-variant-count ${!hasVariants && Number(visibleAvailable(singleVariant)) < 1 ? 'none' : ''}"
                    type="button"
                    data-open-product="${Number(product.id)}"
                >
                    ${availability}
                </button>
                <strong class="summary-product-price">${priceRange(product)}</strong>
                ${hasVariants
                    ? `<button
                        class="summary-product-chevron"
                        type="button"
                        data-open-product="${Number(product.id)}"
                        aria-label="Abrir ${escapeHtml(product.name)}"
                    >›</button>`
                    : quantityControl(product, singleVariant, 'summary-quantity-control')}
            </article>
        `;
    }

    function productSummaryList(matches, showCount = false) {
        return `
            ${showCount ? `
                <div class="search-result-count" role="status">
                    ${matches.length} ${matches.length === 1 ? 'producto encontrado' : 'productos encontrados'}
                </div>
            ` : ''}
            <div class="catalog-summary-list" role="list">
                ${matches.map(productSummary).join('')}
            </div>
        `;
    }

    function completeProductList(matches, showCount = false) {
        const byCategory = new Map();
        matches.forEach(product => {
            const slug = product.category?.slug || 'sin-categoria';
            if (!byCategory.has(slug)) byCategory.set(slug, []);
            byCategory.get(slug).push(product);
        });
        const renderedSlugs = new Set();
        const renderNode = (node, depth = 0) => {
            if (node.active === false) return '';
            const ownProducts = byCategory.get(node.slug) || [];
            const children = (node.children || []).map(child => renderNode(child, depth + 1)).join('');
            if (!ownProducts.length && !children) return '';
            renderedSlugs.add(node.slug);
            const heading = depth === 0 ? 'h2' : 'h3';
            return `<section class="complete-list-category complete-list-depth-${Math.min(depth, 3)}">
                <${heading}>${escapeHtml(node.name)}</${heading}>
                ${ownProducts.length ? `<div class="catalog-summary-list" role="list">${ownProducts.map(productSummary).join('')}</div>` : ''}
                ${children}
            </section>`;
        };
        const tree = categoryTree.length ? categoryTree : [];
        const sections = tree.map(node => renderNode(node)).join('');
        const remaining = matches.filter(product => !renderedSlugs.has(product.category?.slug || 'sin-categoria'));
        return `${showCount ? productResultCount(matches) : ''}<div class="complete-product-list">${sections}${remaining.length ? `<section class="complete-list-category complete-list-depth-0"><h2>Otros productos</h2><div class="catalog-summary-list" role="list">${remaining.map(productSummary).join('')}</div></section>` : ''}</div>`;
    }

    function productResultCount(matches) {
        return `<div class="search-result-count" role="status">${matches.length} ${matches.length === 1 ? 'producto encontrado' : 'productos encontrados'}</div>`;
    }

    function catalogProductCard(product) {
        const hasVariants = product.variants.length > 1;
        const singleVariant = product.variants[0];
        return `
            <article class="catalog-view-card">
                <div class="catalog-view-image">${productImage(product, 'catalog-view-photo')}</div>
                <div class="catalog-view-copy">
                    <button type="button" data-open-product="${Number(product.id)}"><strong>${escapeHtml(product.name)}</strong></button>
                    <small>${escapeHtml(variantDisplayName(product, singleVariant) || product.category?.name || '')}</small>
                    <strong>${priceRange(product)}</strong>
                    <span class="catalog-view-stock">${hasVariants ? `${product.variants.length} variantes` : exactAvailableLabel(visibleAvailable(singleVariant))}</span>
                </div>
                ${hasVariants
                    ? `<button class="catalog-view-options" type="button" data-open-product="${Number(product.id)}">VER OPCIONES</button>`
                    : quantityControl(product, singleVariant, 'catalog-view-quantity')}
            </article>
        `;
    }

    function catalogProductGrid(matches) {
        const groups = new Map();
        matches.forEach(product => {
            const name = product.category?.name || 'Otros productos';
            if (!groups.has(name)) groups.set(name, []);
            groups.get(name).push(product);
        });
        return `<div class="catalog-view-groups">${Array.from(groups, ([name, productsInCategory]) => `
            <section class="catalog-view-group" aria-label="${escapeHtml(name)}">
                <h2>${escapeHtml(name)}</h2>
                <div class="catalog-view-grid">${productsInCategory.map(catalogProductCard).join('')}</div>
            </section>
        `).join('')}</div>`;
    }

    function catalogCategoryLanding() {
        const categories = categoryTree.filter(category => category.active !== false);
        if (!categories.length) return '<div class="empty-state"><h2>AÚN NO HAY CATEGORÍAS</h2><p>Probá con la Lista completa para recorrer los productos.</p></div>';
        return `<section class="catalog-category-landing" aria-labelledby="catalog-category-landing-title">
            <div><p class="eyebrow">CATÁLOGO</p><h2 id="catalog-category-landing-title">ELEGÍ UNA CATEGORÍA</h2><p>Recorré cada categoría para ver sus productos.</p></div>
            <div class="catalog-category-grid">${categories.map(category => `
                <button type="button" data-category="${escapeHtml(category.slug)}"><strong>${escapeHtml(category.name)}</strong><small>${(category.children || []).filter(child => child.active !== false).length ? 'Ver subcategorías y productos' : 'Ver productos'}</small><span aria-hidden="true">→</span></button>
            `).join('')}</div>
        </section>`;
    }

    function minimalProductRow(product) {
        const hasVariants = product.variants.length > 1;
        const singleVariant = product.variants[0];
        return `
            <article class="minimal-product-row" role="listitem">
                <div>${productImage(product, 'minimal-product-image')}</div>
                <button type="button" data-open-product="${Number(product.id)}"><strong>${escapeHtml(product.name)}</strong><small>${escapeHtml(hasVariants ? `${product.variants.length} variantes` : variantDisplayName(product, singleVariant) || product.category?.name || '')}</small></button>
                <strong>${priceRange(product)}</strong>
                <span class="minimal-product-stock">${hasVariants ? 'Ver opciones' : exactAvailableLabel(visibleAvailable(singleVariant))}</span>
                ${hasVariants
                    ? `<button class="minimal-product-open" type="button" data-open-product="${Number(product.id)}" aria-label="Abrir opciones de ${escapeHtml(product.name)}">›</button>`
                    : quantityControl(product, singleVariant, 'minimal-quantity-control')}
            </article>
        `;
    }

    function minimalCategoryPrompt() {
        return `<div class="empty-state">
            <h2>ELEGÍ UNA CATEGORÍA</h2>
            <p>Usá el menú para mostrar únicamente los productos de esa sección.</p>
        </div>`;
    }

    function productViewContent(matches, showCount = false) {
        const count = showCount ? productResultCount(matches) : '';
        if (productView === 'catalog') return state.category || state.searchActive ? `${count}${catalogProductGrid(matches)}` : catalogCategoryLanding();
        if (productView === 'minimal') return state.category || state.searchActive ? productSummaryList(matches, showCount) : minimalCategoryPrompt();
        if (showCount) return productSummaryList(matches, true);
        return completeProductList(matches, showCount);
    }

    function syncProductViewSwitcher() {
        elements.productViewSwitcher?.querySelectorAll('[data-product-view]').forEach(button => {
            const selected = button.dataset.productView === productView;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
    }

    function setProductView(view) {
        if (!PRODUCT_VIEWS.has(view)) return;
        productView = view;
        alwaysUseProductView = true;
        try {
            window.localStorage.setItem(PRODUCT_VIEW_STORAGE_KEY, view);
        } catch (_) { /* La vista funciona aunque el navegador no permita guardar la preferencia. */ }
        if (productView === 'list') setCategoryMenuOpen(false);
        syncProductViewSwitcher();
        renderCatalog();
    }

    function showProductViewChooser() {
        openModal(`
            <section class="product-view-chooser" aria-labelledby="product-view-chooser-title">
                <span class="product-view-chooser-icon" aria-hidden="true">◉</span>
                <h2 id="product-view-chooser-title">Bienvenida a Laboratorio Digital</h2>
                <p>La vista elegida se guarda y podés cambiarla cuando quieras.</p>
                <div>
                    <button type="button" data-product-view="list"><strong>Lista completa</strong><small>Todos los productos ordenados por categoría y subcategoría.</small></button>
                    <button type="button" data-product-view="catalog"><strong>Catálogo</strong><small>Una grilla visual para recorrer productos por categoría.</small></button>
                    <button type="button" data-product-view="minimal"><strong>Minimalista</strong><small>Elegí una categoría desde el menú para ver solo esa sección.</small></button>
                </div>
                <button class="klaus-welcome" type="button" aria-label="Acariciar a Klaus"><img class="klaus-image" src="${escapeHtml(app.asset_url)}/klaus_home_petting_prompt.png" alt=""><span>Bienvenida<br>soy <strong>Klaus</strong><small>¿Me hacés mimitos?</small></span></button>
            </section>
        `);
    }

    function featuredProductCard(product) {
        const hasVariants = product.variants.length > 1;
        const singleVariant = product.variants[0];
        return `
            <article class="featured-product-card">
                <div class="featured-product-image">${productImage(product, 'featured-product-photo')}</div>
                <div class="featured-product-copy">
                    <span>PRODUCTO DESTACADO</span>
                    <button type="button" data-open-product="${Number(product.id)}"><strong>${escapeHtml(product.name)}</strong></button>
                    <small>${escapeHtml(product.category?.name || 'Laboratorio Digital')}</small>
                </div>
                <div class="featured-product-bottom">
                    <strong>${priceRange(product)}</strong>
                    ${hasVariants
                        ? `<button class="featured-product-options" type="button" data-open-product="${Number(product.id)}">VER ${product.variants.length} VARIANTES →</button>`
                        : quantityControl(product, singleVariant, 'featured-quantity-control')}
                </div>
            </article>
        `;
    }

    function renderOpenedProduct(product) {
        elements.results.innerHTML = `
            <section class="opened-product" aria-labelledby="opened-product-title">
                <header class="opened-product-head">
                    <button
                        class="opened-product-back"
                        type="button"
                        data-close-product
                        aria-label="Volver al listado"
                    >‹</button>
                    <div>
                        <h2 id="opened-product-title">${escapeHtml(product.name)}</h2>
                        <button
                            class="description-button"
                            type="button"
                            data-description="${Number(product.id)}"
                        >Ver descripción</button>
                    </div>
                </header>
                <div class="opened-variant-list" role="list">
                    ${product.variants.map(variant => {
                        const quantity = cartQuantity(variant.id);
                        const available = visibleAvailable(variant);
                        const name = variantDisplayName(product, variant);
                        return `
                            <div class="opened-variant-row ${available ? '' : 'out-of-stock'} ${state.changedAvailability.has(Number(variant.id)) ? 'availability-changed' : ''} ${state.reducedAvailability.has(Number(variant.id)) ? 'availability-conflict' : ''}" role="listitem">
                                <div>${productImage(product, 'opened-variant-image')}</div>
                                ${name
                                    ? `<strong class="opened-variant-name">${escapeHtml(name)}</strong>`
                                    : '<span></span>'}
                                <span class="opened-variant-stock ${available ? '' : 'none'}">
                                    ${exactAvailableLabel(available)}
                                    ${quantity ? `<small>${quantity} en tu pedido</small>` : ''}
                                </span>
                                <strong class="opened-variant-price">${money(Number(variant.price_cents))}</strong>
                                ${quantityControl(product, variant, 'opened-quantity-control')}
                            </div>
                        `;
                    }).join('')}
                </div>
            </section>
        `;
    }

    function renderSearchCatalog() {
        const query = state.query.trim();
        if (!query) {
            elements.results.innerHTML = `
                <div class="search-empty-state">
                    <strong>BUSCÁ EN TODO EL CATÁLOGO</strong>
                    <p>Escribí parte del nombre, descripción, variante o código de barras.</p>
                </div>
            `;
            return;
        }

        const matches = searchProducts();
        if (!matches.length) {
            elements.results.innerHTML = `
                <div class="search-empty-state">
                    <strong>NO ENCONTRAMOS PRODUCTOS PARA “${escapeHtml(query)}”</strong>
                    <p>Probá con otro término o revisá el código ingresado.</p>
                </div>
            `;
            return;
        }

        elements.results.innerHTML = `
            <section class="store-home home-search-embedded" aria-label="Resultados de búsqueda">
                <div class="home-search-prompt">
                    <div><strong>RESULTADOS PARA “${escapeHtml(query)}”</strong><span>Encontramos productos en todo el catálogo, sin importar la categoría.</span></div>
                    <button class="search-share-link" type="button" data-copy-search-link="${escapeHtml(searchShareUrl(query))}" title="Copiar enlace de estos resultados">COPIAR ENLACE</button>
                </div>
                ${productViewContent(matches, true)}
            </section>
        `;
    }

    function renderCatalog() {
        syncProductViewSwitcher();
        document.body.classList.toggle('product-view-list', productView === 'list');
        document.body.classList.toggle('search-mode', state.searchActive);
        const isHome = !state.searchActive && !state.category && !state.showAll;
        document.body.classList.toggle('home-mode', isHome);
        elements.results.classList.toggle('search-results-mode', state.searchActive);
        const openedProduct = products.find(product => (
            Number(product.id) === Number(state.openedProductId)
        ));
        document.body.classList.toggle('product-detail-mode', Boolean(openedProduct));
        if (openedProduct) {
            renderOpenedProduct(openedProduct);
            return;
        }
        if (state.searchActive) {
            renderSearchCatalog();
            return;
        }

        if (isHome) {
            const featured = featuredProductIds
                .map(id => products.find(product => Number(product.id) === Number(id)))
                .filter(Boolean);
            const rootCategories = (categoryTree.length ? categoryTree : [])
                .filter(node => node.active !== false);
            const quickCategorySlugs = ['sublimables', 'accesorios', 'remeras', 'papeles'];
            const preferredRoots = quickCategorySlugs
                .map(slug => rootCategories.find(category => category.slug === slug))
                .filter(Boolean);
            const roots = [...preferredRoots, ...rootCategories.filter(category => !preferredRoots.includes(category))]
                .slice(0, 4);
            const homeSections = {
                featured: featured.length ? `<section class="home-featured-products" aria-labelledby="featured-products-title">
                        <div class="home-featured-heading"><div><p class="eyebrow">SELECCIÓN ESPECIAL</p><h2 id="featured-products-title">PRODUCTOS DESTACADOS</h2></div><span>Elegidos para inspirarte</span></div>
                        <div class="featured-product-grid">${featured.map(featuredProductCard).join('')}</div>
                    </section>` : '',
                gallery: `<section class="home-people-gallery" aria-label="Lo que podés encontrar en Laboratorio Digital">
                        <img src="${escapeHtml(safeImage(app.design?.hero_1_path) || '/v1/assets/brand/hero-1.webp')}" alt="Productos para personalizar" loading="lazy">
                        <img src="${escapeHtml(safeImage(app.design?.hero_2_path) || '/v1/assets/brand/hero-2.webp')}" alt="Indumentaria personalizada" loading="lazy">
                        <img src="${escapeHtml(safeImage(app.design?.hero_3_path) || '/v1/assets/brand/hero-3.webp')}" alt="Materiales y productos para crear" loading="lazy">
                    </section>`,
                categories: `<div class="quick-categories">
                        ${roots.map((category, index) => `<button type="button" data-category="${escapeHtml(category.slug)}"><span>${['◈', '◌', '◇', '△'][index]}</span><strong>${escapeHtml(category.name)}</strong><small>Ver productos</small></button>`).join('')}
                    </div>
                    <button class="show-all-products" type="button" data-show-all-products>VER TODOS LOS PRODUCTOS <span>→</span></button>`,
                tutorials: tutorials.length ? `<section class="home-tutorials" aria-labelledby="home-tutorials-title">
                        <div class="home-featured-heading"><div><p class="eyebrow">APRENDE</p><h2 id="home-tutorials-title">TUTORIALES</h2></div><span>Ideas y técnicas para crear</span></div>
                        <div class="tutorial-carousel">
                            <button class="tutorial-carousel-arrow" type="button" data-tutorial-carousel-direction="previous" aria-label="Ver tutorial anterior">&lt;</button>
                            <div class="tutorial-grid" data-tutorial-carousel tabindex="0">${tutorials.map(tutorial => `<button class="tutorial-card" type="button" data-open-tutorial="${Number(tutorial.id)}">
                                ${safeImage(tutorial.image_path) ? `<img src="${escapeHtml(safeImage(tutorial.image_path))}" alt="" loading="lazy">` : '<span class="tutorial-placeholder">APRENDE</span>'}
                                <strong>${escapeHtml(tutorial.title)}</strong><small>LEER TUTORIAL →</small>
                            </button>`).join('')}</div>
                            <button class="tutorial-carousel-arrow" type="button" data-tutorial-carousel-direction="next" aria-label="Ver siguiente tutorial">&gt;</button>
                        </div>
                    </section>` : '',
            };
            const defaultSectionOrder = ['featured', 'gallery', 'categories', 'tutorials'];
            const sectionOrder = String(app.design?.section_order || '').split(',').filter(section => defaultSectionOrder.includes(section));
            const orderedSections = sectionOrder.length === defaultSectionOrder.length ? sectionOrder : defaultSectionOrder;
            elements.results.innerHTML = `<section class="store-home" aria-label="Empezar a comprar">${orderedSections.map(section => homeSections[section]).join('')}</section>`;
            return;
        }

        const matches = filteredProducts();
        if (!matches.length) {
            elements.results.innerHTML = `
                <div class="empty-state">
                    <h2>NO ENCONTRAMOS PRODUCTOS</h2>
                    <p>Probá con otra palabra o elegí “Todos los productos”.</p>
                </div>
            `;
            return;
        }

        elements.results.innerHTML = productViewContent(matches);
    }

    function cartItems() {
        return Array.from(state.cart, ([variantId, quantity]) => {
            const indexed = variantIndex.get(variantId);
            return {
                ...indexed,
                quantity,
                lineTotal: Number(indexed.variant.price_cents) * quantity,
            };
        });
    }

    function renderCart() {
        const items = cartItems();
        const subtotal = items.reduce((sum, item) => sum + item.lineTotal, 0);
        const units = items.reduce((sum, item) => sum + item.quantity, 0);
        const discount = cartDiscount(subtotal, units);
        const total = subtotal - discount.cents;
        const productLabel = items.length === 1 ? 'producto diferente' : 'productos diferentes';
        const unitLabel = units === 1 ? 'unidad' : 'unidades';

        elements.mobileCartCount.textContent = String(units);
        elements.mobileCart.setAttribute('aria-label', `Abrir pedido: ${units} ${unitLabel}`);
        elements.cartSummaryMeta.textContent = `${items.length} ${productLabel} · ${units} ${unitLabel}`;
        elements.cartTotal.textContent = money(total);
        elements.cartSubtotal.textContent = money(subtotal);
        elements.cartDiscount.innerHTML = discountSummaryMarkup(discount);
        const needed = Math.max(0, Number(rewards.reward_quantity_units || 20) - units);
        const klausNearReward = rewardOn('reward_quantity_enabled') && units >= Math.max(1, Number(rewards.reward_quantity_units || 20) - 3);
        const klausReachedReward = rewardOn('reward_quantity_enabled') && units >= Number(rewards.reward_quantity_units || 20);
        const klausMessage = rewardOn('reward_klaus_messages_enabled') && items.length && (klausNearReward || surpriseUnlocked)
            ? (klausReachedReward ? '🎉 ¡Siii, lo lograste!' : (surpriseUnlocked ? rewards.reward_klaus_surprise_text : rewards.reward_klaus_near_text)) : '';
        elements.cartRewards.innerHTML = `
            ${klausMarkup(units, klausMessage)}
            ${rewardOn('reward_quantity_enabled') ? `<div class="reward-progress"><div><strong><b>${units}</b> / ${Number(rewards.reward_quantity_units || 20)} unidades</strong><span>${needed ? escapeHtml(rewardText('reward_quantity_pending_text', { faltan: needed, porcentaje: rewards.reward_quantity_percent || 3 })) : escapeHtml(rewardText('reward_quantity_unlocked_text', { porcentaje: rewards.reward_quantity_percent || 3 }))}</span></div><i aria-label="${Math.round(Math.min(100, units / Number(rewards.reward_quantity_units || 20) * 100))}% completado"><b style="width:${Math.min(100, units / Number(rewards.reward_quantity_units || 20) * 100)}%"></b></i><small>${Math.round(Math.min(100, units / Number(rewards.reward_quantity_units || 20) * 100))}% del beneficio</small></div>` : ''}
            ${surpriseUnlocked ? `<div class="reward-surprise"><strong>${escapeHtml(rewards.reward_surprise_text || '🎁 ¡Sorpresa! Ganaste un descuento en este carrito.')}</strong><span>${escapeHtml(rewards.reward_surprise_continue_text || '')}</span></div>` : ''}`;
        if (elements.mobileKlausHost) elements.mobileKlausHost.innerHTML = klausMarkup(units, klausMessage);
        protectKlausFromDarkReader(elements.cartRewards);
        protectKlausFromDarkReader(elements.mobileKlausHost);
        elements.checkout.disabled = items.length === 0 || !app.orders_enabled || cartMaintenanceEnabled;
        elements.checkout.textContent = cartMaintenanceEnabled
            ? 'CARRITO EN MANTENIMIENTO'
            : app.orders_enabled
            ? 'CONTINUAR PEDIDO'
            : 'PEDIDOS PRÓXIMAMENTE';
        const conflictNames = Array.from(state.reducedAvailability).map(variantId => {
            const indexed = variantIndex.get(Number(variantId));
            if (!indexed) return null;
            const variantName = variantDisplayName(indexed.product, indexed.variant);
            return variantName ? `${indexed.product.name} · ${variantName}` : indexed.product.name;
        }).filter(Boolean);
        const conflictNotice = conflictNames.length ? `
            <section class="stock-change-notice" role="alert" aria-live="assertive">
                <strong>CAMBIÓ EL STOCK DE TU PEDIDO</strong>
                <p>Otra compra modificó la disponibilidad y ajustamos estos productos:</p>
                <ul>${conflictNames.map(name => `<li>${escapeHtml(name)}</li>`).join('')}</ul>
                <button type="button" data-dismiss-stock-warning>ENTENDIDO</button>
            </section>` : '';
        elements.cartLines.innerHTML = conflictNotice + (items.length ? items.map(item => `
            <div class="cart-line ${state.reducedAvailability.has(Number(item.variant.id)) ? 'availability-conflict' : ''}">
                <div class="cart-line-head">
                    <div class="cart-product-main">
                        ${productImage(item.product, 'cart-product-image')}
                        <div>
                            <strong>${escapeHtml(item.product.name)}</strong>
                            ${variantDisplayName(item.product, item.variant)
                                ? `<br><small>${escapeHtml(variantDisplayName(item.product, item.variant))}</small>`
                                : ''}
                        </div>
                    </div>
                </div>
                <div class="cart-line-bottom">
                    <div class="quantity-control">
                        <button
                            type="button"
                            data-quantity="${Number(item.variant.id)}"
                            data-value="${item.quantity - 1}"
                        >−</button>
                        <input
                            type="number"
                            min="0"
                            max="${Number(item.variant.available_stock)}"
                            value="${item.quantity}"
                            data-quantity-input="${Number(item.variant.id)}"
                            aria-label="${escapeHtml(quantityLabel(item.product, item.variant))}"
                        >
                        <button
                            type="button"
                            data-quantity="${Number(item.variant.id)}"
                            data-value="${item.quantity + 1}"
                            ${item.quantity >= Number(item.variant.available_stock) ? 'disabled' : ''}
                        >+</button>
                    </div>
                    <small>${money(Number(item.variant.price_cents))} c/u</small>
                    <strong class="cart-line-subtotal">${money(item.lineTotal)}</strong>
                    <button
                        class="cart-remove icon-action-button trash-button"
                        type="button"
                        data-remove-item="${Number(item.variant.id)}"
                        aria-label="Quitar ${escapeHtml(item.product.name)} del pedido"
                        title="Quitar del pedido"
                    ><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"/></svg></button>
                </div>
            </div>
        `).join('') : '<p class="empty-copy">Todavía no agregaste productos.</p>');
    }

    function openModal(html) {
        elements.modalContent.innerHTML = html;
        protectKlausFromDarkReader(elements.modalContent);
        elements.modal.classList.add('open');
        elements.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        elements.modal.classList.remove('open');
        elements.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function showStoreContact() {
        const contact = app.contact || {};
        const phone = String(contact.whatsapp_number || app.whatsapp_number || '').replace(/\D+/g, '');
        const address = String(contact.pickup_address || '').trim();
        const hours = String(contact.business_hours || '').trim();
        openModal(`
            <section class="store-contact-modal">
                <p class="eyebrow">CONTACTO</p>
                <h2 id="modal-title">${escapeHtml(contact.store_name || 'Laboratorio Digital')}</h2>
                <p class="contact-modal-lead">Estamos para ayudarte a encontrar los productos que necesitás.</p>
                <div class="contact-info-grid">
                    <a class="contact-info-card contact-whatsapp-card" href="whatsapp://send?phone=${escapeHtml(phone)}" target="_blank" rel="noopener">
                        <span>WHATSAPP</span><strong>Escribinos por WhatsApp</strong><small>+${escapeHtml(phone)}</small>
                    </a>
                    <div class="contact-info-card contact-hours-card">
                        <span>HORARIOS DE ATENCIÓN</span><strong>Te esperamos en el local</strong><small>${escapeHtml(hours || 'Consultanos por WhatsApp para coordinar.')}</small>
                    </div>
                    ${address ? `<a class="contact-info-card contact-map-card" href="${escapeHtml(contact.map_url || '#')}" target="_blank" rel="noopener">
                        <span>UBICACIÓN</span><strong>${escapeHtml(address)}</strong><small>Ver en Google Maps ↗</small>
                    </a>` : ''}
                </div>
            </section>
        `);
    }

    function showImagePreview(productId) {
        const product = products.find(item => Number(item.id) === Number(productId));
        const image = safeImage(product?.image_path);
        if (!product || !image) {
            return;
        }
        openModal(`
            <div class="image-viewer">
                <h2 id="modal-title">${escapeHtml(product.name)}</h2>
                <p>Hacé clic sobre la imagen para acercar o alejar.</p>
                <div class="image-viewer-stage">
                    <img
                        class="image-viewer-image"
                        src="${escapeHtml(image)}"
                        alt="${escapeHtml(product.name)}"
                        data-zoomable-image
                    >
                </div>
            </div>
        `);
    }

    function showDescription(productId) {
        const product = products.find(item => Number(item.id) === Number(productId));
        if (!product) {
            return;
        }
        openModal(`
            <h2 id="modal-title">${escapeHtml(product.name)}</h2>
            ${safeImage(product.image_path)
                ? `<img class="description-product-image" src="${escapeHtml(safeImage(product.image_path))}" alt="${escapeHtml(product.name)}" data-zoomable-image>`
                : ''}
            <p class="product-description-text">${formatDescription(product.description || 'Este producto todavía no tiene descripción.')}</p>
            <div class="notice">
                <strong>${priceRange(product)}</strong><br>
                ${product.variants.map(variant => {
                    const variantName = variantDisplayName(product, variant);
                    return variantName
                        ? `${escapeHtml(variantName)}: ${availableLabel(visibleAvailable(variant))}`
                        : availableLabel(visibleAvailable(variant));
                }).join('<br>')}
            </div>
        `);
    }

    async function showCheckout() {
        if (cartMaintenanceEnabled) {
            toast('Estamos realizando trabajos en la tienda. El carrito estará disponible nuevamente muy pronto.');
            return;
        }
        elements.checkout.disabled = true;
        elements.checkout.textContent = 'REVISANDO STOCK…';
        const previousUnits = Array.from(state.cart.values()).reduce(
            (sum, quantity) => sum + Number(quantity),
            0
        );
        const stockAdjusted = await refreshCatalog();
        const items = cartItems();
        if (!items.length) {
            if (previousUnits > 0) {
                toast('Ese stock cambió. Actualizamos el carrito antes de confirmar.');
            }
            renderCart();
            return;
        }
        if (stockAdjusted) {
            renderCart();
            return;
        }
        const subtotal = items.reduce((sum, item) => sum + item.lineTotal, 0);
        const discount = cartDiscount(subtotal, items.reduce((sum, item) => sum + item.quantity, 0));
        const total = subtotal - discount.cents;
        const customer = savedCustomer();
        // Crea un paso de historial interno: Atrás cierra el checkout y no
        // abandona la tienda hacia la página anterior del navegador.
        window.history.pushState({ catalogCheckout: true }, '', window.location.href);
        openModal(`
            <div class="klaus-checkout" aria-hidden="true"><img class="klaus-image" src="${escapeHtml(app.asset_url)}/klaus_checkout_sitting.png" alt=""></div>
            <h2 id="modal-title">TUS DATOS</h2>
            ${checkoutSteps(1)}
            <p class="checkout-lead">Solo necesitamos estos datos para identificar tu pedido.</p>
            <div class="checkout-lines">
                ${items.map(item => `
                    <div class="checkout-line">
                        <span>${item.quantity} × ${escapeHtml(item.product.name)}${variantDisplayName(item.product, item.variant)
                            ? ` · ${escapeHtml(variantDisplayName(item.product, item.variant))}`
                            : ''}</span>
                        <strong>${money(item.lineTotal)}</strong>
                    </div>
                `).join('')}
            </div>
            <div class="order-total"><span>Subtotal<br><small>${discountSummaryMarkup(discount)}<br>Total</small></span><strong>${money(total)}</strong></div>
            <form id="checkout-form" novalidate>
                <label>
                    Nombre y Apellido
                    <input name="name" required autocomplete="name" value="${escapeHtml(customer.name || '')}" aria-describedby="checkout-name-help">
                </label>
                <small id="checkout-name-help" class="field-help">Por favor escribilo completo, tal como querés que figure en tu pedido.</small>
                <label>
                    WhatsApp
                    <input
                        name="phone"
                        type="tel"
                        required
                        autocomplete="tel"
                        placeholder="Ej.: 341 569 9338"
                        value="${escapeHtml(customer.phone || '')}"
                    >
                </label>
                <label>
                    Email <span class="optional-field">(opcional)</span>
                    <input
                        name="email"
                        type="email"
                        autocomplete="email"
                        placeholder="Ej.: nombre@email.com"
                        value="${escapeHtml(customer.email || '')}"
                    >
                </label>
                <small class="field-help">Si lo completás, te enviaremos el detalle de esta compra.</small>
                <p class="form-error" id="checkout-error" role="alert" hidden></p>
                <button class="primary-button" type="submit">CONTINUAR AL PAGO</button>
            </form>
        `);
        const checkoutButton = elements.modalContent.querySelector('#checkout-form button[type="submit"]');
        if (checkoutButton) checkoutButton.textContent = 'CONFIRMAR PEDIDO';
    }

    function checkoutSteps(activeStep) {
        return `
            <ol class="checkout-steps" aria-label="Progreso del pedido">
                ${['Tus datos', 'Transferencia'].map((label, index) => {
                    const step = index + 1;
                    const stateClass = step < activeStep ? 'done' : (step === activeStep ? 'active' : '');
                    return `<li class="${stateClass}"><span>${step}</span>${label}</li>`;
                }).join('')}
            </ol>
        `;
    }

    async function refreshCsrfToken() {
        const url = new URL(app.api_url, window.location.href);
        url.searchParams.set('action', 'session');
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (!response.ok || !data.ok || !data.csrf_token) {
            throw new Error('No pudimos renovar la sesión. Actualizá la página e intentá nuevamente.');
        }
        app.csrf_token = data.csrf_token;
    }

    async function apiJson(payload, retryAttempt = 0) {
        const canRetryOrder = payload.action === 'create_order' && retryAttempt < 2;
        const retryOrderRequest = async () => {
            // La misma request_key evita crear dos ventas si el servidor sí
            // recibió el primer intento pero la respuesta se cortó.
            const pause = retryAttempt === 0 ? 350 : 900;
            await new Promise(resolve => window.setTimeout(resolve, pause));
            return apiJson(payload, retryAttempt + 1);
        };
        let response;
        try {
            response = await fetch(app.api_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': app.csrf_token,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...payload, csrf_token: app.csrf_token }),
            });
        } catch {
            if (canRetryOrder) {
                return retryOrderRequest();
            }
            throw new Error('No pudimos comunicarnos con el servidor. Revisá tu conexión e intentá nuevamente.');
        }
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch {
            if (canRetryOrder) {
                return retryOrderRequest();
            }
            throw new Error('No pudimos comunicarnos con el servidor. Intentá nuevamente.');
        }
        const sessionExpired = response.status === 401
            && /sesión venció|sesion vencio/i.test(String(data?.error || ''));
        if (sessionExpired && retryAttempt === 0) {
            await refreshCsrfToken();
            return apiJson(payload, retryAttempt + 1);
        }
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'No pudimos completar la operación.');
        }
        return data;
    }

    function hasValidCustomerFullName(value) {
        const parts = String(value || '').trim().split(/\s+/).filter(Boolean);
        return parts.length >= 2 && parts.every(part => /^\p{L}[\p{L}'’.-]{1,}$/u.test(part));
    }

    async function createOrder(form) {
        const button = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector('#checkout-error');
        const formData = new FormData(form);
        const customerName = String(formData.get('name') || '').trim();
        const customerPhone = String(formData.get('phone') || '').replace(/\D+/g, '');
        const customerEmail = String(formData.get('email') || '').trim();
        // La tienda opera con transferencia como único medio de pago web.
        const paymentMethod = 'bank_transfer';
        if (!hasValidCustomerFullName(customerName) || customerPhone.length < 8) {
            errorBox.hidden = false;
            errorBox.textContent = !hasValidCustomerFullName(customerName)
                ? 'Por favor, escribí tu nombre y apellido completos, tal como querés que figuren en el pedido.'
                : 'Por favor, revisá tu WhatsApp para que podamos responderte sin errores.';
            form.querySelector(!hasValidCustomerFullName(customerName)
                ? '[name="name"]'
                : '[name="phone"]')?.focus();
            return;
        }
        if (customerEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerEmail)) {
            errorBox.hidden = false;
            errorBox.textContent = 'Revisá el email o dejalo vacío si preferís no recibir el detalle.';
            form.querySelector('[name="email"]')?.focus();
            return;
        }
        errorBox.hidden = true;
        persistCustomer(customerName, String(formData.get('phone') || '').trim(), customerEmail);
        button.disabled = true;
        button.textContent = 'PREPARANDO TRANSFERENCIA…';
        try {
            const data = await apiJson({
                action: 'create_order',
                request_key: window.crypto?.randomUUID?.() || `${Date.now()}-${Math.random()}`,
                channel: 'web',
                payment_method: paymentMethod,
                customer: {
                    name: formData.get('name'),
                    email: customerEmail,
                    phone: formData.get('phone'),
                },
                items: cartItems().map(item => ({
                    variant_id: Number(item.variant.id),
                    quantity: item.quantity,
                })),
            });
            state.order = data.order;
            surpriseUnlocked = false;
            surpriseChecked = false;
            klausDiscountUnlocked = false;
            klausDiscountAcknowledged = false;
            klausRewardShown = false;
            klausAwake = false;
            window.sessionStorage.removeItem(KLAUS_REWARD_SHOWN_STORAGE_KEY);
            window.sessionStorage.removeItem(KLAUS_AWAKE_STORAGE_KEY);
            try { window.localStorage.removeItem(KLAUS_REWARD_DRAWN_STORAGE_KEY); } catch (_) { /* El pedido sigue funcionando sin almacenamiento local. */ }
            klausRewardChecked = false;
            const transferWhatsappUrl = whatsappUrl(data.order);
            state.cart.clear();
            persistCart();
            renderCatalog();
            renderCart();
            refreshCatalog();
            showTransferConfirmation(data.order, transferWhatsappUrl);
        } catch (error) {
            errorBox.hidden = false;
            errorBox.textContent = error.message;
            const stockAdjusted = await refreshCatalog();
            if (stockAdjusted) {
                closeModal();
                openMobileCart();
            } else {
                toast(error.message);
            }
            button.disabled = false;
            button.textContent = 'CONFIRMAR PEDIDO';
        }
    }

    function showTransferConfirmation(order, whatsapp) {
        const alias = order.bank?.alias || 'Pendiente de configurar';
        const cbu = order.bank?.cbu || '0070146030004048890954';
        const holder = order.bank?.holder || 'Laboratorio Digital';
        const total = money(Number(order.total_cents));
        openModal(`
            ${checkoutSteps(2)}
            ${rewardOn('reward_checkout_celebration_enabled') ? `<div class="checkout-celebration" aria-hidden="true">${rewardOn('reward_checkout_confetti_enabled') ? '✦ ✦ ✦' : ''}<b>✓</b></div><p class="checkout-celebration-copy">¡Listo! Recibimos tu compra.</p>` : ''}
            <h2 id="modal-title">¡Gracias por tu pedido!</h2>
            <p class="checkout-lead">Tu pedido <strong>${escapeHtml(order.public_number)}</strong> ingresó correctamente.</p>
            <section class="transfer-ready" aria-live="polite"><span aria-hidden="true">✨</span><div><strong>Ya está casi listo</strong><p>Transferí el total con estos datos y después avisános por WhatsApp.</p></div></section>
            <div class="payment-focus">
                <span>Para confirmarlo, transferí</span>
                <strong class="payment-amount">${total}</strong>
                <span>a nombre de ${escapeHtml(holder)}</span>
            </div>
            <dl class="bank-details">
                <div><dt>Alias</dt><dd>${escapeHtml(alias)} <button class="copy-button" type="button" data-copy-bank="${escapeHtml(alias)}">COPIAR</button></dd></div>
                <div><dt>CBU</dt><dd>${escapeHtml(cbu)} <button class="copy-button" type="button" data-copy-bank="${escapeHtml(cbu)}">COPIAR</button></dd></div>
            </dl>
            <p class="copy-bank-help">Tocá <strong>COPIAR</strong> y el dato se guarda en el portapapeles para pegarlo en tu banco.</p>
            <div class="payment-instructions">
                <strong>Cuando la hagas, avisános por WhatsApp</strong>
                <span>Así preparamos tu pedido apenas veamos la transferencia.</span>
            </div>
            <a class="primary-button button-link" href="${escapeHtml(whatsapp)}" target="_blank" rel="noopener" data-whatsapp-order-complete>AVISAR POR WHATSAPP</a>
            <button class="secondary-button" type="button" data-finish-order>VOLVER A LA TIENDA</button>
        `);
    }

    function whatsappUrl(order) {
        const lines = cartItems().map(item => (
            `- ${item.quantity} x ${item.product.name}${variantDisplayName(item.product, item.variant)
                ? ` · ${variantDisplayName(item.product, item.variant)}`
                : ''} (${money(item.lineTotal)})`
        )).join('\n');
        const message = [
            'Hola Laboratorio Digital.',
            '',
            `Soy ${order.customer_name || 'el cliente'}.`,
            `Comparto el detalle del pedido ${order.public_number}.`,
            '',
            'Productos:',
            lines,
            '',
            `Total: ${money(Number(order.total_cents))}`,
        ].join('\n');
        return `whatsapp://send?phone=${app.whatsapp_number}&text=${encodeURIComponent(message)}`;
    }

    function rememberCompletedOrder() {
        if (isMobileStorefront() || !state.order?.public_number) {
            return;
        }
        try {
            sessionStorage.setItem(ORDER_COMPLETE_STORAGE_KEY, JSON.stringify({
                public_number: String(state.order.public_number),
            }));
        } catch {
            // Si el navegador bloquea el almacenamiento, la compra igualmente finaliza.
        }
    }

    function showCompletedOrderNotice(order) {
        openModal(`
            <div class="success-box">
                <strong>¡Gracias por tu compra!</strong>
                <span>Tu pedido <strong>${escapeHtml(order.public_number)}</strong> ingresó correctamente.</span>
            </div>
            <p class="checkout-lead">Ya recibimos tu solicitud. Te contactaremos por WhatsApp para continuar con el pedido.</p>
            <button class="primary-button" type="button" data-close-modal>SEGUIR COMPRANDO</button>
        `);
    }

    function finishOrder() {
        rememberCompletedOrder();
        state.cart.clear();
        persistCart();
        state.order = null;
        // Volvemos a una portada limpia después de confirmar el pedido. Así el
        // cliente no queda detenido en un carrito vacío ni en el checkout.
        const homeUrl = new URL(window.location.href);
        homeUrl.search = '';
        homeUrl.hash = '';
        if (isMobileStorefront()) {
            sessionStorage.removeItem(ORDER_COMPLETE_STORAGE_KEY);
            window.location.replace(homeUrl.href);
            return;
        }
        window.location.assign(homeUrl.href);
    }

    function toast(message) {
        elements.toast.textContent = message;
        elements.toast.classList.add('open');
        window.setTimeout(() => elements.toast.classList.remove('open'), 3600);
    }

    function closeSearchMode() {
        codeSearchController?.abort();
        codeSearchRequest += 1;
        state.searchActive = false;
        state.openedProductId = null;
        syncProductUrl(null);
        syncSearchUrl('');
        state.query = '';
        state.remoteSearchIds = new Set();
        elements.search.value = '';
        elements.search.blur();
        renderCatalog();
    }

    document.addEventListener('click', event => {
        const carouselArrow = event.target.closest('[data-tutorial-carousel-direction]');
        if (carouselArrow) {
            const carousel = carouselArrow.parentElement?.querySelector('[data-tutorial-carousel]');
            const direction = carouselArrow.dataset.tutorialCarouselDirection === 'previous' ? -1 : 1;
            carousel?.scrollBy({ left: direction * carousel.clientWidth * 0.9, behavior: 'smooth' });
            return;
        }
        const tutorialButton = event.target.closest('[data-open-tutorial]');
        if (tutorialButton) {
            const tutorial = tutorials.find(item => Number(item.id) === Number(tutorialButton.dataset.openTutorial));
            if (tutorial) openModal(`<article class="tutorial-reader">
                ${safeImage(tutorial.image_path) ? `<img src="${escapeHtml(safeImage(tutorial.image_path))}" alt="">` : ''}
                <p class="eyebrow">APRENDE</p><h2 id="modal-title">${escapeHtml(tutorial.title)}</h2>
                <div>${escapeHtml(tutorial.content).replace(/\n/g, '<br>')}</div>
            </article>`);
            return;
        }
        const productViewButton = event.target.closest('[data-product-view]');
        if (productViewButton) {
            const chooser = productViewButton.closest('.product-view-chooser');
            setProductView(productViewButton.dataset.productView);
            if (chooser) closeModal();
            return;
        }
        if (event.target.closest('[data-dismiss-stock-warning]')) {
            state.reducedAvailability.clear();
            renderCatalog();
            renderCart();
            return;
        }
        const copyBank = event.target.closest('[data-copy-bank]');
        if (copyBank) {
            const bankValue = copyBank.dataset.copyBank;
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(bankValue)
                    .then(() => toast('Dato bancario copiado.'))
                    .catch(() => window.prompt('Copiá este dato:', bankValue));
            } else {
                window.prompt('Copiá este dato:', bankValue);
            }
            return;
        }
        const copySearchLink = event.target.closest('[data-copy-search-link]');
        if (copySearchLink) {
            const link = copySearchLink.dataset.copySearchLink;
            navigator.clipboard.writeText(link)
                .then(() => toast('Enlace de búsqueda copiado. Ya podés pegarlo en WhatsApp.'))
                .catch(() => window.prompt('Copiá este enlace:', link));
            return;
        }
        const imagePreview = event.target.closest('[data-image-preview]');
        if (imagePreview) {
            showImagePreview(Number(imagePreview.dataset.imagePreview));
            return;
        }

        const zoomableImage = event.target.closest('[data-zoomable-image]');
        if (zoomableImage) {
            zoomableImage.classList.toggle('zoomed');
            return;
        }

        const categoryToggle = event.target.closest('[data-category-toggle]');
        if (categoryToggle) {
            const slug = categoryToggle.dataset.categoryToggle;
            if (collapsedCategories.has(slug)) {
                collapsedCategories.delete(slug);
            } else {
                collapsedCategories.add(slug);
            }
            renderCategories();
            return;
        }

        const category = event.target.closest('[data-category]');
        if (category) {
            state.category = category.dataset.category;
            state.showAll = state.category === '';
            state.query = '';
            state.searchActive = false;
            state.openedProductId = null;
            syncProductUrl(null);
            syncSearchUrl('');
            state.remoteSearchIds = new Set();
            elements.search.value = '';
            setCategoryMenuOpen(false);
            elements.categoryToggle.setAttribute('aria-expanded', 'false');
            elements.categoryMenu.setAttribute('aria-expanded', 'false');
            window.history.pushState({ catalog: true }, '', window.location.href);
            renderCategories();
            renderCatalog();
            if (!catalogLoaded) {
                refreshCatalog();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (event.target.closest('[data-show-all-products]')) {
            state.category = '';
            state.showAll = true;
            window.history.pushState({ catalog: true }, '', window.location.href);
            renderCategories();
            renderCatalog();
            if (!catalogLoaded) {
                refreshCatalog();
            }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const openProduct = event.target.closest('[data-open-product]');
        if (openProduct) {
            state.openedProductId = Number(openProduct.dataset.openProduct);
            syncProductUrl(state.openedProductId);
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (event.target.closest('[data-close-product]')) {
            const previousProductId = state.openedProductId;
            state.openedProductId = null;
            syncProductUrl(null);
            renderCatalog();
            window.requestAnimationFrame(() => {
                const trigger = document.querySelector(
                    `[data-open-product="${Number(previousProductId)}"]`
                );
                trigger?.focus({ preventScroll: true });
            });
            return;
        }

        const quantityButton = event.target.closest('[data-quantity]');
        if (quantityButton) {
            setQuantity(
                Number(quantityButton.dataset.quantity),
                Number(quantityButton.dataset.value)
            );
            return;
        }

        const removeItem = event.target.closest('[data-remove-item]');
        if (removeItem) {
            setQuantity(Number(removeItem.dataset.removeItem), 0);
            toast('Producto quitado del pedido.');
            return;
        }

        const description = event.target.closest('[data-description]');
        if (description) {
            showDescription(Number(description.dataset.description));
            return;
        }

        if (event.target.closest('[data-close-modal]')) {
            closeModal();
            return;
        }

        if (event.target.closest('[data-finish-order]')) {
            finishOrder();
            return;
        }

        if (event.target.closest('[data-whatsapp-order-complete]')) {
            const whatsappLink = event.target.closest('[data-whatsapp-order-complete]');
            if (isMobileStorefront()) {
                event.preventDefault();
                // Abrimos WhatsApp durante el gesto del usuario y dejamos la
                // pestaña de la tienda ya ubicada en la portada para su regreso.
                window.open(whatsappLink.href, '_blank', 'noopener');
                finishOrder();
                return;
            }
            window.setTimeout(finishOrder, 0);
        }
    });

    window.Klaus?.attach(document, '.klaus', async () => {
        apiJson({ action: 'klaus_interaction' }).catch(() => {});
        klausAwake = true;
        window.sessionStorage.setItem(KLAUS_AWAKE_STORAGE_KEY, '1');
        if (!reactKlaus('is-petted is-celebrating')) renderCart();
        window.setTimeout(() => {
            setKlausPose('after_touch_happy_tailwag', 4000);
            window.Klaus?.pant(1800);
        }, 950);
        if (klausDiscountPending || klausRewardShown || klausRewardChecked) return;
        const playClink = prepareKlausClink();
        const rewardAt = Date.now() + 700;
        const showReward = () => window.setTimeout(() => {
            klausDiscountPending = false;
            klausRewardShown = true;
            window.sessionStorage.setItem(KLAUS_REWARD_SHOWN_STORAGE_KEY, '1');
            renderCart();
            playClink();
            showKlausRewardDialog();
        }, Math.max(0, rewardAt - Date.now()));
        if (klausDiscountUnlocked && klausRewardChecked) {
            klausDiscountPending = true;
            showReward();
            return;
        }
        klausDiscountPending = true;
        try {
            const reward = await apiJson({ action: 'reward_klaus' });
            klausRewardChecked = true;
            try { window.localStorage.setItem(KLAUS_REWARD_DRAWN_STORAGE_KEY, '1'); } catch (_) { /* El servidor mantiene el bloqueo del carrito. */ }
            if (reward.unlocked === true) {
                klausDiscountUnlocked = true;
                showReward();
            } else {
                klausDiscountPending = false;
            }
        } catch (_) {
            klausDiscountPending = false;
            // El gesto y sus animaciones siguen funcionando aunque no pueda validarse el beneficio.
        }
    });

    window.Klaus?.attach(document, '.klaus-welcome', (klaus) => {
        apiJson({ action: 'klaus_interaction' }).catch(() => {});
        window.Klaus?.pose(klaus, 'touch_bark_hearts');
        window.setTimeout(() => {
            window.Klaus?.pose(klaus, 'after_touch_happy_tailwag');
            window.Klaus?.pant(1800);
        }, 950);
        window.setTimeout(() => window.Klaus?.pose(klaus, 'home_petting_prompt'), 4800);
    });

    ['pointerdown', 'scroll', 'keydown'].forEach(type => document.addEventListener(type, keepKlausAwake, { passive: true }));
    keepKlausAwake();
    window.setInterval(() => {
        if (KLAUS_NORMAL_POSES.includes(klausPose)) {
            setKlausPose(KLAUS_NORMAL_POSES[Math.floor(Math.random() * KLAUS_NORMAL_POSES.length)]);
        }
    }, 6000);

    document.addEventListener('change', event => {
        if (event.target.matches('[data-quantity-input]')) {
            setQuantity(
                Number(event.target.dataset.quantityInput),
                Number(event.target.value)
            );
        }
    });

    document.addEventListener('submit', event => {
        if (event.target.id === 'checkout-form') {
            event.preventDefault();
            createOrder(event.target);
        }
    });

    elements.search.addEventListener('input', event => {
        state.query = event.target.value;
        // Con menos de tres caracteres se conserva la categoría actual.
        state.searchActive = state.query.trim().length >= 3;
        state.openedProductId = null;
        syncProductUrl(null);
        syncSearchUrl(state.query);
        scheduleCodeSearch(state.query);
        renderCatalog();
        if (state.searchActive && !catalogLoaded) {
            refreshCatalog();
        }
    });
    document.getElementById('contact-button')?.addEventListener('click', showStoreContact);
    function closeMobileCart({ returnToCatalog = false } = {}) {
        elements.orderPanel.classList.remove('mobile-open');
        document.body.classList.remove('cart-open');
        if (returnToCatalog) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            elements.mobileCart.focus({ preventScroll: true });
        }
    }

    function openMobileCart() {
        elements.orderPanel.classList.add('mobile-open');
        document.body.classList.add('cart-open');
        window.requestAnimationFrame(() => elements.closeMobileCart.focus({ preventScroll: true }));
        if (isMobileStorefront() && !window.history.state?.[CART_HISTORY_KEY]) {
            window.history.pushState({
                ...(window.history.state || {}),
                [CART_HISTORY_KEY]: true,
            }, '', window.location.href);
        }
    }

    function leaveMobileCartForCatalog() {
        if (window.history.state?.[CART_HISTORY_KEY]) {
            window.history.back();
            return;
        }
        closeMobileCart({ returnToCatalog: true });
    }

    window.addEventListener('popstate', () => {
        closeMobileCart();
        if (elements.modal.classList.contains('open')) {
            closeModal();
            return;
        }
        const url = new URL(window.location.href);
        state.category = '';
        state.showAll = false;
        state.query = String(url.searchParams.get('buscar') || '').trim();
        state.searchActive = state.query.length >= 3;
        state.openedProductId = null;
        elements.search.value = state.query;
        renderCategories();
        renderCatalog();
    });
    elements.search.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeSearchMode();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            requestCodeMatches(state.query);
        }
    });
    elements.closeSearch.addEventListener('click', closeSearchMode);
    elements.checkout.addEventListener('click', showCheckout);
    elements.mobileCart.addEventListener('click', () => {
        openMobileCart();
    });
    elements.closeMobileCart.addEventListener('click', () => {
        leaveMobileCartForCatalog();
    });
    document.getElementById('continue-shopping-button')?.addEventListener('click', leaveMobileCartForCatalog);
    elements.categoryToggle.addEventListener('click', () => {
        const isOpen = elements.categoryPanel.classList.toggle('mobile-open');
        elements.categoryToggle.setAttribute('aria-expanded', String(isOpen));
    });
    function setCategoryMenuOpen(open) {
        elements.categoryPanel.classList.toggle('menu-open', open);
        elements.categoryPanel.classList.toggle('mobile-open', open);
        elements.categoryBackdrop.classList.toggle('menu-open', open);
        document.body.classList.toggle('category-menu-open', open);
        elements.categoryMenu.setAttribute('aria-expanded', String(open));
        if (open) window.requestAnimationFrame(() => elements.categoryPanel.focus({ preventScroll: true }));
    }

    elements.categoryMenu.addEventListener('click', () => {
        setCategoryMenuOpen(!elements.categoryPanel.classList.contains('menu-open'));
    });
    elements.categoryPanel.addEventListener('wheel', event => {
        if (!elements.categoryPanel.classList.contains('menu-open')) return;
        if (elements.categoryPanel.scrollHeight <= elements.categoryPanel.clientHeight) return;
        event.preventDefault();
        elements.categoryPanel.scrollTop += event.deltaY;
    }, { passive: false });
    elements.categoryPanel.addEventListener('keydown', event => {
        if (!elements.categoryPanel.classList.contains('menu-open')) return;
        const amounts = { ArrowDown: 46, ArrowUp: -46, PageDown: 260, PageUp: -260 };
        if (Object.hasOwn(amounts, event.key)) {
            event.preventDefault();
            elements.categoryPanel.scrollTop += amounts[event.key];
        } else if (event.key === 'Home') {
            event.preventDefault();
            elements.categoryPanel.scrollTop = 0;
        } else if (event.key === 'End') {
            event.preventDefault();
            elements.categoryPanel.scrollTop = elements.categoryPanel.scrollHeight;
        }
    });
    elements.categoryBackdrop.addEventListener('click', () => {
        setCategoryMenuOpen(false);
    });
    elements.modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeModal();
        }
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || event.defaultPrevented) {
            return;
        }
        if (elements.modal.classList.contains('open')) {
            event.preventDefault();
            closeModal();
            return;
        }
        if (elements.orderPanel.classList.contains('mobile-open')) {
            event.preventDefault();
            leaveMobileCartForCatalog();
            return;
        }
        if (elements.categoryPanel.classList.contains('mobile-open')) {
            event.preventDefault();
            if (elements.categoryPanel.classList.contains('menu-open')) {
                setCategoryMenuOpen(false);
                return;
            }
            elements.categoryPanel.classList.remove('mobile-open');
            elements.categoryToggle.setAttribute('aria-expanded', 'false');
            return;
        }
        if (elements.categoryPanel.classList.contains('menu-open')) {
            event.preventDefault();
            setCategoryMenuOpen(false);
            return;
        }
        if (state.openedProductId !== null) {
            event.preventDefault();
            state.openedProductId = null;
            syncProductUrl(null);
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }
        if (state.searchActive || elements.search.value) {
            event.preventDefault();
            closeSearchMode();
        }
    });

    elements.search.value = state.query;
    if (state.searchActive) scheduleCodeSearch(state.query);
    renderCategories();
    renderCatalog();
    renderCart();
    if (!returnedFromQuote) window.setTimeout(showProductViewChooser, 350);
    // La lista completa es la vista inicial, por lo que el catálogo se carga
    // al entrar. Las imágenes conservan loading="lazy".
    const loadCatalogWhenIdle = () => refreshCatalog();
    loadCatalogWhenIdle();
    try {
        const completedOrder = JSON.parse(
            sessionStorage.getItem(ORDER_COMPLETE_STORAGE_KEY) || 'null'
        );
        sessionStorage.removeItem(ORDER_COMPLETE_STORAGE_KEY);
        if (completedOrder?.public_number && !isMobileStorefront()) {
            window.setTimeout(() => showCompletedOrderNotice(completedOrder), 120);
        }
    } catch {
        sessionStorage.removeItem(ORDER_COMPLETE_STORAGE_KEY);
    }
    // Una consulta frecuente mantiene el stock actualizado sin descargar el
    // catálogo completo varias veces por segundo en cada navegador.
    window.setInterval(() => {
        if (catalogLoaded && document.visibilityState === 'visible') {
            refreshCatalog();
        }
    }, 15000);
    document.addEventListener('visibilitychange', () => {
        if (catalogLoaded && document.visibilityState === 'visible') {
            refreshCatalog();
        }
    });
    window.addEventListener('focus', () => {
        if (catalogLoaded) refreshCatalog();
    });
    window.addEventListener('pageshow', () => {
        if (catalogLoaded) refreshCatalog();
    });
    window.addEventListener('storage', event => {
        if (event.key !== CART_STORAGE_KEY || state.order) {
            return;
        }
        restoreCart(false);
        renderCatalog();
        renderCart();
    });
})();
