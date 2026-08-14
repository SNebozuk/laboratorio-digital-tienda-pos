(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('app-data').textContent);
    let products = Array.isArray(app.products) ? app.products : [];
    let categoryTree = Array.isArray(app.categories) ? app.categories : [];
    const linkedProductId = (() => {
        const value = Number(new URL(window.location.href).searchParams.get('producto'));
        return products.some(product => Number(product.id) === value) ? value : null;
    })();
    const state = {
        category: '',
        showAll: false,
        query: '',
        searchActive: false,
        openedProductId: linkedProductId,
        remoteSearchIds: new Set(),
        cart: new Map(),
        order: null,
        changedAvailability: new Set(),
        reducedAvailability: new Set(),
    };

    let codeSearchController = null;
    let codeSearchRequest = 0;
    // El menú arranca compacto: cada rama se abre con el indicador ›.
    const collapsedCategories = new Set(
        categoryTree
            .filter(category => Array.isArray(category.children) && category.children.some(child => child.active !== false))
            .map(category => category.slug)
    );
    const CART_STORAGE_KEY = 'laboratorio-digital:public-cart:v1';
    const CUSTOMER_STORAGE_KEY = 'laboratorio-digital:checkout-customer:v1';
    const ORDER_COMPLETE_STORAGE_KEY = 'laboratorio-digital:completed-order:v1';
    const CART_HISTORY_KEY = 'laboratorio-digital:mobile-cart-open';
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
        if (url.startsWith('/')) {
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

    function syncProductUrl(productId) {
        const url = new URL(window.location.href);
        if (productId === null) {
            url.searchParams.delete('producto');
        } else {
            url.searchParams.set('producto', String(Number(productId)));
        }
        window.history.replaceState({}, '', url);
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

    function availabilitySnapshot(catalog) {
        const snapshot = new Map();
        catalog.forEach(product => product.variants.forEach(variant => {
            snapshot.set(Number(variant.id), Number(variant.available_stock || 0));
        }));
        return snapshot;
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
            const previousAvailability = availabilitySnapshot(products);
            const changedVariantIds = new Set();
            const reducedVariantIds = new Set();
            data.products.forEach(product => product.variants.forEach(variant => {
                const id = Number(variant.id);
                if (previousAvailability.has(id)
                    && previousAvailability.get(id) !== Number(variant.available_stock || 0)) {
                    changedVariantIds.add(id);
                    if (Number(variant.available_stock || 0) < previousAvailability.get(id)) {
                        reducedVariantIds.add(id);
                    }
                }
            }));
            products = data.products;
            state.changedAvailability = changedVariantIds;
            state.reducedAvailability = reducedVariantIds;
            if (Array.isArray(data.categories)) {
                categoryTree = data.categories;
            }
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
            if (changedVariantIds.size) {
                toast(adjusted
                    ? 'Cambió la disponibilidad: ajustamos tu pedido.'
                    : 'Cambió la disponibilidad de algunos productos.');
            }
            if (adjusted) {
                persistCart();
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
        if (units < 1) {
            return 'Sin stock';
        }
        if (units === 1) {
            return 'Última unidad';
        }
        return units <= 3 ? 'Últimas unidades' : 'Disponible';
    }

    function exactAvailableLabel(available) {
        const units = Number(available);
        return units > 0
            ? `${units} ${units === 1 ? 'disponible' : 'disponibles'}`
            : 'Sin stock';
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
                    class="summary-variant-count"
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
            const roots = (categoryTree.length ? categoryTree : []).filter(node => node.active !== false).slice(0, 4);
            elements.results.innerHTML = `
                <section class="store-home" aria-label="Empezar a comprar">
                    <div class="home-search-prompt">
                        <strong>¿QUÉ ESTÁS BUSCANDO HOY?</strong>
                        <span>Usá el buscador o elegí una categoría para empezar.</span>
                    </div>
                    <section class="home-people-gallery" aria-label="Inspiración para tus próximos productos">
                        <img src="/v1/assets/brand/hero-1.webp" alt="Prenda personalizada en uso" loading="eager">
                        <img src="/v1/assets/brand/hero-2.webp" alt="Indumentaria personalizada" loading="lazy">
                        <img src="/v1/assets/brand/hero-3.webp" alt="Productos para personalizar" loading="lazy">
                    </section>
                    <div class="quick-categories">
                        ${roots.map((category, index) => `<button type="button" data-category="${escapeHtml(category.slug)}"><span>${['◈', '◌', '◇', '△'][index]}</span><strong>${escapeHtml(category.name)}</strong><small>Ver productos</small></button>`).join('')}
                    </div>
                    <button class="show-all-products" type="button" data-show-all-products>VER TODOS LOS PRODUCTOS <span>→</span></button>
                    <section class="home-how-it-works" aria-labelledby="home-how-title">
                        <div><p class="eyebrow">COMPRA SIMPLE</p><h2 id="home-how-title">ARMÁ TU PEDIDO EN TRES PASOS</h2></div>
                        <ol>
                            <li><span>1</span><div><strong>Buscá o elegí una categoría</strong><small>Encontrá el producto y la variante que necesitás.</small></div></li>
                            <li><span>2</span><div><strong>Sumá cantidades al pedido</strong><small>El carrito se actualiza al instante.</small></div></li>
                            <li><span>3</span><div><strong>Confirmá y elegí cómo pagar</strong><small>Transferencia o efectivo al retirar en el local.</small></div></li>
                        </ol>
                    </section>
                </section>`;
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
        elements.checkout.disabled = true;
        elements.checkout.textContent = 'REVISANDO STOCK…';
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
            renderCart();
            return;
        }
        const total = items.reduce((sum, item) => sum + item.lineTotal, 0);
        const customer = savedCustomer();
        // Crea un paso de historial interno: Atrás cierra el checkout y no
        // abandona la tienda hacia la página anterior del navegador.
        window.history.pushState({ catalogCheckout: true }, '', window.location.href);
        openModal(`
            ${checkoutSteps(1)}
            <h2 id="modal-title">TUS DATOS</h2>
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
            <div class="order-total"><span>Total</span><strong>${money(total)}</strong></div>
            <form id="checkout-form" novalidate>
                <label>
                    Nombre y Apellido
                    <input name="name" required autocomplete="name" value="${escapeHtml(customer.name || '')}">
                </label>
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
                <p class="form-error" id="checkout-error" role="alert" hidden></p>
                <button class="primary-button" type="submit">CONTINUAR AL PAGO</button>
            </form>
            <p class="checkout-footnote" data-payment-footnote>Elegí la forma de pago que te resulte más cómoda.</p>
        `);
        const transferOption = elements.modalContent.querySelector('[value="bank_transfer"]')?.closest('.payment-option');
        const cashOption = elements.modalContent.querySelector('[value="cash"]')?.closest('.payment-option');
        transferOption?.querySelector('small') && (transferOption.querySelector('small').textContent = 'Te mostraremos los datos y lo enviás por WhatsApp.');
        cashOption?.querySelector('small') && (cashOption.querySelector('small').textContent = 'Guardamos tu pedido durante 6 horas.');
        const reservationMessage = elements.modalContent.querySelector('#cash-reservation-warning');
        if (reservationMessage) {
            reservationMessage.innerHTML = '<strong>Guardamos tu pedido por 6 horas</strong><span>Así tenés tiempo para acercarte con tranquilidad. Después liberaremos los productos para otras personas.</span>';
        }
        elements.modalContent.querySelector('[data-payment-footnote]')?.replaceChildren(
            document.createTextNode('Elegí la forma de pago que te resulte más cómoda.')
        );
        const checkoutButton = elements.modalContent.querySelector('#checkout-form button[type="submit"]');
        if (checkoutButton) checkoutButton.textContent = 'CONFIRMAR PEDIDO';
    }

    function checkoutSteps(activeStep) {
        return `
            <ol class="checkout-steps" aria-label="Progreso del pedido">
                ${['Tus datos', 'Forma de pago', 'Confirmación'].map((label, index) => {
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

    async function apiJson(payload, retried = false) {
        const response = await fetch(app.api_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': app.csrf_token,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                ...payload,
                csrf_token: app.csrf_token,
            }),
        });
        const responseText = await response.text();
        let data;
        try {
            data = JSON.parse(responseText);
        } catch {
            throw new Error('No pudimos comunicarnos con el servidor. Intentá nuevamente.');
        }
        const sessionExpired = response.status === 401
            && /sesión venció|sesion vencio/i.test(String(data?.error || ''));
        if (sessionExpired && !retried) {
            await refreshCsrfToken();
            return apiJson(payload, true);
        }
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'No pudimos completar la operación.');
        }
        return data;
    }

    async function createOrder(form) {
        const button = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector('#checkout-error');
        const formData = new FormData(form);
        const customerName = String(formData.get('name') || '').trim();
        const customerPhone = String(formData.get('phone') || '').replace(/\D+/g, '');
        // La tienda opera con transferencia como único medio de pago web.
        const paymentMethod = 'bank_transfer';
        if (customerName.trim().split(/\s+/).length < 2 || customerPhone.length < 8) {
            errorBox.hidden = false;
            errorBox.textContent = 'Completá Nombre y Apellido y un WhatsApp válido para crear el pedido.';
            form.querySelector(customerName.length < 2
                ? '[name="name"]'
                : '[name="phone"]')?.focus();
            return;
        }
        errorBox.hidden = true;
        persistCustomer(customerName, String(formData.get('phone') || '').trim(), '');
        button.disabled = true;
        button.textContent = 'PREPARANDO TRANSFERENCIA…';
        try {
            const data = await apiJson({
                action: 'create_order',
                channel: 'web',
                payment_method: paymentMethod,
                customer: {
                    name: formData.get('name'),
                    email: '',
                    phone: formData.get('phone'),
                },
                items: cartItems().map(item => ({
                    variant_id: Number(item.variant.id),
                    quantity: item.quantity,
                })),
            });
            state.order = data.order;
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
            toast(error.message);
            await refreshCatalog();
            button.disabled = false;
            button.textContent = 'CONFIRMAR PEDIDO';
        }
    }

    function formatLocalDeadline(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return match ? `${match[3]}/${match[2]} a las ${match[4]}:${match[5]}` : String(value || '');
    }

    function showTransferConfirmation(order, whatsapp) {
        const alias = order.bank?.alias || 'Pendiente de configurar';
        const holder = order.bank?.holder || 'Laboratorio Digital';
        const total = money(Number(order.total_cents));
        openModal(`
            ${checkoutSteps(3)}
            <h2 id="modal-title">¡Gracias por tu pedido!</h2>
            <p class="checkout-lead">Tu pedido <strong>${escapeHtml(order.public_number)}</strong> ingresó correctamente.</p>
            <div class="payment-focus">
                <span>Para confirmarlo, transferí</span>
                <strong class="payment-amount">${total}</strong>
                <span>a nombre de ${escapeHtml(holder)}</span>
            </div>
            <dl class="bank-details">
                <div><dt>Alias</dt><dd>${escapeHtml(alias)} <button class="copy-button" type="button" data-copy-bank="${escapeHtml(alias)}" aria-label="Copiar alias">⧉</button></dd></div>
            </dl>
            <div class="payment-instructions">
                <strong>Un último paso, muy simple</strong>
                <span>Cuando hagas la transferencia, escribinos por WhatsApp para avisarnos.</span>
            </div>
            <a class="primary-button button-link" href="${escapeHtml(whatsapp)}" target="_blank" rel="noopener" data-whatsapp-order-complete>AVISAR TRANSFERENCIA POR WHATSAPP</a>
            <button class="secondary-button" type="button" data-finish-order>VOLVER A LA TIENDA</button>
        `);
    }

    function showCashConfirmationSixHours(order) {
        openModal(`
            ${checkoutSteps(3)}
            <h2 id="modal-title">¡Gracias por tu pedido!</h2>
            <p class="checkout-lead">Tu pedido <strong>${escapeHtml(order.public_number)}</strong> ingresó correctamente.</p>
            <div class="cash-confirmation">
                <span>Elegiste pagar al retirar</span>
                <strong>Guardamos estos productos para vos durante 6 horas</strong>
                <span>Hasta el ${escapeHtml(formatLocalDeadline(order.payment_deadline_at))}</span>
            </div>
            <div class="cash-expiry-explanation">
                <strong>Te esperamos con gusto dentro de ese plazo.</strong>
                <p>Después de las 6 horas, liberaremos la mercadería para que otras personas también puedan aprovecharla.</p>
            </div>
            ${order.pickup_address ? `<p>Retiro en: <strong>${escapeHtml(order.pickup_address)}</strong></p>` : ''}
            <button class="primary-button" type="button" data-finish-order>ENTENDIDO</button>
        `);
    }

    function showCashConfirmation(order) {
        openModal(`
            <p class="checkout-lead"><strong>¡Gracias por tu compra!</strong> Tu pedido ya quedó registrado.</p>
            ${checkoutSteps(3)}
            <h2 id="modal-title">PAGO EN EFECTIVO</h2>
            <div class="cash-confirmation">
                <span>Tu pedido ${escapeHtml(order.public_number)} quedó reservado</span>
                <strong>Disponible para retirar durante 2 horas</strong>
                <span>Hasta el ${escapeHtml(formatLocalDeadline(order.payment_deadline_at))}</span>
            </div>
            <div class="cash-expiry-explanation">
                <strong>Te esperamos para retirar y pagar en efectivo antes de ese horario.</strong>
                <p>Al finalizar el plazo, liberaremos los productos nuevamente para que otras personas puedan pedirlos.</p>
            </div>
            ${order.pickup_address ? `<p>Retiro en: <strong>${escapeHtml(order.pickup_address)}</strong></p>` : ''}
            <p class="checkout-footnote">Te esperamos en el local dentro del horario indicado.</p>
            <button class="primary-button" type="button" data-finish-order>ENTENDIDO</button>
        `);
    }

    function showPayment(order) {
        const alias = order.bank?.alias || 'Pendiente de configurar';
        const cbu = order.bank?.cbu || 'Pendiente de configurar';
        const paymentUrl = safePageUrl(order.payment_url);
        const maxMegabytes = Math.max(1, Math.round(Number(app.proof_max_bytes || 8388608) / 1048576));
        const aiCopy = app.receipt_ai_enabled
            ? 'La revisión automática comprobará que parezca una transferencia realizada y comparará importe y destinatario. La acreditación se confirma manualmente.'
            : 'Comprobaremos el formato del archivo y revisaremos el pago manualmente antes de aprobarlo.';
        openModal(`
            ${checkoutSteps(2)}
            <h2 id="modal-title">HACÉ LA TRANSFERENCIA</h2>
            <div class="payment-focus">
                <span>Transferí exactamente</span>
                <strong class="payment-amount">${money(Number(order.total_cents))}</strong>
                <span>a nombre de ${escapeHtml(order.bank?.holder || 'Laboratorio Digital')}</span>
            </div>
            <dl class="bank-details">
                <div><dt>Alias</dt><dd>${escapeHtml(alias)} <button class="copy-button" type="button" data-copy-bank="${escapeHtml(alias)}" aria-label="Copiar alias" title="Copiar alias">⧉</button></dd></div>
                <div><dt>CBU</dt><dd>${escapeHtml(cbu)} ${order.bank?.cbu ? `<button class="copy-button" type="button" data-copy-bank="${escapeHtml(cbu)}" aria-label="Copiar CBU" title="Copiar CBU">⧉</button>` : ''}</dd></div>
            </dl>
            <div class="payment-instructions">
                <strong>Después de transferir:</strong>
                <span>Descargá o capturá el comprobante del banco.</span>
                <span>Adjuntalo abajo para reservar el stock.</span>
            </div>
            <form id="proof-form">
                <div class="proof-section-head"><span>3</span><strong>Comprobante</strong></div>
                <label class="proof-drop" for="proof-file">
                    <strong>Adjuntá el comprobante</strong>
                    <span>JPG, PNG o PDF · máximo ${maxMegabytes} MB</span>
                    <input id="proof-file" name="proof" type="file" accept="image/jpeg,image/png,application/pdf" required>
                </label>
                <div class="proof-file-meta" id="proof-file-meta" aria-live="polite">Todavía no elegiste un archivo.</div>
                <p class="proof-ai-note">${escapeHtml(aiCopy)}</p>
                <p class="form-error" id="proof-error" role="alert" hidden></p>
                <p class="proof-processing" id="proof-processing" role="status" hidden></p>
                <button class="primary-button" type="submit" disabled>
                    ENVIAR COMPROBANTE
                </button>
            </form>
            ${paymentUrl ? `
                <a class="checkout-later" href="${escapeHtml(paymentUrl)}">Guardar para continuar más tarde</a>
            ` : ''}
        `);
    }

    function proofFileSelected(input) {
        const form = input.form;
        const file = input.files[0];
        const button = form?.querySelector('button[type="submit"]');
        const meta = form?.querySelector('#proof-file-meta');
        const errorBox = form?.querySelector('#proof-error');
        const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        const maxBytes = Number(app.proof_max_bytes || 8388608);
        if (!button || !meta || !errorBox) {
            return;
        }
        errorBox.hidden = true;
        button.disabled = true;
        if (!file) {
            meta.textContent = 'Todavía no elegiste un archivo.';
            return;
        }
        if (!allowed.includes(file.type) || file.size > maxBytes) {
            input.value = '';
            meta.textContent = 'Archivo no seleccionado.';
            errorBox.hidden = false;
            errorBox.textContent = !allowed.includes(file.type)
                ? 'Elegí una imagen JPG, PNG o un archivo PDF.'
                : `El archivo supera el máximo de ${Math.max(1, Math.round(maxBytes / 1048576))} MB.`;
            return;
        }
        meta.innerHTML = `<strong>${escapeHtml(file.name)}</strong><span>${(file.size / 1048576).toFixed(1)} MB · listo para enviar</span>`;
        button.disabled = false;
    }

    async function uploadProof(form) {
        const button = form.querySelector('button[type="submit"]');
        const errorBox = form.querySelector('#proof-error');
        const processing = form.querySelector('#proof-processing');
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
        button.textContent = app.receipt_ai_enabled ? 'REVISANDO COMPROBANTE…' : 'ENVIANDO COMPROBANTE…';
        errorBox.hidden = true;
        processing.hidden = false;
        processing.textContent = app.receipt_ai_enabled
            ? 'Estamos comprobando el tipo de documento, el importe y el destinatario. Puede tardar unos segundos.'
            : 'Estamos guardando el archivo de forma segura.';

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
            errorBox.hidden = false;
            errorBox.textContent = error.message;
            processing.hidden = true;
            button.disabled = false;
            button.textContent = 'ENVIAR COMPROBANTE';
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
            `Soy ${order.customer_name || 'el cliente'}.`,
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
        const aiStatus = result.prevalidation_status === 'prevalidated'
            ? 'El comprobante coincide de forma preliminar con el importe y el destinatario.'
            : 'Recibimos el comprobante y revisaremos la acreditación.';
        openModal(`
            <p class="checkout-lead"><strong>¡Gracias por tu compra!</strong> Recibimos tu pedido y el comprobante.</p>
            ${checkoutSteps(3)}
            <h2 id="modal-title">¡LISTO!</h2>
            <div class="success-box">
                ${escapeHtml(aiStatus)} El stock quedó reservado.
            </div>
            <p>
                Pedido <strong>${escapeHtml(result.public_number)}</strong><br>
                Te avisaremos por WhatsApp cuando esté aprobado y listo para retirar.
            </p>
            <button class="primary-button" type="button" data-finish-order>LISTO</button>
        `);
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
        state.query = '';
        state.remoteSearchIds = new Set();
        elements.search.value = '';
        elements.search.blur();
        renderCatalog();
    }

    document.addEventListener('click', event => {
        const copyBank = event.target.closest('[data-copy-bank]');
        if (copyBank) {
            navigator.clipboard.writeText(copyBank.dataset.copyBank)
                .then(() => toast('Dato bancario copiado.'))
                .catch(() => window.prompt('Copiá este dato:', copyBank.dataset.copyBank));
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
            state.remoteSearchIds = new Set();
            elements.search.value = '';
            setCategoryMenuOpen(false);
            elements.categoryToggle.setAttribute('aria-expanded', 'false');
            elements.categoryMenu.setAttribute('aria-expanded', 'false');
            window.history.pushState({ catalog: true }, '', window.location.href);
            renderCategories();
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
            return;
        }

        if (event.target.closest('[data-show-all-products]')) {
            state.category = '';
            state.showAll = true;
            window.history.pushState({ catalog: true }, '', window.location.href);
            renderCategories();
            renderCatalog();
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

    document.addEventListener('change', event => {
        if (event.target.matches('[data-quantity-input]')) {
            setQuantity(
                Number(event.target.dataset.quantityInput),
                Number(event.target.value)
            );
        }
        if (event.target.matches('input[name="payment_method"]')) {
            const warning = document.getElementById('cash-reservation-warning');
            if (warning) {
                warning.hidden = event.target.value !== 'cash';
            }
            const footnote = document.querySelector('[data-payment-footnote]');
            if (footnote) {
                footnote.textContent = event.target.value === 'cash'
                    ? 'Al confirmar, guardamos el stock para vos durante 6 horas.'
                    : 'Elegí la forma de pago que te resulte más cómoda.';
            }
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
        state.searchActive = Boolean(state.query.trim());
        if (state.searchActive) {
            state.category = '';
            renderCategories();
        }
        state.openedProductId = null;
        syncProductUrl(null);
        scheduleCodeSearch(state.query);
        renderCatalog();
    });
    function closeMobileCart({ returnToCatalog = false } = {}) {
        elements.orderPanel.classList.remove('mobile-open');
        if (returnToCatalog) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function openMobileCart() {
        elements.orderPanel.classList.add('mobile-open');
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
        state.category = '';
        state.showAll = false;
        state.query = '';
        state.searchActive = false;
        state.openedProductId = null;
        elements.search.value = '';
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

    renderCategories();
    renderCatalog();
    renderCart();
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
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshCatalog();
        }
    }, 2000);
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
