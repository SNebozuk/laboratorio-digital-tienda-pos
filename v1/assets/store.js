(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('app-data').textContent);
    let products = Array.isArray(app.products) ? app.products : [];
    const state = {
        category: '',
        query: '',
        searchActive: false,
        openedProductId: null,
        remoteSearchIds: new Set(),
        cart: new Map(),
        order: null,
    };

    let codeSearchController = null;
    let codeSearchRequest = 0;
    const CART_STORAGE_KEY = 'laboratorio-digital:public-cart:v1';

    const elements = {
        categories: document.getElementById('category-list'),
        categoryPanel: document.querySelector('.category-panel'),
        categoryToggle: document.getElementById('category-toggle'),
        categoryBreadcrumb: document.getElementById('category-breadcrumb'),
        search: document.getElementById('product-search'),
        closeSearch: document.getElementById('search-close'),
        results: document.getElementById('catalog-results'),
        cartLines: document.getElementById('cart-lines'),
        cartTotal: document.getElementById('cart-total'),
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

    const safeImage = value => {
        const url = String(value || '').trim();
        if (url.startsWith('/') || /^https:\/\//i.test(url)) {
            return url;
        }
        return '';
    };

    const safePageUrl = value => {
        try {
            const url = new URL(String(value || ''), window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
        } catch {
            return '';
        }
    };

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

    function rebuildVariantIndex() {
        variantIndex.clear();
        products.forEach(product => {
            product.variants.forEach(variant => {
                variantIndex.set(Number(variant.id), { product, variant });
            });
        });
    }
    rebuildVariantIndex();
    restoreCart();

    async function refreshCatalog() {
        if (state.order) {
            return false;
        }
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
            rebuildVariantIndex();

            let adjusted = false;
            Array.from(state.cart).forEach(([variantId, quantity]) => {
                const indexed = variantIndex.get(Number(variantId));
                const maximum = Number(indexed?.variant.available_stock || 0);
                if (!indexed || maximum < 1) {
                    state.cart.delete(Number(variantId));
                    adjusted = true;
                } else if (quantity > maximum) {
                    state.cart.set(Number(variantId), maximum);
                    adjusted = true;
                }
            });

            renderCategories();
            renderCatalog();
            renderCart();
            if (adjusted) {
                persistCart();
                toast('Actualizamos el pedido porque cambió el stock disponible.');
            }
            return adjusted;
        } catch {
            // La próxima actualización vuelve a intentarlo sin interrumpir la compra.
            return false;
        }
    }

    const searchWords = value => fold(value)
        .replace(/[^a-z0-9]+/g, ' ')
        .trim()
        .split(/\s+/)
        .filter(Boolean);

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

        const tolerance = token.length >= 7 ? 2 : token.length >= 4 ? 1 : 0;
        if (tolerance && words.some(word => (
            limitedEditDistance(token, word, tolerance) <= tolerance
        ))) {
            return weight + 15;
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

    function filteredProducts() {
        return products.filter(product => (
            (!state.category || product.category?.slug === state.category)
            && productHasStock(product)
        ));
    }

    function searchProducts() {
        const query = state.query.trim();
        if (!query) {
            return [];
        }
        return products
            .filter(productHasStock)
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
        if (query.trim().length < 2) {
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
        codeSearchRequest += 1;
        state.remoteSearchIds = new Set();
        if (query.trim().length < 2) {
            return;
        }
        requestCodeMatches(query);
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
        if (units === 1) {
            return 'Última unidad';
        }
        return units <= 3 ? 'Últimas unidades' : 'Disponible';
    }

    function exactAvailableLabel(available) {
        const units = Number(available);
        return units > 0
            ? `${units} ${units === 1 ? 'disponible' : 'disponibles'}`
            : 'ⓘ Agotado';
    }

    function setQuantity(variantId, requestedQuantity) {
        const indexed = variantIndex.get(Number(variantId));
        if (!indexed) {
            return;
        }
        const max = Number(indexed.variant.available_stock);
        const quantity = Math.max(0, Math.min(max, Number(requestedQuantity) || 0));
        if (quantity > 0) {
            state.cart.set(Number(variantId), quantity);
        } else {
            state.cart.delete(Number(variantId));
        }
        persistCart();
        renderCatalog();
        renderCart();
    }

    function renderCategories() {
        const seen = new Map();
        products.filter(productHasStock).forEach(product => {
            const category = product.category || {
                name: 'Sin categoría',
                slug: 'sin-categoria',
            };
            seen.set(category.slug, category.name);
        });
        const categories = [
            { slug: '', name: 'Todos los productos' },
            ...Array.from(seen, ([slug, name]) => ({ slug, name })),
        ];
        elements.categories.innerHTML = categories.map(category => `
            <button
                class="category-button ${state.category === category.slug ? 'active' : ''}"
                type="button"
                data-category="${escapeHtml(category.slug)}"
            >${escapeHtml(category.name)}</button>
        `).join('');
        const current = categories.find(category => category.slug === state.category);
        elements.categoryBreadcrumb.textContent = current?.slug
            ? `Todos los productos › ${current.name}`
            : 'Todos los productos';
    }

    function productImage(product, className) {
        const image = safeImage(product.image_path);
        return image
            ? `<button
                    class="product-image-button"
                    type="button"
                    data-image-preview="${Number(product.id)}"
                    aria-label="Ampliar imagen de ${escapeHtml(product.name)}"
                ><img class="${className}" src="${escapeHtml(image)}" alt="${escapeHtml(product.name)}"></button>`
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
                    ${quantity < 1 ? 'disabled' : ''}
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
                >
                <button
                    type="button"
                    data-quantity="${Number(variant.id)}"
                    data-value="${quantity + 1}"
                    ${available < 1 ? 'disabled' : ''}
                    aria-label="Agregar una unidad"
                >+</button>
            </div>
        `;
    }

    function productSummary(product) {
        const hasVariants = product.variants.length > 1;
        const availability = hasVariants
            ? `${product.variants.length} ${product.variants.length === 1 ? 'variante' : 'variantes'}`
            : 'Disponible';
        return `
            <article class="catalog-product-summary unified-product-row" role="listitem">
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
                    class="summary-variant-count"
                    type="button"
                    data-open-product="${Number(product.id)}"
                >
                    ${availability}
                </button>
                <strong class="summary-product-price">${priceRange(product)}</strong>
                <button
                    class="summary-product-chevron"
                    type="button"
                    data-open-product="${Number(product.id)}"
                    aria-label="Abrir ${escapeHtml(product.name)}"
                >›</button>
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
                            <div class="opened-variant-row ${available ? '' : 'out-of-stock'}" role="listitem">
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
                    <strong>NO ENCONTRAMOS COINCIDENCIAS</strong>
                    <p>Probá con menos palabras o revisá el código ingresado.</p>
                </div>
            `;
            return;
        }

        elements.results.innerHTML = productSummaryList(matches, true);
    }

    function renderCatalog() {
        document.body.classList.toggle('search-mode', state.searchActive);
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

        elements.results.innerHTML = productSummaryList(matches);
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
        const total = items.reduce((sum, item) => sum + item.lineTotal, 0);
        const units = items.reduce((sum, item) => sum + item.quantity, 0);

        elements.mobileCartCount.textContent = String(units);
        elements.cartTotal.textContent = money(total);
        elements.checkout.disabled = items.length === 0 || !app.orders_enabled;
        elements.checkout.textContent = app.orders_enabled
            ? 'CONTINUAR PEDIDO'
            : 'PEDIDOS PRÓXIMAMENTE';
        elements.cartLines.innerHTML = items.length ? items.map(item => `
            <div class="cart-line">
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
                    <div class="cart-line-actions">
                        <strong>${money(item.lineTotal)}</strong>
                        <button
                            class="cart-remove"
                            type="button"
                            data-remove-item="${Number(item.variant.id)}"
                            aria-label="Quitar ${escapeHtml(item.product.name)} del pedido"
                            title="Quitar del pedido"
                        ><span aria-hidden="true">🗑</span></button>
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
                </div>
            </div>
        `).join('') : '<p class="empty-copy">Todavía no agregaste productos.</p>';
    }

    function openModal(html) {
        elements.modalContent.innerHTML = html;
        elements.modal.classList.add('open');
        elements.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        elements.modal.classList.remove('open');
        elements.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
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
            <p>${escapeHtml(product.description || 'Este producto todavía no tiene descripción.')}</p>
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
        const previousUnits = Array.from(state.cart.values()).reduce(
            (sum, quantity) => sum + Number(quantity),
            0
        );
        await refreshCatalog();
        const items = cartItems();
        if (!items.length) {
            if (previousUnits > 0) {
                toast('Ese stock cambió. Actualizamos el carrito antes de confirmar.');
            }
            return;
        }
        const total = items.reduce((sum, item) => sum + item.lineTotal, 0);
        openModal(`
            <h2 id="modal-title">CONFIRMAR PEDIDO</h2>
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
            <div class="order-total"><span>Total</span><strong>${money(total)}</strong></div>
            <div class="notice">
                Confirmar el pedido todavía no reserva unidades. La reserva se realiza
                al subir el comprobante, después de validar nuevamente el stock.
            </div>
            <form id="checkout-form">
                <label>
                    Nombre o comercio
                    <input name="name" required autocomplete="name">
                </label>
                <label>
                    WhatsApp
                    <input
                        name="phone"
                        type="tel"
                        required
                        autocomplete="tel"
                        placeholder="Ej.: 341 569 9338"
                    >
                </label>
                <label>
                    Email para recibir una copia (opcional)
                    <input name="email" type="email" autocomplete="email">
                </label>
                <button class="primary-button" type="submit">CREAR PEDIDO</button>
            </form>
        `);
    }

    async function apiJson(payload) {
        const response = await fetch(app.api_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': app.csrf_token,
            },
            body: JSON.stringify({
                ...payload,
                csrf_token: app.csrf_token,
            }),
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'No pudimos completar la operación.');
        }
        return data;
    }

    async function createOrder(form) {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'CREANDO PEDIDO…';
        try {
            const formData = new FormData(form);
            const data = await apiJson({
                action: 'create_order',
                channel: 'web',
                customer: {
                    name: formData.get('name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                },
                items: cartItems().map(item => ({
                    variant_id: Number(item.variant.id),
                    quantity: item.quantity,
                })),
            });
            state.order = data.order;
            showPayment(
                data.order,
                String(formData.get('email') || '').trim() !== ''
            );
        } catch (error) {
            toast(error.message);
            await refreshCatalog();
            button.disabled = false;
            button.textContent = 'CREAR PEDIDO';
        }
    }

    function showPayment(order, hasEmail) {
        const alias = order.bank?.alias || 'Pendiente de configurar';
        const cbu = order.bank?.cbu || 'Pendiente de configurar';
        const paymentUrl = safePageUrl(order.payment_url);
        const whatsappOrderUrl = whatsappUrl(order);
        openModal(`
            <h2 id="modal-title">PEDIDO ${escapeHtml(order.public_number)}</h2>
            <div class="success-box">
                Tu pedido fue creado.
                ${hasEmail
                    ? 'También enviaremos una copia al email informado.'
                    : 'Podés guardar el seguimiento o compartir el detalle por WhatsApp.'}
            </div>
            <div class="order-total">
                <span>Total exacto</span>
                <strong>${money(Number(order.total_cents))}</strong>
            </div>
            <p>
                Transferí a <strong>${escapeHtml(order.bank?.holder || 'Laboratorio Digital')}</strong><br>
                Alias: <strong>${escapeHtml(alias)}</strong><br>
                CBU: <strong>${escapeHtml(cbu)}</strong>
            </p>
            ${order.pickup_address ? `
                <p>
                    Retiro en: <strong>${escapeHtml(order.pickup_address)}</strong>
                </p>
            ` : ''}
            <div class="notice">
                El stock se reservará cuando subas el comprobante, siempre que
                siga disponible. Plazo: ${escapeHtml(order.payment_deadline_at)}.
            </div>
            <form id="proof-form">
                <label>
                    Comprobante JPG, PNG o PDF
                    <input name="proof" type="file" accept="image/jpeg,image/png,application/pdf" required>
                </label>
                <button class="primary-button" type="submit">
                    SUBIR COMPROBANTE Y RESERVAR
                </button>
            </form>
            ${paymentUrl ? `
                <p class="order-note">
                    ${hasEmail
                        ? 'También enviaremos por email un enlace personal para retomar esta carga más tarde.'
                        : 'Guardá este enlace personal para retomar la carga más tarde.'}
                </p>
                <a class="secondary-button" href="${escapeHtml(paymentUrl)}">
                    ABRIR SEGUIMIENTO DEL PEDIDO
                </a>
            ` : ''}
            <a
                class="whatsapp-button"
                href="${escapeHtml(whatsappOrderUrl)}"
                target="_blank"
                rel="noopener"
            >
                ENVIAR DETALLE POR WHATSAPP
            </a>
        `);
    }

    async function uploadProof(form) {
        const button = form.querySelector('button[type="submit"]');
        const file = form.querySelector('input[type="file"]').files[0];
        if (!file || !state.order) {
            return;
        }
        const payload = new FormData();
        payload.append('action', 'upload_proof');
        payload.append('csrf_token', app.csrf_token);
        payload.append('order_id', String(state.order.id));
        payload.append('upload_token', state.order.upload_token);
        payload.append('proof', file);
        button.disabled = true;
        button.textContent = 'SUBIENDO…';

        try {
            const response = await fetch(app.api_url, {
                method: 'POST',
                headers: { 'X-CSRF-Token': app.csrf_token },
                body: payload,
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.error || 'No pudimos subir el comprobante.');
            }
            showSuccess(data.result);
        } catch (error) {
            toast(error.message);
            button.disabled = false;
            button.textContent = 'SUBIR COMPROBANTE Y RESERVAR';
        }
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
            `Comparto el detalle del pedido ${order.public_number}.`,
            '',
            'Productos:',
            lines,
            '',
            `Total: ${money(Number(order.total_cents))}`,
        ].join('\n');
        return `https://wa.me/${app.whatsapp_number}?text=${encodeURIComponent(message)}`;
    }

    function showSuccess(result) {
        const order = state.order;
        const url = whatsappUrl(order);
        const paymentUrl = safePageUrl(order.payment_url);
        openModal(`
            <h2 id="modal-title">SU PEDIDO HA SIDO ENVIADO</h2>
            <div class="success-box">
                Recibimos el comprobante y el stock quedó reservado.
                El pago está pendiente de verificación.
            </div>
            <p>
                Pedido <strong>${escapeHtml(result.public_number)}</strong><br>
                Te avisaremos por WhatsApp o email cuando esté aprobado y listo para retirar.
            </p>
            <div class="button-row">
                <button class="secondary-button" type="button" data-finish-order>
                    FINALIZAR
                </button>
                ${paymentUrl ? `
                    <a class="secondary-button" href="${escapeHtml(paymentUrl)}">
                        VER SEGUIMIENTO
                    </a>
                ` : ''}
                <a class="whatsapp-button" href="${escapeHtml(url)}" target="_blank" rel="noopener">
                    ENVIAR DETALLE POR WHATSAPP
                </a>
            </div>
        `);
    }

    function finishOrder() {
        state.cart.clear();
        persistCart();
        state.order = null;
        renderCatalog();
        renderCart();
        closeModal();
        refreshCatalog();
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
        state.query = '';
        state.remoteSearchIds = new Set();
        elements.search.value = '';
        elements.search.blur();
        renderCatalog();
    }

    document.addEventListener('click', event => {
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

        const category = event.target.closest('[data-category]');
        if (category) {
            state.category = category.dataset.category;
            state.query = '';
            state.searchActive = false;
            state.openedProductId = null;
            state.remoteSearchIds = new Set();
            elements.search.value = '';
            elements.categoryPanel.classList.remove('mobile-open');
            elements.categoryToggle.setAttribute('aria-expanded', 'false');
            renderCategories();
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        const openProduct = event.target.closest('[data-open-product]');
        if (openProduct) {
            state.openedProductId = Number(openProduct.dataset.openProduct);
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (event.target.closest('[data-close-product]')) {
            const previousProductId = state.openedProductId;
            state.openedProductId = null;
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
        }
    });

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
        if (event.target.id === 'proof-form') {
            event.preventDefault();
            uploadProof(event.target);
        }
    });

    elements.search.addEventListener('input', event => {
        state.query = event.target.value;
        state.searchActive = Boolean(state.query.trim());
        if (state.searchActive) {
            state.category = '';
            renderCategories();
        }
        state.openedProductId = null;
        scheduleCodeSearch(state.query);
        renderCatalog();
    });
    elements.search.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
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
        elements.orderPanel.classList.add('mobile-open');
    });
    elements.closeMobileCart.addEventListener('click', () => {
        elements.orderPanel.classList.remove('mobile-open');
    });
    elements.categoryToggle.addEventListener('click', () => {
        const isOpen = elements.categoryPanel.classList.toggle('mobile-open');
        elements.categoryToggle.setAttribute('aria-expanded', String(isOpen));
    });
    elements.modal.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    renderCategories();
    renderCatalog();
    renderCart();
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshCatalog();
        }
    }, 30000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            refreshCatalog();
        }
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
