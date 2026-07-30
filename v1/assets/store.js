(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('app-data').textContent);
    let products = Array.isArray(app.products) ? app.products : [];
    const state = {
        category: '',
        query: '',
        cart: new Map(),
        order: null,
    };

    const elements = {
        categories: document.getElementById('category-list'),
        search: document.getElementById('product-search'),
        suggestions: document.getElementById('search-suggestions'),
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
    function rebuildVariantIndex() {
        variantIndex.clear();
        products.forEach(product => {
            product.variants.forEach(variant => {
                variantIndex.set(Number(variant.id), { product, variant });
            });
        });
    }
    rebuildVariantIndex();

    async function refreshCatalog() {
        if (state.order) {
            return;
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
                toast('Actualizamos el pedido porque cambió el stock disponible.');
            }
        } catch {
            // La próxima actualización vuelve a intentarlo sin interrumpir la compra.
        }
    }

    function productSearchText(product) {
        return fold([
            product.name,
            product.description,
            product.category?.name,
            ...product.variants.map(variant => variant.name),
        ].join(' '));
    }

    function filteredProducts() {
        const query = fold(state.query);
        return products.filter(product => {
            const matchesCategory = !state.category
                || product.category?.slug === state.category;
            return matchesCategory && productSearchText(product).includes(query);
        });
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
        renderCatalog();
        renderCart();
    }

    function renderCategories() {
        const seen = new Map();
        products.forEach(product => {
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
    }

    function productImage(product, className) {
        const image = safeImage(product.image_path);
        return image
            ? `<img class="${className}" src="${escapeHtml(image)}" alt="">`
            : `<div class="${className}-placeholder">SIN FOTO</div>`;
    }

    function priceRange(product) {
        const prices = product.variants.map(variant => Number(variant.price_cents));
        const minimum = Math.min(...prices);
        const maximum = Math.max(...prices);
        return minimum === maximum
            ? money(minimum)
            : `${money(minimum)} – ${money(maximum)}`;
    }

    function renderCatalog() {
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

        elements.results.innerHTML = matches.map(product => `
            <article class="product-row" id="product-${Number(product.id)}">
                <div>${productImage(product, 'product-image')}</div>
                <div>
                    <div class="product-head">
                        <div>
                            <span class="product-category">${escapeHtml(product.category?.name || 'Sin categoría')}</span>
                            <h2>${escapeHtml(product.name)}</h2>
                            <button
                                class="description-button"
                                type="button"
                                data-description="${Number(product.id)}"
                            >Ver descripción</button>
                        </div>
                        <strong class="product-price">${priceRange(product)}</strong>
                    </div>
                    <div class="variant-list">
                        ${product.variants.map(variant => {
                            const quantity = cartQuantity(variant.id);
                            const available = visibleAvailable(variant);
                            return `
                                <div class="variant-row">
                                    <div>
                                        <strong>${escapeHtml(variant.name)}</strong>
                                        <div class="variant-stock ${available ? '' : 'none'}">
                                            ${available ? `${available} disponibles` : 'Sin stock'}
                                            ${quantity ? ` · ${quantity} en tu pedido` : ''}
                                        </div>
                                    </div>
                                    <span class="variant-price">${money(Number(variant.price_cents))}</span>
                                    <div class="quantity-control">
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
                                            aria-label="Cantidad de ${escapeHtml(product.name)} ${escapeHtml(variant.name)}"
                                        >
                                        <button
                                            type="button"
                                            data-quantity="${Number(variant.id)}"
                                            data-value="${quantity + 1}"
                                            ${available < 1 ? 'disabled' : ''}
                                            aria-label="Agregar una unidad"
                                        >+</button>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            </article>
        `).join('');
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
        elements.checkout.disabled = items.length === 0;
        elements.cartLines.innerHTML = items.length ? items.map(item => `
            <div class="cart-line">
                <div class="cart-line-head">
                    <div>
                        <strong>${escapeHtml(item.product.name)}</strong><br>
                        <small>${escapeHtml(item.variant.name)}</small>
                    </div>
                    <strong>${money(item.lineTotal)}</strong>
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
                            aria-label="Cantidad de ${escapeHtml(item.product.name)} ${escapeHtml(item.variant.name)}"
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

    function showSuggestions() {
        const query = state.query.trim();
        if (!query) {
            closeSuggestions();
            return;
        }
        const matches = filteredProducts().slice(0, 6);
        elements.suggestions.innerHTML = matches.length ? matches.map(product => {
            const stock = product.variants.reduce(
                (sum, variant) => sum + visibleAvailable(variant),
                0
            );
            return `
                <button
                    class="suggestion-button"
                    type="button"
                    role="option"
                    data-suggestion="${Number(product.id)}"
                >
                    ${productImage(product, 'suggestion')}
                    <span>
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>${escapeHtml(product.category?.name || '')} · ${stock} disponibles</small>
                    </span>
                    <strong>${priceRange(product)}</strong>
                </button>
            `;
        }).join('') : '<p class="empty-copy">No encontramos coincidencias.</p>';
        elements.suggestions.classList.add('open');
    }

    function closeSuggestions() {
        elements.suggestions.classList.remove('open');
        elements.suggestions.innerHTML = '';
    }

    function selectSuggestion(productId) {
        const product = products.find(item => Number(item.id) === Number(productId));
        if (!product) {
            return;
        }
        state.category = '';
        state.query = product.name;
        elements.search.value = '';
        closeSuggestions();
        renderCategories();
        renderCatalog();
        document.getElementById(`product-${Number(product.id)}`)?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
        state.query = '';
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

    function showDescription(productId) {
        const product = products.find(item => Number(item.id) === Number(productId));
        if (!product) {
            return;
        }
        openModal(`
            <h2 id="modal-title">${escapeHtml(product.name)}</h2>
            ${safeImage(product.image_path)
                ? `<img class="product-image" src="${escapeHtml(safeImage(product.image_path))}" alt="" style="width:100%;height:260px;margin-bottom:14px">`
                : ''}
            <p>${escapeHtml(product.description || 'Este producto todavía no tiene descripción.')}</p>
            <div class="notice">
                <strong>${priceRange(product)}</strong><br>
                ${product.variants.map(variant => (
                    `${escapeHtml(variant.name)}: ${visibleAvailable(variant)} disponibles`
                )).join('<br>')}
            </div>
        `);
    }

    function showCheckout() {
        const items = cartItems();
        if (!items.length) {
            return;
        }
        const total = items.reduce((sum, item) => sum + item.lineTotal, 0);
        openModal(`
            <h2 id="modal-title">CONFIRMAR PEDIDO</h2>
            <div class="checkout-lines">
                ${items.map(item => `
                    <div class="checkout-line">
                        <span>${item.quantity} × ${escapeHtml(item.product.name)} · ${escapeHtml(item.variant.name)}</span>
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
                    Email para recibir la confirmación
                    <input name="email" type="email" required autocomplete="email">
                </label>
                <label>
                    WhatsApp (opcional)
                    <input name="phone" type="tel" autocomplete="tel">
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
            showPayment(data.order);
        } catch (error) {
            toast(error.message);
            button.disabled = false;
            button.textContent = 'CREAR PEDIDO';
        }
    }

    function showPayment(order) {
        const alias = order.bank?.alias || 'Pendiente de configurar';
        const cbu = order.bank?.cbu || 'Pendiente de configurar';
        const paymentUrl = safePageUrl(order.payment_url);
        openModal(`
            <h2 id="modal-title">PEDIDO ${escapeHtml(order.public_number)}</h2>
            <div class="success-box">
                Tu pedido fue creado. Te enviaremos una copia al email informado.
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
                    También te enviamos por email un enlace personal para retomar
                    esta carga más tarde.
                </p>
                <a class="secondary-button" href="${escapeHtml(paymentUrl)}">
                    ABRIR SEGUIMIENTO DEL PEDIDO
                </a>
            ` : ''}
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
            `- ${item.quantity} x ${item.product.name} · ${item.variant.name} (${money(item.lineTotal)})`
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
                Te avisaremos por email cuando esté aprobado y listo para retirar.
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

    document.addEventListener('click', event => {
        const category = event.target.closest('[data-category]');
        if (category) {
            state.category = category.dataset.category;
            state.query = '';
            elements.search.value = '';
            closeSuggestions();
            renderCategories();
            renderCatalog();
            window.scrollTo({ top: 0, behavior: 'smooth' });
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

        const suggestion = event.target.closest('[data-suggestion]');
        if (suggestion) {
            selectSuggestion(Number(suggestion.dataset.suggestion));
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
        renderCatalog();
        showSuggestions();
    });
    elements.search.addEventListener('focus', showSuggestions);
    elements.search.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeSuggestions();
            elements.search.blur();
        }
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.search-wrap')) {
            closeSuggestions();
        }
    });
    elements.checkout.addEventListener('click', showCheckout);
    elements.mobileCart.addEventListener('click', () => {
        elements.orderPanel.classList.add('mobile-open');
    });
    elements.closeMobileCart.addEventListener('click', () => {
        elements.orderPanel.classList.remove('mobile-open');
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
})();
