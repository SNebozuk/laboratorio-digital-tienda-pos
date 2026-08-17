(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('admin-app-data').textContent);
    const state = {
        products: [],
        featuredProductIds: new Set(),
        categories: [],
        orders: [],
        deliverySlots: [],
        pendingDeliveryOrderId: 0,
        pendingDeliveryOrderIds: [],
        deliveryQuery: '',
        orderQuery: '',
        orderStatus: '',
        orderChannel: '',
        orderPayment: '',
        orderDateRange: '',
        selectedOrderIds: new Set(),
        selectedProductIds: new Set(),
        productCategoryId: '',
        productAvailability: '',
        productVisibility: '',
        showArchivedOrders: false,
        settings: null,
        sizeGuide: { intro: '', rows: [] },
        users: [],
        invitations: [],
        pendingInvitationCount: 0,
        newOrderCount: 0,
        posCart: new Map(),
        posQuery: '',
        posProductId: null,
        pendingBarcode: '',
        barcodeBuffer: '',
        barcodeStartedAt: 0,
        barcodeLastAt: 0,
        barcodeTarget: null,
        barcodeOriginalValue: '',
        posCartRestored: false,
        posChangedAvailability: new Set(),
        posStockConflicts: new Set(),
        editOrder: null,
        customerHistoryName: '',
        customerHistoryChildOpen: false,
        view: 'orders',
    };

    const money = cents => new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        maximumFractionDigits: 0,
    }).format(Number(cents || 0) / 100);

    // SQLite guarda los timestamps en UTC. Toda fecha visible se expresa en hora argentina.
    const argentinaDateParts = value => {
        const source = String(value || '').trim();
        if (!source) return { date: '', time: '' };
        const normalized = source.replace(' ', 'T');
        const instant = /(?:Z|[+-]\d{2}:?\d{2})$/i.test(normalized)
            ? normalized
            : `${normalized}Z`;
        const date = new Date(instant);
        if (Number.isNaN(date.getTime())) return { date: source, time: '' };
        const parts = new Intl.DateTimeFormat('es-AR', {
            timeZone: 'America/Argentina/Buenos_Aires',
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hourCycle: 'h23',
        }).formatToParts(date).reduce((result, part) => ({ ...result, [part.type]: part.value }), {});
        return {
            date: `${parts.day} ${parts.month} ${parts.year}`,
            time: `${parts.hour}:${parts.minute}`,
        };
    };

    const argentinaDateLabel = value => {
        const parts = argentinaDateParts(value);
        return [parts.date, parts.time].filter(Boolean).join(' · ');
    };

    const posProductPrice = product => {
        const prices = product.variants
            .filter(variant => variant.active)
            .map(variant => Number(variant.price_cents));
        const minimum = Math.min(...prices);
        const maximum = Math.max(...prices);
        return minimum === maximum ? money(minimum) : `${money(minimum)} a ${money(maximum)}`;
    };

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
        return url.startsWith('/') ? url : '';
    };

    const elements = {
        modal: document.getElementById('modal'),
        modalContent: document.getElementById('modal-content'),
        toast: document.getElementById('toast'),
        productList: document.getElementById('admin-product-list'),
        productSearch: document.getElementById('admin-product-search'),
        productSearchShare: document.getElementById('copy-product-search-link'),
        categoryTree: document.getElementById('category-admin-tree'),
        posSearch: document.getElementById('pos-search'),
        posSuggestions: document.getElementById('pos-suggestions'),
        posProducts: document.getElementById('pos-products'),
        posCartLines: document.getElementById('pos-cart-lines'),
        posTotal: document.getElementById('pos-total'),
        posClearCart: document.getElementById('pos-clear-cart'),
        completeSale: document.getElementById('complete-sale-button'),
        orderList: document.getElementById('order-list'),
        deliverySlots: document.getElementById('delivery-slots'),
        deliveryCopyGuide: document.getElementById('delivery-copy-guide'),
        deliverySearch: document.getElementById('delivery-search'),
        openOrdersCount: document.getElementById('open-orders-count'),
        orderSearch: document.getElementById('order-search'),
        orderChannelFilter: document.getElementById('order-channel-filter'),
        orderPaymentFilter: document.getElementById('order-payment-filter'),
        orderDateFilter: document.getElementById('order-date-filter'),
        showArchivedOrders: document.getElementById('show-archived-orders'),
        bulkOrderAction: document.getElementById('bulk-order-action'),
        userList: document.getElementById('user-list'),
        sizeGuideIntro: document.getElementById('size-guide-intro'),
        sizeGuideRows: document.getElementById('size-guide-rows'),
        mobileView: document.getElementById('mobile-view'),
        invitationList: document.getElementById('invitation-list'),
        invitationsBadge: document.getElementById('invitations-badge'),
        ordersBadge: document.getElementById('orders-badge'),
    };
    const POS_CART_STORAGE_KEY = `laboratorio-digital:pos-cart:v1:${Number(app.user?.id || 0)}`;
    const POS_CUSTOMER_STORAGE_KEY = `laboratorio-digital:pos-customer:v1:${Number(app.user?.id || 0)}`;
    const quickUpdateTimers = new Map();
    let automaticRefreshRunning = false;
    let quickUpdateInFlight = 0;
    let productActionsMenuPauseUntil = 0;
    let invitationBadgeRefreshAt = 0;
    let orderBadgeRefreshAt = 0;

    async function apiGet(action, parameters = {}) {
        const url = new URL(app.api_url, window.location.href);
        url.searchParams.set('action', action);
        Object.entries(parameters).forEach(([key, value]) => {
            url.searchParams.set(key, String(value));
        });
        url.searchParams.set('_sync', String(Date.now()));
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'No pudimos completar la consulta.');
        }
        return data;
    }

    async function apiPost(payload) {
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
        if (data.csrf_token) {
            app.csrf_token = data.csrf_token;
        }
        return data;
    }

    async function uploadProductImage(file) {
        await validateProductImage(file);
        const payload = new FormData();
        payload.append('action', 'product_image_upload');
        payload.append('csrf_token', app.csrf_token);
        payload.append('image', file);
        const response = await fetch(app.api_url, {
            method: 'POST',
            headers: { 'X-CSRF-Token': app.csrf_token },
            body: payload,
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'No pudimos subir la foto.');
        }
        return data.image_path;
    }

    async function validateProductImage(file) {
        if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) {
            throw new Error('Elegí una imagen JPG, PNG o WebP.');
        }
        if (file.size > 8 * 1024 * 1024) {
            throw new Error('La foto supera el límite de 8 MB.');
        }
    }

    function showSelectedImage(input, file) {
        if (!input || !file) return;
        const container = input.closest('label');
        let preview = container?.querySelector('[data-image-preview]');
        if (!preview) {
            preview = document.createElement('img');
            preview.dataset.imagePreview = '1';
            preview.className = input.classList.contains('variant-image-file')
                ? 'variant-image-preview'
                : 'product-editor-image-preview';
            container?.appendChild(preview);
        }
        preview.src = URL.createObjectURL(file);
        preview.alt = 'Vista previa de la foto seleccionada';
    }

    function toast(message) {
        if (!elements.toast) {
            return;
        }
        elements.toast.textContent = message;
        elements.toast.classList.add('open');
        window.setTimeout(() => elements.toast.classList.remove('open'), 3600);
    }

    function openModal(html) {
        elements.modalContent.innerHTML = html;
        elements.modal.classList.add('open');
        elements.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (state.customerHistoryChildOpen && state.customerHistoryName) {
            const customerName = state.customerHistoryName;
            state.customerHistoryChildOpen = false;
            showCustomerHistory(customerName);
            return;
        }
        elements.modal.classList.remove('open');
        elements.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        state.editOrder = null;
        state.customerHistoryName = '';
        state.customerHistoryChildOpen = false;
    }

    async function authenticate(form, action) {
        const button = form.querySelector('button[type="submit"]');
        const formData = new FormData(form);
        button.disabled = true;
        try {
            await apiPost({
                action,
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                setup_token: formData.get('setup_token'),
            });
            window.location.reload();
        } catch (error) {
            toast(error.message);
            button.disabled = false;
        }
    }

    function showView(view) {
        const availableViews = new Set(['orders', 'deliveries', 'products', 'categories', 'size-guide', 'contact', 'design', 'invitations', 'whatsapp', 'users', 'settings', 'maintenance']);
        if (!availableViews.has(view) || !document.getElementById(`view-${view}`)) {
            view = 'orders';
        }
        state.view = view;
        if (!document.querySelector('.pos-page')) {
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.history.replaceState({ adminView: view }, '', url);
        }
        document.querySelectorAll('.admin-view').forEach(section => {
            section.classList.toggle('active', section.id === `view-${view}`);
        });
        document.querySelector('.admin-shell')?.classList.toggle('delivery-workspace', view === 'deliveries');
        document.querySelectorAll('[data-view]').forEach(button => {
            button.classList.toggle('active', button.dataset.view === view);
        });
        if (elements.mobileView) {
            elements.mobileView.value = view;
        }
        if (view === 'orders') {
            loadOrders().then(markOrdersSeen);
        }
        if (view === 'deliveries') {
            loadDeliverySlots();
        }
        if (view === 'settings') {
            loadSettings();
        }
        if (view === 'maintenance') {
            loadMaintenance();
        }
        if (view === 'contact') {
            loadContact();
        }
        if (view === 'design') {
            loadDesign();
        }
        if (view === 'invitations') {
            loadInvitations();
        }
        if (view === 'whatsapp') {
            loadEmailSettings();
        }
        if (view === 'users') {
            loadUsers();
        }
        if (view === 'categories') {
            loadCategories();
        }
        if (view === 'size-guide') {
            loadSizeGuide();
        }
    }

    function invitationDate(value) {
        const parts = argentinaDateParts(value);
        return `<span>${escapeHtml(parts.date || '—')}</span>${parts.time ? `<small>${escapeHtml(parts.time)}</small>` : ''}`;
    }

    function renderInvitations() {
        if (!elements.invitationList) return;
        if (!state.invitations.length) {
            elements.invitationList.innerHTML = '<tr><td colspan="4" class="empty-copy">Todavía no hay solicitudes de invitación.</td></tr>';
            return;
        }
        elements.invitationList.innerHTML = state.invitations.map(invitation => {
            const sent = invitation.status === 'sent';
            return `<tr class="invitation-row ${sent ? 'is-sent' : 'is-pending'}">
                <td><strong>${escapeHtml(invitation.email)}</strong></td>
                <td class="invitation-date">${invitationDate(invitation.created_at)}</td>
                <td><button type="button" class="invitation-status ${sent ? 'is-sent' : 'is-pending'}" data-invitation-status="${Number(invitation.id)}" data-invitation-sent="${sent ? '0' : '1'}">${sent ? 'ENVIADA' : 'PENDIENTE'}</button></td>
                <td class="invitation-actions"><button type="button" class="invitation-copy" data-copy-invitation-email="${escapeHtml(invitation.email)}" title="Copiar email" aria-label="Copiar ${escapeHtml(invitation.email)}">⧉</button></td>
            </tr>`;
        }).join('');
    }

    function renderInvitationBadge() {
        if (!elements.invitationsBadge) return;
        const count = Number(state.pendingInvitationCount || 0);
        elements.invitationsBadge.hidden = count < 1;
        elements.invitationsBadge.textContent = String(count);
        elements.invitationsBadge.setAttribute('aria-label', `${count} solicitudes de invitación pendientes`);
    }

    async function loadInvitations() {
        if (!elements.invitationList) return;
        try {
            const data = await apiGet('invitations');
            state.invitations = data.requests || [];
            state.pendingInvitationCount = Number(data.pending_count || 0);
            renderInvitations();
            renderInvitationBadge();
        } catch (error) {
            toast(error.message);
        }
    }

    function renderOrderBadge() {
        if (!elements.ordersBadge) return;
        const count = Number(state.newOrderCount || 0);
        elements.ordersBadge.hidden = count < 1;
        elements.ordersBadge.textContent = String(count);
        elements.ordersBadge.setAttribute('aria-label', `${count} ventas nuevas desde la última revisión`);
    }

    async function loadOrderNotifications() {
        if (!elements.ordersBadge) return;
        try {
            const data = await apiGet('order_notifications');
            state.newOrderCount = Number(data.new_count || 0);
            renderOrderBadge();
        } catch (error) {
            // Una alerta no debe interrumpir el trabajo si la red se demora.
        }
    }

    async function markOrdersSeen() {
        if (!elements.ordersBadge) return;
        try {
            await apiPost({ action: 'order_notifications_seen' });
            state.newOrderCount = 0;
            renderOrderBadge();
        } catch (error) {
            // La lista permanece disponible aunque la marca de lectura falle.
        }
    }

    async function copyInvitationEmail(email) {
        try {
            await navigator.clipboard.writeText(email);
            toast('Email copiado. Ya podés pegarlo en Codex > Invitar a un amigo.');
        } catch (error) {
            const textarea = document.createElement('textarea');
            textarea.value = email;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
            toast('Email copiado.');
        }
    }

    async function loadProducts() {
        try {
            const data = await apiGet('admin_products');
            state.products = data.products;
            state.featuredProductIds = new Set((data.featured_product_ids || []).map(Number));
            const categoryData = await apiGet('admin_categories');
            state.categories = categoryData.categories;
            renderCategories();
            const adjusted = restoreOrReconcilePosCart();
            renderProducts();
            renderPos();
            renderPosCart();
            if (adjusted && state.posCartRestored) {
                toast('Recuperamos la venta pendiente y ajustamos las cantidades al stock actual.');
            }
        } catch (error) {
            toast(error.message);
        }
    }

    function productAvailabilitySnapshot(catalog) {
        const snapshot = new Map();
        catalog.forEach(product => product.variants.forEach(variant => {
            snapshot.set(Number(variant.id), Number(variant.available_stock || 0));
        }));
        return snapshot;
    }

    async function refreshPosAvailability() {
        if ((!elements.posProducts && !elements.posCartLines)
            || document.visibilityState !== 'visible') {
            return;
        }
        try {
            const data = await apiGet('admin_products');
            const nextAvailability = productAvailabilitySnapshot(data.products);
            const conflicts = new Set(state.posStockConflicts);
            state.posCart.forEach((quantity, variantId) => {
                if (Number(quantity) > Number(nextAvailability.get(Number(variantId)) || 0)) {
                    conflicts.add(Number(variantId));
                }
            });
            state.products = data.products;
            state.posStockConflicts = conflicts;
            state.posChangedAvailability = new Set(conflicts);
            restoreOrReconcilePosCart();
            renderPos();
            renderPosCart();
        } catch {
            // La próxima comprobación vuelve a intentar sin interrumpir la venta.
        }
    }

    async function markPosStockConflicts() {
        try {
            const data = await apiGet('admin_products');
            state.products = data.products;
            const index = variantIndex();
            const conflicts = new Set();
            state.posCart.forEach((quantity, variantId) => {
                const available = Number(index.get(Number(variantId))?.variant.available_stock || 0);
                if (Number(quantity) > available) {
                    conflicts.add(Number(variantId));
                }
            });
            state.posStockConflicts = conflicts;
            state.posChangedAvailability = conflicts;
            renderPos();
            renderPosCart();
            if (conflicts.size) {
                toast('Cambió la disponibilidad: los productos marcados en rojo ya no alcanzan para esta venta.');
            }
        } catch {
            toast('No pudimos actualizar la disponibilidad. Intentá finalizar nuevamente.');
        }
    }

    async function loadCategories() {
        if (!elements.categoryTree) return;
        try {
            const data = await apiGet('admin_categories');
            state.categories = data.categories;
            renderCategories();
        } catch (error) { toast(error.message); }
    }

    function flatCategories(nodes = state.categories, depth = 0, rows = []) {
        nodes.forEach(category => {
            rows.push({ ...category, depth });
            flatCategories(category.children || [], depth + 1, rows);
        });
        return rows;
    }

    function renderCategories() {
        if (!elements.categoryTree) return;
        const rows = flatCategories();
        const namesById = new Map(rows.map(item => [Number(item.id), item.name]));
        elements.categoryTree.innerHTML = rows.length ? `<p class="category-sort-help"><strong>ORDENAR CATEGORÍAS</strong><span>Arrastrá una fila y soltala sobre la línea azul. También podés moverla a otro grupo.</span></p>${rows.map(category => `
            <article class="category-admin-row" draggable="true" data-category-row="${Number(category.id)}" data-category-parent="${category.parent_id === null ? '' : Number(category.parent_id)}" style="--category-depth:${Number(category.depth)}">
                <span class="category-drag-handle" aria-hidden="true" title="Arrastrar para ordenar">⋮⋮</span>
                <div><strong>${Number(category.depth) > 0 ? '↳ ' : ''}${escapeHtml(category.name)}</strong><small><span class="category-level-badge">${category.parent_id ? 'SUBCATEGORÍA' : 'PRINCIPAL'}</span>${category.parent_id ? ` de ${escapeHtml(namesById.get(Number(category.parent_id)) || 'otra categoría')} · ` : ' · '}${Number(category.product_count)} productos${category.active ? '' : ' · inactiva'}</small></div>
                <div class="category-admin-actions"><button class="small-button" type="button" data-edit-category="${Number(category.id)}">Editar</button><button class="small-button danger-button" type="button" data-delete-category="${Number(category.id)}">Borrar</button></div>
            </article>`).join('')}` : '<p class="empty-copy">Todavía no hay categorías.</p>';
    }

    async function moveCategory(draggedId, targetId, position = 'before') {
        if (draggedId === targetId) return;
        const rows = flatCategories();
        const dragged = rows.find(item => Number(item.id) === Number(draggedId));
        const target = rows.find(item => Number(item.id) === Number(targetId));
        if (!dragged || !target) return;
        await apiPost({ action: 'category_move', category_id: draggedId, target_id: targetId, position });
        await loadProducts();
        toast('Nuevo orden de categorías guardado.');
    }

    function showCategoryForm(category = null) {
        const options = flatCategories().filter(item => Number(item.id) !== Number(category?.id || 0)).map(item => `<option value="${Number(item.id)}" ${Number(category?.parent_id) === Number(item.id) ? 'selected' : ''}>${'— '.repeat(item.depth)}${escapeHtml(item.name)}</option>`).join('');
        openModal(`<h2 id="modal-title">${category ? 'EDITAR CATEGORÍA' : 'NUEVA CATEGORÍA'}</h2><form id="category-form" data-category-id="${Number(category?.id || 0)}"><label>NOMBRE<input name="name" value="${escapeHtml(category?.name || '')}" required></label><label>CATEGORÍA SUPERIOR<select name="parent_id"><option value="">Categoría principal</option>${options}</select></label><label>ORDEN<input name="sort_order" type="number" value="${Number(category?.sort_order || 0)}"></label><label>ACTIVA<select name="active"><option value="1" ${category?.active !== false ? 'selected' : ''}>Sí</option><option value="0" ${category?.active === false ? 'selected' : ''}>No</option></select></label><div class="button-row"><button class="primary-button fit-button" type="submit">GUARDAR CATEGORÍA</button></div></form>`);
    }

    async function saveCategory(form) {
        const id = Number(form.dataset.categoryId), data = new FormData(form);
        await apiPost({ action: id ? 'category_update' : 'category_create', category_id: id || undefined, category: { name: data.get('name'), parent_id: data.get('parent_id') || null, sort_order: Number(data.get('sort_order') || 0), active: data.get('active') === '1' } });
        closeModal(); await loadCategories(); await loadProducts(); toast('Categoría guardada.');
    }

    function productSearchText(product) {
        return fold([
            product.name,
            product.category?.name,
            ...product.variants.flatMap(variant => [
                variant.name,
                variant.sku,
                variant.barcode,
            ]),
        ].join(' '));
    }

    // Conserva medidas y códigos técnicos: 20.1 y 24.1 son términos distintos.
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
        const tolerance = /\d/.test(token)
            ? 0
            : (token.length >= 7 ? 2 : token.length >= 4 ? 1 : 0);
        if (tolerance && words.some(word => (
            limitedEditDistance(token, word, tolerance) <= tolerance
        ))) {
            return weight + 15;
        }
        return -1;
    }

    function productSearchScore(product, query) {
        const tokens = searchWords(query);
        if (!tokens.length) {
            return 0;
        }
        const fields = [
            [product.name, 150],
            [product.description, 45],
            [product.category?.name, 35],
            ...product.variants.flatMap(variant => [
                [variant.name, 80],
                [variant.sku, 210],
                [variant.barcode, 260],
            ]),
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

    function rankedProducts(query, products = state.products) {
        if (!query.trim()) {
            return products;
        }
        return products
            .map(product => ({ product, score: productSearchScore(product, query) }))
            .filter(result => result.score !== null)
            .sort((left, right) => (
                right.score - left.score
                || left.product.name.localeCompare(right.product.name, 'es')
            ))
            .map(result => result.product);
    }

    function variantDisplayName(product, variant) {
        const name = String(variant?.name || '').trim();
        return product?.variants?.length === 1 && fold(name) === 'unica'
            ? ''
            : name;
    }

    function adminProductImage(product) {
        const image = safeImage(product.image_path);
        return image
            ? `<img class="product-admin-image" src="${escapeHtml(image)}" alt="">`
            : '<div class="product-admin-placeholder">SIN FOTO</div>';
    }

    function adminProductPrice(product) {
        const prices = product.variants.map(variant => Number(variant.price_cents));
        const minimum = Math.min(...prices);
        const maximum = Math.max(...prices);
        return minimum === maximum
            ? money(minimum)
            : `${money(minimum)} a ${money(maximum)}`;
    }

    function productShareUrl(productId) {
        const url = new URL(app.store_url || '/', window.location.href);
        url.searchParams.set('producto', String(Number(productId)));
        return url.href;
    }

    function productSearchShareUrl(query) {
        const url = new URL(app.store_url || '/', window.location.href);
        url.searchParams.set('buscar', String(query || '').trim());
        return url.href;
    }

    async function shareProductSearch() {
        const query = String(elements.productSearch?.value || '').trim();
        if (query.length < 3) {
            toast('Escribí al menos 3 letras para crear un enlace de búsqueda.');
            return;
        }
        const url = productSearchShareUrl(query);
        try {
            await navigator.clipboard.writeText(url);
            toast('Enlace de búsqueda copiado. Ya podés pegarlo en WhatsApp.');
        } catch {
            window.prompt('Copiá este enlace de búsqueda:', url);
        }
    }

    async function shareProduct(productId) {
        const product = state.products.find(item => Number(item.id) === Number(productId));
        if (!product) {
            return;
        }
        const url = productShareUrl(product.id);
        try {
            await navigator.clipboard.writeText(url);
            toast('Enlace del producto copiado. Ya podés pegarlo en WhatsApp.');
        } catch {
            window.prompt('Copiá este enlace del producto:', url);
        }
    }

    function renderProducts() {
        if (!elements.productList) {
            return;
        }
        const typedQuery = String(elements.productSearch?.value || '').trim();
        const query = typedQuery.length >= 3 ? typedQuery : '';
        const products = state.products.filter(product => (
            productSearchText(product).includes(query)
        ));

        elements.productList.innerHTML = products.length ? products.map(product => `
            <article class="product-admin-card">
                <header class="product-admin-head">
                    ${adminProductImage(product)}
                    <button class="product-admin-title" type="button" data-edit-product="${Number(product.id)}" title="Editar producto">
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>${escapeHtml(product.category?.name || 'Sin categoría')} · ${product.active ? 'Activo' : 'Inactivo'}</small>
                    </button>
                    <button
                        class="product-admin-variant-count"
                        type="button"
                        data-edit-product="${Number(product.id)}"
                        title="Editar variantes"
                    >
                        ${product.variants.length} ${product.variants.length === 1 ? 'variante' : 'variantes'}
                    </button>
                    <strong class="product-admin-price">${adminProductPrice(product)}</strong>
                    <div class="product-admin-actions">
                        <button class="small-button share-product-button" type="button" data-share-product="${Number(product.id)}" title="Copiar enlace del producto" aria-label="Copiar enlace de ${escapeHtml(product.name)}">&#128279;</button>
                        <button class="small-button" type="button" data-duplicate-product="${Number(product.id)}">Duplicar</button>
                    </div>
                </header>
                <div>
                    ${product.variants.map(variant => `
                        <div class="variant-admin-row">
                            <div class="variant-admin-name">
                                ${variantDisplayName(product, variant)
                                    ? `<strong>${escapeHtml(variantDisplayName(product, variant))}</strong><br>`
                                    : ''}
                                <small>${escapeHtml(variant.sku)}${variant.barcode ? ` · ${escapeHtml(variant.barcode)}` : ''}</small>
                            </div>
                            <label>
                                PRECIO
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="${Number(variant.price_cents) / 100}"
                                    data-quick-price="${Number(variant.id)}"
                                >
                            </label>
                            <label>
                                DISPONIBLE
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    value="${Number(variant.stock_on_hand)}"
                                    data-quick-stock="${Number(variant.id)}"
                                >
                            </label>
                            <div class="reserved-value">${Number(variant.stock_on_hand)} disponibles</div>
                        </div>
                    `).join('')}
                </div>
            </article>
        `).join('') : '<p class="empty-copy">No encontramos productos.</p>';
    }

    function selectedProducts() {
        return state.products.filter(product => state.selectedProductIds.has(Number(product.id)));
    }

    async function setProductsVisibility(ids, active) {
        await apiPost({ action: 'product_visibility', product_ids: ids, active });
        await loadProducts();
        toast(active ? 'Productos visibles en tienda y PDV.' : 'Productos ocultos de tienda y PDV.');
    }

    async function deleteProducts(ids) {
        if (!window.confirm(`¿Eliminar ${ids.length === 1 ? 'el producto seleccionado' : `los ${ids.length} productos seleccionados`}? Esta acción no se puede deshacer.`)) return false;
        try {
            await apiPost({ action: 'product_delete', product_ids: ids });
        } catch (error) {
            if (String(error.message || '').includes('ventas activas:')) {
                openModal(`<h2 id="modal-title">NO SE PUEDE ELIMINAR</h2><p class="empty-copy">Este producto todavía está incluido en ventas activas.</p><p class="notice"><strong>${escapeHtml(error.message)}</strong></p><p class="empty-copy">Archivá o cancelá esas ventas primero. Las ventas archivadas no impiden eliminarlo.</p><div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>ENTENDIDO</button></div>`);
                return false;
            }
            throw error;
        }
        ids.forEach(id => state.selectedProductIds.delete(Number(id)));
        await loadProducts();
        toast(ids.length === 1 ? 'Producto eliminado.' : 'Productos eliminados.');
        return true;
    }

    function showProductFilters() {
        openModal(`
            <h2 id="modal-title" class="filter-modal-title"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M10 7a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm10 10H10M6 17H4m6 0a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z"/></svg>FILTRAR PRODUCTOS</h2>
            <p class="checkout-lead">Podés combinar los filtros para encontrar exactamente lo que necesitás.</p>
            <form id="product-filters-form" class="settings-card compact-filter-card">
                <label>CATEGORÍA
                    <select name="category"><option value="">Todas las categorías</option><option value="__unassigned__" ${state.productCategoryId === '__unassigned__' ? 'selected' : ''}>Sin categoría asignada</option>${flatCategories().map(category => `<option value="${Number(category.id)}" ${Number(state.productCategoryId) === Number(category.id) ? 'selected' : ''}>${'— '.repeat(category.depth)}${escapeHtml(category.name)}</option>`).join('')}</select>
                </label>
                <input type="hidden" name="availability" value="${escapeHtml(state.productAvailability)}">
                <fieldset class="filter-button-group"><legend>DISPONIBILIDAD</legend><div>
                    <button type="button" data-product-filter-button="availability:in_stock" class="${state.productAvailability === 'in_stock' ? 'is-selected' : ''}">En stock</button>
                    <button type="button" data-product-filter-button="availability:out_of_stock" class="${state.productAvailability === 'out_of_stock' ? 'is-selected' : ''}">Fuera de stock</button>
                </div></fieldset>
                <input type="hidden" name="visibility" value="${escapeHtml(state.productVisibility)}">
                <fieldset class="filter-button-group"><legend>VISIBILIDAD EN LA TIENDA</legend><div>
                    <button type="button" data-product-filter-button="visibility:visible" class="${state.productVisibility === 'visible' ? 'is-selected' : ''}">Visibles</button>
                    <button type="button" data-product-filter-button="visibility:hidden" class="${state.productVisibility === 'hidden' ? 'is-selected' : ''}">Ocultos</button>
                </div></fieldset>
                <div class="filter-modal-actions"><button class="secondary-button" type="button" data-clear-product-filters>LIMPIAR Y CERRAR</button><button class="primary-button" type="submit">APLICAR FILTROS</button></div>
            </form>
        `);
    }

    function showFeaturedProducts() {
        const visibleProducts = state.products.filter(product => product.active !== false);
        openModal(`
            <h2 id="modal-title">PRODUCTOS DESTACADOS</h2>
            <p class="checkout-lead">Elegí hasta 8 productos visibles para mostrarlos primero en la portada de la tienda.</p>
            <form id="featured-products-form" class="featured-products-form">
                ${visibleProducts.length ? `<div class="featured-products-options">${visibleProducts.map(product => `<label><input type="checkbox" name="product_ids" value="${Number(product.id)}" ${state.featuredProductIds.has(Number(product.id)) ? 'checked' : ''}><span>${adminProductImage(product)}</span><strong>${escapeHtml(product.name)}</strong></label>`).join('')}</div>` : '<p class="empty-copy">Primero necesitás tener productos visibles en la tienda.</p>'}
                <div class="filter-modal-actions"><button class="secondary-button" type="button" data-close-modal>CANCELAR</button><button class="primary-button" type="submit" ${visibleProducts.length ? '' : 'disabled'}>GUARDAR DESTACADOS</button></div>
            </form>
        `);
    }

    function renderProducts() {
        if (!elements.productList) return;
        const query = fold(elements.productSearch?.value || '');
        const isProductVisible = product => (
            product?.active === true || product?.active === 1 || product?.active === '1'
        );
        const productMatchesFilters = product => {
            const categoryMatch = state.productCategoryId === '__unassigned__'
                ? !product.category?.id
                : (!state.productCategoryId || Number(product.category?.id) === Number(state.productCategoryId));
            const stock = (product.variants || []).some(variant => Number(variant.stock_on_hand || 0) > 0);
            const availabilityMatch = !state.productAvailability || (state.productAvailability === 'in_stock' ? stock : !stock);
            const visibilityMatch = !state.productVisibility || (state.productVisibility === 'visible' ? isProductVisible(product) : !isProductVisible(product));
            return (!query || productSearchScore(product, query) !== null)
                && categoryMatch && availabilityMatch && visibilityMatch;
        };
        const products = state.products
            .filter(productMatchesFilters)
            .sort((left, right) => query
                ? (productSearchScore(right, query) - productSearchScore(left, query)
                    || left.name.localeCompare(right.name, 'es'))
                : left.name.localeCompare(right.name, 'es'));
        const selectedCount = state.selectedProductIds.size;
        const singularSelection = selectedCount === 1;
        const visibleSelectedCount = products.filter(product => state.selectedProductIds.has(Number(product.id))).length;
        const allSelected = products.length > 0 && visibleSelectedCount === products.length;
        const toolbar = `
            <div class="product-bulk-toolbar">
                <label class="product-select-all"><input type="checkbox" id="select-all-products" ${allSelected ? 'checked' : ''}> <span>Seleccionar todo</span></label>
                <button class="secondary-button" type="button" data-open-featured-products>DESTACADOS${state.featuredProductIds.size ? ` · ${state.featuredProductIds.size}` : ''}</button>
                <button class="secondary-button" type="button" data-open-product-filters>FILTRAR${state.productCategoryId || state.productAvailability || state.productVisibility ? ' · ACTIVO' : ''}</button>
            </div>
            <div class="order-actions-bar product-actions-bar" ${selectedCount ? '' : 'hidden'}>
                <strong class="selected-orders-count product-selection-count">${selectedCount} ${singularSelection ? 'producto seleccionado' : 'productos seleccionados'}</strong>
                <label class="bulk-actions-control product-bulk-actions-control">
                    <span>ACCIONES SOBRE ${singularSelection ? 'EL PRODUCTO SELECCIONADO' : 'LOS PRODUCTOS SELECCIONADOS'}</span>
                    <select data-bulk-product-action aria-label="Acciones sobre ${singularSelection ? 'el producto seleccionado' : 'los productos seleccionados'}">
                        <option value="">Acciones</option><option value="show">Mostrar ${singularSelection ? 'Producto' : 'Productos'}</option><option value="hide">Ocultar ${singularSelection ? 'Producto' : 'Productos'}</option><option value="delete">Eliminar ${singularSelection ? 'Producto' : 'Productos'}</option>
                    </select>
                </label>
            </div>`;
        elements.productList.innerHTML = `${toolbar}${products.length ? `
            <div class="product-list-table" role="table" aria-label="Listado de productos">
                <div class="product-list-head" role="row"><span></span><span>Producto y variantes</span><span>Estado</span><span>Stock</span><span>Precio</span><span></span></div>
                ${products.map(product => {
                    const hasVariants = product.variants.length > 1;
                    const single = product.variants[0];
                    const inlineFields = single ? `<label class="product-inline-field"><input type="number" min="0" step="1" value="${single.stock_on_hand == null ? '' : Number(single.stock_on_hand)}" data-quick-stock="${Number(single.id)}" aria-label="Stock de ${escapeHtml(product.name)}"></label>
                        <label class="product-inline-field"><input type="number" min="0" step="1" value="${single.price_cents == null ? '' : Number(single.price_cents) / 100}" data-quick-price="${Number(single.id)}" aria-label="Precio de ${escapeHtml(product.name)}"></label>` : '<span></span><span></span>';
                    const visible = isProductVisible(product);
                    const featured = state.featuredProductIds.has(Number(product.id));
                    return `<div class="product-list-row ${visible ? '' : 'is-hidden'}" role="row">
                        <span><input type="checkbox" data-select-product="${Number(product.id)}" ${state.selectedProductIds.has(Number(product.id)) ? 'checked' : ''} aria-label="Seleccionar ${escapeHtml(product.name)}"></span>
                        <button class="product-table-name" type="button" data-edit-product="${Number(product.id)}">${adminProductImage(product)}<span><strong>${escapeHtml(product.name)}</strong><small>${hasVariants ? `${product.variants.length} variantes · hacé clic para editar` : 'Hacé clic para editar el producto'}</small></span></button>
                        <span class="product-visibility ${visible ? 'is-visible' : 'is-hidden'}">${visible ? 'Visible' : 'Oculto'}${featured ? '<small class="featured-product-label">Destacado</small>' : ''}</span>
                        ${inlineFields}
                        <div class="product-table-actions"><button class="small-button share-product-button" type="button" data-share-product="${Number(product.id)}" title="Copiar enlace">&#128279;</button><button class="small-button" type="button" data-duplicate-product="${Number(product.id)}">Duplicar</button><button class="small-button product-delete-button" type="button" data-delete-product="${Number(product.id)}" title="Eliminar producto" aria-label="Eliminar ${escapeHtml(product.name)}">&#128465;</button></div>
                    </div>${hasVariants ? product.variants.map(variant => {
                        const name = variantDisplayName(product, variant);
                        return `<div class="product-variant-inline-row ${product.active && variant.active ? '' : 'is-hidden'}" role="row">
                            <span></span>
                            <span class="product-inline-variant-name"><strong>${escapeHtml(name || 'Variante única')}</strong><small>${escapeHtml(variant.sku || '')}</small></span>
                            <span></span>
                            <label class="product-inline-field"><input type="number" min="0" step="1" value="${variant.stock_on_hand == null ? '' : Number(variant.stock_on_hand)}" data-quick-stock="${Number(variant.id)}" aria-label="Stock de ${escapeHtml(product.name)} ${escapeHtml(name)}"></label>
                            <label class="product-inline-field"><input type="number" min="0" step="1" value="${variant.price_cents == null ? '' : Number(variant.price_cents) / 100}" data-quick-price="${Number(variant.id)}" aria-label="Precio de ${escapeHtml(product.name)} ${escapeHtml(name)}"></label>
                            <span></span>
                        </div>`;
                    }).join('') : ''}`;
                }).join('')}
            </div>` : '<p class="empty-copy">No encontramos productos con esos filtros.</p>'}`;
    }

    function variantFormRow(variant = {}, single = false) {
        return `
            <div class="variant-form-row ${single ? 'single-variant-row' : ''}" data-variant-row data-variant-id="${Number(variant.id || 0)}" data-variant-stock-original="${Number(variant.stock_on_hand || 0)}">
                <label class="variant-name-field">
                    VARIANTE
                    <input class="variant-name" value="${escapeHtml(single ? 'Única' : (variant.name || ''))}" placeholder="Talle 1" required>
                </label>
                <label class="variant-sku-field">
                    SKU
                    <input class="variant-sku" value="${escapeHtml(variant.sku || '')}" placeholder="Opcional">
                </label>
                <label class="variant-price-field">
                    PRECIO
                    <input class="variant-price" type="number" min="0" value="${variant.price_cents == null ? '' : Number(variant.price_cents) / 100}" placeholder="Opcional">
                </label>
                <label class="variant-stock-field">
                    STOCK
                    <input class="variant-stock" type="number" min="0" value="${variant.stock_on_hand == null ? '' : Number(variant.stock_on_hand)}" placeholder="Opcional">
                </label>
                <button class="small-button danger-button" type="button" data-remove-variant>Quitar</button>
                <label class="variant-image-field">
                    FOTO DE ESTA VARIANTE
                    <input class="variant-image-file" type="file" accept="image/jpeg,image/png,image/webp">
                    <input class="variant-image-path" type="hidden" value="${escapeHtml(variant.image_path || '')}">
                    ${variant.image_path ? `<img class="variant-image-preview" data-image-preview src="${escapeHtml(variant.image_path)}" alt="Foto actual de la variante">` : '<small>Opcional: si no cargás una, se usa la foto del producto.</small>'}
                </label>
                <label class="variant-barcode-field">
                    CÓDIGO DE BARRAS
                    <input class="variant-barcode" value="${escapeHtml(variant.barcode || '')}" placeholder="Opcional">
                </label>
                <label class="variant-min-field">
                    STOCK MÍNIMO
                    <input class="variant-min" type="number" min="0" value="${Number(variant.min_stock || 0)}">
                </label>
                <label class="variant-active-field">
                    <span>ACTIVA</span>
                    <select class="variant-active">
                        <option value="1" ${variant.active !== false ? 'selected' : ''}>Sí</option>
                        <option value="0" ${variant.active === false ? 'selected' : ''}>No</option>
                    </select>
                </label>
            </div>
        `;
    }

    function showProductForm(product = null) {
        const formVariants = product?.variants?.length
            ? product.variants
            : [{ name: 'Única', active: true }];
        const singleVariant = formVariants.length === 1;
        openModal(`
            <h2 id="modal-title">${product ? 'EDITAR PRODUCTO' : 'NUEVO PRODUCTO'}</h2>
            <form id="product-form" data-product-id="${Number(product?.id || 0)}">
                <label>
                    TÍTULO COMPLETO
                    <input name="name" value="${escapeHtml(product?.name || '')}" required>
                </label>
                <label>
                    CATEGORÍA
                    <select name="category_id"><option value="">Elegí una categoría</option>${flatCategories().map(item => `<option value="${Number(item.id)}" ${Number(product?.category?.id) === Number(item.id) ? 'selected' : ''}>${'— '.repeat(item.depth)}${escapeHtml(item.name)}</option>`).join('')}</select>
                    <small>Elegí la subcategoría más específica. El árbol se mantiene desde Categorías.</small>
                </label>
                <label>
                    DESCRIPCIÓN
                    <textarea name="description" rows="4" placeholder="Visible únicamente al abrir el producto">${escapeHtml(product?.description || '')}</textarea>
                </label>
                <label class="product-image-field">
                    FOTO DEL PRODUCTO
                    <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
                    <span class="image-drop-zone" data-image-drop data-image-input="image_file">Arrastrá la foto aquí o hacé clic para elegirla</span>
                    <small>JPG, PNG o WebP · máximo 8 MB. Se sube automáticamente al alojamiento.</small>
                    ${product?.image_path ? `<img class="product-editor-image-preview" data-image-preview src="${escapeHtml(product.image_path)}" alt="Foto actual del producto">` : ''}
                </label>
                <label>
                    URL DE IMAGEN · OPCIONAL
                    <input name="image_path" value="${escapeHtml(product?.image_path || '')}" placeholder="https://...">
                </label>
                <label>
                    PRODUCTO ACTIVO
                    <select name="active">
                        <option value="1" ${product?.active !== false ? 'selected' : ''}>Sí</option>
                        <option value="0" ${product?.active === false ? 'selected' : ''}>No</option>
                    </select>
                </label>
                <h3 class="variant-section-title">${singleVariant ? 'PRECIO, STOCK E IDENTIFICACIÓN' : 'VARIANTES'}</h3>
                <div class="variant-form-list" id="variant-form-list">
                    ${formVariants.map(variant => variantFormRow(variant, singleVariant)).join('')}
                </div>
                <div class="button-row">
                    <button class="secondary-button" type="button" data-add-variant>+ VARIANTE</button>
                    <button class="primary-button fit-button" type="submit">GUARDAR PRODUCTO</button>
                </div>
                ${product ? `<div class="product-form-actions"><button type="button" data-product-visibility="show" data-product-id="${Number(product.id)}">Mostrar producto</button><button type="button" data-product-visibility="hide" data-product-id="${Number(product.id)}">Ocultar producto</button><button type="button" class="danger-button" data-delete-product="${Number(product.id)}">Eliminar producto</button></div>` : ''}
            </form>
        `);
    }

    function readProductForm(form) {
        const formData = new FormData(form);
        const variants = Array.from(form.querySelectorAll('[data-variant-row]')).map(row => ({
            id: Number(row.dataset.variantId) || undefined,
            name: row.querySelector('.variant-name').value.trim(),
            sku: row.querySelector('.variant-sku').value.trim(),
            barcode: row.querySelector('.variant-barcode').value.trim(),
            image_path: row.querySelector('.variant-image-path').value.trim(),
            price_cents: row.querySelector('.variant-price').value.trim() === '' ? null : Math.round(Number(row.querySelector('.variant-price').value) * 100),
            stock_on_hand: row.querySelector('.variant-stock').value.trim() === '' ? null : Number(row.querySelector('.variant-stock').value),
            reset_stock_reservations: row.querySelector('.variant-stock').value.trim() !== '' && Number(row.querySelector('.variant-stock').value)
                !== Number(row.dataset.variantStockOriginal || 0),
            min_stock: Number(row.querySelector('.variant-min').value),
            active: row.querySelector('.variant-active').value === '1',
        }));
        return {
            name: formData.get('name'),
            category: productCategoryName(formData.get('category_id')),
            category_id: formData.get('category_id') || null,
            description: formData.get('description'),
            image_path: formData.get('image_path'),
            active: formData.get('active') === '1',
            variants,
        };
    }

    function productCategoryName(id) {
        return flatCategories().find(item => Number(item.id) === Number(id))?.name || 'General';
    }

    async function saveProduct(form) {
        const productId = Number(form.dataset.productId);
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            const product = readProductForm(form);
            const imageFile = form.querySelector('[name="image_file"]').files[0];
            if (imageFile) {
                button.textContent = 'SUBIENDO FOTO…';
                product.image_path = await uploadProductImage(imageFile);
            }
            const variantRows = Array.from(form.querySelectorAll('[data-variant-row]'));
            for (let index = 0; index < variantRows.length; index += 1) {
                const file = variantRows[index].querySelector('.variant-image-file').files[0];
                if (!file) continue;
                button.textContent = `SUBIENDO FOTO ${index + 1}/${variantRows.length}…`;
                product.variants[index].image_path = await uploadProductImage(file);
            }
            button.textContent = 'GUARDANDO…';
            await apiPost({
                action: productId ? 'product_update' : 'product_create',
                product_id: productId || undefined,
                product,
            });
            closeModal();
            await loadProducts();
            toast(productId ? 'Producto guardado correctamente.' : 'Producto creado correctamente.');
        } catch (error) {
            toast(error.message);
            button.disabled = false;
            button.textContent = 'GUARDAR PRODUCTO';
        }
    }

    function updateVariantEditorMode() {
        const list = document.getElementById('variant-form-list');
        if (!list) return;
        const rows = Array.from(list.querySelectorAll('[data-variant-row]'));
        const single = rows.length === 1;
        rows.forEach(row => row.classList.toggle('single-variant-row', single));
        if (single) {
            rows[0].querySelector('.variant-name').value = 'Única';
        }
        const title = document.querySelector('.variant-section-title');
        if (title) title.textContent = single ? 'PRECIO, STOCK E IDENTIFICACIÓN' : 'VARIANTES';
    }

    async function quickUpdate(variantId, input) {
        const product = state.products.find(item => (
            item.variants.some(variant => Number(variant.id) === variantId)
        ));
        const variant = product?.variants.find(item => Number(item.id) === variantId);
        if (!variant) {
            return;
        }
        const priceInput = document.querySelector(`[data-quick-price="${variantId}"]`);
        const stockInput = document.querySelector(`[data-quick-stock="${variantId}"]`);
        input.disabled = true;
        quickUpdateInFlight += 1;
        try {
            await apiPost({
                action: 'variant_quick_update',
                variant_id: variantId,
                changes: {
                    price_cents: priceInput.value.trim() === '' ? null : Math.round(Number(priceInput.value) * 100),
                    stock_on_hand: stockInput.value.trim() === '' ? null : Number(stockInput.value),
                    reset_stock_reservations: Boolean(input.dataset.quickStock),
                },
            });
            await loadProducts();
            toast('Variante actualizada.');
        } catch (error) {
            toast(error.message);
            await loadProducts();
        } finally {
            quickUpdateInFlight = Math.max(0, quickUpdateInFlight - 1);
        }
    }

    function scheduleQuickUpdate(input) {
        const variantId = Number(input.dataset.quickPrice || input.dataset.quickStock);
        if (!Number.isFinite(variantId)) {
            return;
        }
        window.clearTimeout(quickUpdateTimers.get(variantId));
        quickUpdateTimers.set(variantId, window.setTimeout(() => {
            quickUpdateTimers.delete(variantId);
            quickUpdate(variantId, input);
        }, 700));
    }

    async function duplicateProduct(productId) {
        try {
            const copyImages = window.confirm(
                '¿Querés duplicar también la foto del producto y las fotos de sus variantes?\n\nAceptar: duplicar con fotos.\nCancelar: duplicar sin fotos.'
            );
            await apiPost({
                action: 'product_duplicate',
                product_id: productId,
                copy_images: copyImages,
            });
            await loadProducts();
            toast(`Producto duplicado ${copyImages ? 'con sus fotos' : 'sin fotos'}, con stock cero e inactivo.`);
        } catch (error) {
            toast(error.message);
        }
    }

    function variantIndex() {
        const index = new Map();
        state.products.forEach(product => {
            product.variants.filter(variant => variant && variant.active !== false).forEach(variant => {
                index.set(Number(variant.id), { product, variant });
            });
        });
        return index;
    }

    function allVariantIndex() {
        const index = new Map();
        state.products.forEach(product => {
            product.variants.forEach(variant => {
                index.set(Number(variant.id), { product, variant });
            });
        });
        return index;
    }

    function posQuantity(variantId) {
        return Number(state.posCart.get(Number(variantId)) || 0);
    }

    function persistPosCart() {
        try {
            if (state.posCart.size === 0) {
                localStorage.removeItem(POS_CART_STORAGE_KEY);
                return;
            }
            localStorage.setItem(POS_CART_STORAGE_KEY, JSON.stringify({
                version: 1,
                updated_at: new Date().toISOString(),
                items: Array.from(state.posCart, ([variantId, quantity]) => ({
                    variant_id: Number(variantId),
                    quantity: Number(quantity),
                })),
            }));
        } catch {
            // El POS sigue operativo aunque el navegador bloquee localStorage.
        }
    }

    function restorePosCustomer() {
        const input = document.getElementById('pos-customer');
        if (!input) return;
        try {
            input.value = localStorage.getItem(POS_CUSTOMER_STORAGE_KEY) || '';
        } catch {
            input.value = '';
        }
        input.addEventListener('input', () => {
            try {
                const value = input.value.trim();
                if (value) localStorage.setItem(POS_CUSTOMER_STORAGE_KEY, input.value);
                else localStorage.removeItem(POS_CUSTOMER_STORAGE_KEY);
            } catch {
                // El nombre sigue disponible durante esta pantalla.
            }
        });
    }

    function restoreOrReconcilePosCart(shouldPersist = true) {
        let adjusted = false;
        if (!state.posCartRestored) {
            state.posCart.clear();
            try {
                const stored = JSON.parse(
                    localStorage.getItem(POS_CART_STORAGE_KEY) || 'null'
                );
                if (stored?.version === 1 && Array.isArray(stored.items)) {
                    stored.items.forEach(item => {
                        const variantId = Number(item?.variant_id);
                        const quantity = Number(item?.quantity);
                        if (Number.isFinite(variantId) && Number.isFinite(quantity)) {
                            state.posCart.set(variantId, quantity);
                        }
                    });
                }
            } catch {
                adjusted = true;
            }
            state.posCartRestored = true;
        }

        const index = variantIndex();
        Array.from(state.posCart).forEach(([variantId, requested]) => {
            const indexed = index.get(Number(variantId));
            const maximum = Number(indexed?.variant.available_stock || 0);
            const quantity = Math.max(
                0,
                Math.min(maximum, Number(requested) || 0)
            );
            if (quantity < 1) {
                state.posCart.delete(Number(variantId));
                adjusted = true;
            } else if (quantity !== Number(requested)) {
                state.posCart.set(Number(variantId), quantity);
                adjusted = true;
            }
        });
        if (shouldPersist) {
            persistPosCart();
        }
        return adjusted;
    }

    function setPosQuantity(variantId, requested) {
        const indexed = variantIndex().get(Number(variantId));
        if (!indexed) {
            return;
        }
        const max = Number(indexed.variant.available_stock);
        const quantity = Math.max(0, Math.min(max, Number(requested) || 0));
        if (quantity) {
            state.posCart.set(Number(variantId), quantity);
        } else {
            state.posCart.delete(Number(variantId));
        }
        if (quantity <= max) {
            state.posStockConflicts.delete(Number(variantId));
        }
        persistPosCart();
        renderPos();
        renderPosCart();
        if (state.posQuery.trim()) {
            showPosSuggestions();
        }
    }

    function stockLabel(available) {
        const units = Math.max(0, Number(available) || 0);
        if (units === 0) return '<span class="stock-zero">AGOTADO</span>';
        if (units === 1) return 'Última unidad';
        return `${units} disponibles`;
    }

    function renderPos() {
        if (!elements.posProducts) {
            return;
        }
        const query = state.posQuery.trim();
        if (!query) {
            elements.posProducts.innerHTML = `
                <div class="pos-idle">
                    <strong>BUSCÁ UN PRODUCTO O ESCANEÁ SU CÓDIGO</strong>
                    <span>Los resultados aparecerán acá. Al escanear, el producto se suma directamente al carrito.</span>
                </div>`;
            return;
        }
        const products = rankedProducts(
            query,
            state.products.filter(product => product && product.active !== false)
        ).filter(product => product.variants.some(variant => (
            variant && variant.active !== false
            && (query || Number(variant.available_stock) > 0)
        )));
        elements.posProducts.innerHTML = products.length ? `
            ${query ? `<div class="pos-search-summary"><strong>${products.length}</strong> productos encontrados en todo el catálogo</div>` : ''}
            ${products.map(product => {
                const variants = product.variants.filter(variant => variant && variant.active !== false);
                if (!variants.length) {
                    return '';
                }
                const hasVariants = variants.length > 1;
                const expanded = Number(state.posProductId) === Number(product.id);
                const single = variants[0];
                const singleQuantity = single ? posQuantity(single.id) : 0;
                const singleRemaining = single ? Math.max(0, Number(single.available_stock) - singleQuantity) : 0;
                return `
            <article class="pos-product pos-result-product ${expanded ? 'pos-expanded' : ''} ${product.variants.some(variant => state.posChangedAvailability.has(Number(variant.id))) ? 'availability-changed' : ''} ${product.variants.some(variant => state.posStockConflicts.has(Number(variant.id))) ? 'stock-conflict' : ''}">
                ${safeImage(product.image_path)
                    ? `<img src="${escapeHtml(safeImage(product.image_path))}" alt="${escapeHtml(product.name)}">`
                    : '<div class="pos-product-placeholder">SIN FOTO</div>'}
                ${hasVariants
                    ? `<button class="pos-result-name pos-result-name-toggle" type="button" data-pos-open-product="${Number(product.id)}" aria-label="Mostrar variantes de ${escapeHtml(product.name)}">${escapeHtml(product.name)}</button>`
                    : `<strong class="pos-result-name">${escapeHtml(product.name)}</strong>`}
                ${hasVariants
                    ? `<button class="pos-result-meta pos-result-meta-toggle" type="button" data-pos-open-product="${Number(product.id)}" aria-label="Mostrar variantes de ${escapeHtml(product.name)}">${variants.length} variantes</button>`
                    : `<span class="pos-result-meta">${stockLabel(singleRemaining)}</span>`}
                <span class="pos-product-price">${posProductPrice(product)}</span>
                ${hasVariants
                    ? `<button class="pos-result-action pos-open-product-button" type="button" data-pos-open-product="${Number(product.id)}" aria-label="Mostrar variantes de ${escapeHtml(product.name)}"><span aria-hidden="true">${expanded ? '‹' : '›'}</span></button>`
                    : `<div class="pos-inline-quantity" aria-label="Cantidad de ${escapeHtml(product.name)}"><button type="button" data-pos-quantity="${Number(single.id)}" data-value="${Math.max(0, singleQuantity - 1)}" ${singleQuantity < 1 ? 'disabled' : ''} aria-label="Quitar una unidad">−</button><input type="number" inputmode="numeric" value="${singleQuantity}" min="0" max="${Number(single.available_stock)}" data-pos-input="${Number(single.id)}" aria-label="Cantidad de ${escapeHtml(product.name)}"><button type="button" data-pos-quantity="${Number(single.id)}" data-value="${singleQuantity + 1}" ${singleRemaining < 1 ? 'disabled' : ''} aria-label="Agregar una unidad">+</button></div>`}
                ${hasVariants ? `<div class="pos-result-variants">${variants.map(variant => {
                        const quantity = posQuantity(variant.id);
                        const remaining = Math.max(
                            0,
                            Number(variant.available_stock) - quantity
                        );
                        return `
                            <div class="pos-variant-row ${state.posChangedAvailability.has(Number(variant.id)) ? 'availability-changed' : ''} ${state.posStockConflicts.has(Number(variant.id)) ? 'stock-conflict' : ''}">
                                <span class="pos-variant-name">
                                    ${variantDisplayName(product, variant)
                                        ? `<strong>${escapeHtml(variantDisplayName(product, variant))}</strong><br>`
                                        : ''}
                                </span>
                                <span class="pos-variant-meta">
                                    <span class="pos-variant-stock">${stockLabel(remaining)}</span>
                                    <span class="pos-variant-price">${money(variant.price_cents)}</span>
                                </span>
                                <button class="pos-variant-remove" type="button" data-pos-quantity="${Number(variant.id)}" data-value="${Math.max(0, quantity - 1)}" ${quantity < 1 ? 'disabled' : ''} aria-label="Restar ${escapeHtml(variantDisplayName(product, variant) || product.name)}">−</button>
                                <input class="pos-variant-quantity" type="number" inputmode="numeric" value="${quantity}" min="0" max="${Number(variant.available_stock)}" data-pos-input="${Number(variant.id)}" aria-label="Cantidad de ${escapeHtml(variantDisplayName(product, variant) || product.name)}">
                                <button class="pos-variant-add" type="button" data-pos-quantity="${Number(variant.id)}" data-value="${quantity + 1}" ${remaining < 1 ? 'disabled' : ''} aria-label="Agregar ${escapeHtml(variantDisplayName(product, variant) || product.name)}">+</button>
                            </div>
                        `;
                    }).join('')}</div>` : ''}
            </article>
        `;}).join('')}` : '<p class="empty-copy">No encontramos productos.</p>';
    }

    function renderPosCart() {
        if (!elements.posCartLines) {
            return;
        }
        const index = variantIndex();
        const items = Array.from(state.posCart, ([variantId, quantity]) => ({
            ...index.get(variantId),
            variantId,
            quantity,
        }));
        const total = items.reduce(
            (sum, item) => sum + Number(item.variant.price_cents) * item.quantity,
            0
        );
        elements.posTotal.textContent = money(total);
        elements.completeSale.disabled = items.length === 0;
        elements.posClearCart.disabled = items.length === 0;
        const conflictNames = Array.from(state.posStockConflicts).map(variantId => {
            const indexed = index.get(Number(variantId));
            if (!indexed) return null;
            const name = variantDisplayName(indexed.product, indexed.variant);
            return name ? `${indexed.product.name} · ${name}` : indexed.product.name;
        }).filter(Boolean);
        const conflictNotice = conflictNames.length ? `
            <section class="pos-stock-change-notice" role="alert" aria-live="assertive">
                <strong>CAMBIÓ EL STOCK DE ESTA VENTA</strong>
                <p>Otra operación modificó la disponibilidad y ajustamos:</p>
                <ul>${conflictNames.map(name => `<li>${escapeHtml(name)}</li>`).join('')}</ul>
                <button type="button" data-dismiss-pos-stock-warning>ENTENDIDO</button>
            </section>` : '';
        elements.posCartLines.innerHTML = conflictNotice + (items.length ? items.map(item => `
            <div class="cart-line pos-cart-detail-row ${state.posStockConflicts.has(Number(item.variantId)) ? 'stock-conflict' : ''}">
                <div class="pos-cart-product">
                    <strong>${escapeHtml(item.product.name)}</strong>
                    ${variantDisplayName(item.product, item.variant)
                        ? `<small>${escapeHtml(variantDisplayName(item.product, item.variant))}</small>`
                        : ''}
                </div>
                <div class="quantity-control">
                    <button type="button" data-pos-quantity="${item.variantId}" data-value="${item.quantity - 1}">−</button>
                    <input type="number" value="${item.quantity}" min="0" max="${Number(item.variant.available_stock)}" data-pos-input="${item.variantId}">
                    <button type="button" data-pos-quantity="${item.variantId}" data-value="${item.quantity + 1}" ${item.quantity >= Number(item.variant.available_stock) ? 'disabled' : ''}>+</button>
                </div>
                <strong class="pos-cart-subtotal">${money(Number(item.variant.price_cents) * item.quantity)}</strong>
                <button class="pos-remove-cart-line" type="button" data-pos-quantity="${item.variantId}" data-value="0" aria-label="Eliminar ${escapeHtml(item.product.name)} del carrito">🗑</button>
            </div>
        `).join('') : '<p class="empty-copy">Sin productos.</p>');
    }

    function showPosSuggestions() {
        closePosSuggestions();
    }

    function closePosSuggestions() {
        if (!elements.posSuggestions) {
            return;
        }
        elements.posSuggestions.classList.remove('open');
        elements.posSuggestions.innerHTML = '';
    }

    function choosePosProduct(productId) {
        state.posProductId = Number(productId);
        state.posQuery = '';
        elements.posSearch.value = '';
        closePosSuggestions();
        renderPos();
    }

    function scanBarcode(value) {
        const query = fold(value.trim());
        const indexed = Array.from(variantIndex().values()).find(item => (
            fold(item.variant.barcode) === query || fold(item.variant.sku) === query
        ));
        if (!indexed) {
            return false;
        }
        const available = Number(indexed.variant.available_stock);
        if (available <= posQuantity(indexed.variant.id)) {
            toast(available > 0
                ? 'Ya agregaste todo el stock disponible de este producto.'
                : 'El producto está sin stock disponible.');
            return true;
        }
        setPosQuantity(
            Number(indexed.variant.id),
            posQuantity(indexed.variant.id) + 1
        );
        elements.posSearch.value = '';
        state.posQuery = '';
        state.posProductId = null;
        closePosSuggestions();
        renderPos();
        return true;
    }

    function resetBarcodeCapture() {
        state.barcodeBuffer = '';
        state.barcodeStartedAt = 0;
        state.barcodeLastAt = 0;
        state.barcodeTarget = null;
        state.barcodeOriginalValue = '';
    }

    function restoreInputAfterBarcodeScan() {
        const target = state.barcodeTarget;
        if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) {
            return;
        }
        target.value = state.barcodeOriginalValue;
        target.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function captureGlobalBarcode(event) {
        if (
            state.view !== 'pos'
            || elements.modal?.classList.contains('open')
            || event.isComposing
            || event.ctrlKey
            || event.altKey
            || event.metaKey
        ) {
            resetBarcodeCapture();
            return;
        }

        if (event.defaultPrevented) {
            resetBarcodeCapture();
            return;
        }

        const now = performance.now();
        if (event.key.length === 1) {
            const startsNewScan = !state.barcodeBuffer
                || now - state.barcodeLastAt > 120;
            if (startsNewScan) {
                resetBarcodeCapture();
                state.barcodeStartedAt = now;
                state.barcodeTarget = event.target;
                state.barcodeOriginalValue = (
                    event.target instanceof HTMLInputElement
                    || event.target instanceof HTMLTextAreaElement
                ) ? event.target.value : '';
            }
            state.barcodeBuffer += event.key;
            state.barcodeLastAt = now;
            return;
        }

        if (event.key !== 'Enter') {
            return;
        }

        const barcode = state.barcodeBuffer.trim();
        const duration = state.barcodeLastAt - state.barcodeStartedAt;
        const averageGap = barcode.length > 1
            ? duration / (barcode.length - 1)
            : Number.POSITIVE_INFINITY;
        const scannerSpeed = barcode.length >= 3
            && now - state.barcodeLastAt <= 150
            && averageGap <= 100;

        if (!scannerSpeed) {
            resetBarcodeCapture();
            return;
        }

        event.preventDefault();
        restoreInputAfterBarcodeScan();
        resetBarcodeCapture();
        if (!scanBarcode(barcode)) {
            offerBarcodeAssignment(barcode);
        }
    }

    function barcodeAssignmentResults(query) {
        const results = document.getElementById('barcode-assignment-results');
        if (!results) {
            return;
        }
        const products = rankedProducts(
            query,
            state.products.filter(product => product.active)
        ).slice(0, query.trim() ? 30 : 12);
        results.innerHTML = products.length ? products.map(product => `
            <article class="barcode-product">
                <header>
                    ${safeImage(product.image_path)
                        ? `<img src="${escapeHtml(safeImage(product.image_path))}" alt="">`
                        : '<span class="barcode-product-placeholder">SIN FOTO</span>'}
                    <span>
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>${escapeHtml(product.category?.name || '')}</small>
                    </span>
                </header>
                <div class="barcode-variant-list">
                    ${product.variants.filter(variant => variant.active).map(variant => `
                        <button type="button" data-assign-barcode-variant="${Number(variant.id)}">
                            <span>
                                <strong>${escapeHtml(variantDisplayName(product, variant) || 'VARIANTE ÚNICA')}</strong>
                                <small>${Number(variant.available_stock)} disponibles · ${money(variant.price_cents)}</small>
                            </span>
                            <span>ASIGNAR</span>
                        </button>
                    `).join('')}
                </div>
            </article>
        `).join('') : '<p class="empty-copy">No encontramos productos.</p>';
    }

    function offerBarcodeAssignment(barcode) {
        state.pendingBarcode = String(barcode || '').trim();
        if (state.pendingBarcode.length < 3) {
            toast('Ingresá o escaneá un código completo.');
            return;
        }
        openModal(`
            <div class="barcode-assignment">
                <p class="eyebrow">CÓDIGO NO ASIGNADO</p>
                <h2 id="modal-title">${escapeHtml(state.pendingBarcode)}</h2>
                <p>Buscá el producto y elegí la variante. El código quedará guardado y se agregará a la venta.</p>
                <label for="barcode-assignment-search">PRODUCTO O VARIANTE</label>
                <input id="barcode-assignment-search" type="search" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" aria-autocomplete="none" placeholder="Nombre, talle, SKU o descripción">
                <div id="barcode-assignment-results" class="barcode-assignment-results"></div>
            </div>
        `);
        barcodeAssignmentResults('');
        document.getElementById('barcode-assignment-search')?.focus();
    }

    async function assignScannedBarcode(variantId) {
        const barcode = state.pendingBarcode;
        if (!barcode) {
            return;
        }
        try {
            await apiPost({
                action: 'variant_barcode_assign',
                variant_id: variantId,
                barcode,
            });
            await loadProducts();
            const indexed = variantIndex().get(Number(variantId));
            closeModal();
            state.pendingBarcode = '';
            elements.posSearch.value = '';
            state.posQuery = '';
            if (indexed && Number(indexed.variant.available_stock) > 0) {
                setPosQuantity(variantId, posQuantity(variantId) + 1);
                toast('Código asignado y producto agregado.');
            } else {
                renderPos();
                toast('Código asignado. La variante está sin stock.');
            }
        } catch (error) {
            toast(error.message);
        }
    }

    function posCartTotal() {
        const index = variantIndex();
        return Array.from(state.posCart, ([variantId, quantity]) => {
            const indexed = index.get(Number(variantId));
            return indexed ? Number(indexed.variant.price_cents) * quantity : 0;
        }).reduce((sum, value) => sum + value, 0);
    }

    function showPosCheckoutMenu() {
        if (!state.posCart.size) return;
        openModal(`
            <div class="pos-checkout-menu">
                <p class="eyebrow">CIERRE DE VENTA</p>
                <h2 id="modal-title">TOTAL <strong>${money(posCartTotal())}</strong></h2>
                <div class="pos-checkout-actions">
                    <button type="button" data-pos-checkout-action="register"><strong>REGISTRAR VENTA</strong><small>Guardar en el listado de ventas</small></button>
                    <button type="button" data-pos-checkout-action="archive"><strong>ARCHIVAR VENTA</strong><small>Guardar directamente como archivada</small></button>
                    <button type="button" data-pos-checkout-action="cancel"><strong>CANCELAR</strong><small>Descartar sin descontar stock</small></button>
                    <button type="button" data-pos-checkout-action="print"><strong>IMPRIMIR COMPROBANTE</strong><small>Guardar la venta e imprimir</small></button>
                </div>
            </div>
        `);
    }

    async function createPosSale() {
        const items = Array.from(state.posCart, ([variantId, quantity]) => ({
            variant_id: variantId,
            quantity,
        }));
        if (!items.length) {
            return null;
        }
        elements.completeSale.disabled = true;
        elements.completeSale.textContent = 'PROCESANDO…';
        try {
            const data = await apiPost({
                action: 'pos_sale',
                items,
                customer_name: document.getElementById('pos-customer')?.value.trim() || 'Consumidor final',
                payment_method: 'pos',
            });
            const sale = data.order;
            state.posStockConflicts.clear();
            state.posCart.clear();
            persistPosCart();
            try { localStorage.removeItem(POS_CUSTOMER_STORAGE_KEY); } catch {}
            if (document.getElementById('pos-customer')) document.getElementById('pos-customer').value = '';
            await loadProducts();
            renderPosCart();
            return sale;
        } catch (error) {
            toast(error.message);
            await markPosStockConflicts();
            return null;
        } finally {
            elements.completeSale.disabled = state.posCart.size === 0;
            elements.completeSale.textContent = 'FINALIZAR VENTA';
        }
    }

    function printReceipt(order, openedWindow = null) {
        const receiptUrl = new URL('receipt.php', window.location.href);
        receiptUrl.searchParams.set('id', String(order.id));
        const receipt = openedWindow || window.open(receiptUrl, '_blank');
        if (openedWindow) receipt.location.href = receiptUrl.href;
        if (!receipt) {
            toast('La venta se guardó, pero el navegador bloqueó la impresión.');
        }
    }

    function showPosSaleFinished(sale) {
        openModal(`
            <div class="pos-checkout-menu">
                <p class="eyebrow">VENTA REGISTRADA</p>
                <h2 id="modal-title">${escapeHtml(sale.public_number)}</h2>
                <div class="pos-checkout-actions">
                    <button type="button" data-pos-sale-finish="archive" data-order-id="${Number(sale.id)}"><strong>ARCHIVAR VENTA</strong><small>Guardar con estado archivada</small></button>
                    <button type="button" data-pos-sale-finish="cancel" data-order-id="${Number(sale.id)}"><strong>CANCELAR</strong><small>Anular y restaurar el stock</small></button>
                    <button type="button" data-pos-sale-finish="print" data-order-id="${Number(sale.id)}"><strong>IMPRIMIR COMPROBANTE</strong><small>Volver a imprimir esta venta</small></button>
                </div>
            </div>
        `);
    }

    async function finishPosSaleDirectly() {
        const sale = await createPosSale();
        if (sale) {
            returnToAdminOrders();
        }
    }

    function returnToAdminOrders(notifySaleCompleted = true) {
        const ordersUrl = new URL('./', window.location.href);
        ordersUrl.searchParams.set('view', 'orders');
        if (window.opener && !window.opener.closed) {
            try {
                if (notifySaleCompleted) {
                    window.opener.postMessage(
                        { type: 'laboratorio-pos-sale-completed' },
                        window.location.origin
                    );
                }
                window.opener.focus();
                window.close();
                window.setTimeout(() => {
                    window.location.href = ordersUrl.href;
                }, 350);
                return;
            } catch {
                // Si el navegador bloquea el cierre, continuamos en la lista.
            }
        }
        window.location.href = ordersUrl.href;
    }

    async function cancelPosSale(orderId) {
        try {
            await apiPost({ action: 'order_cancel', order_id: Number(orderId), notify_customer: false });
            await loadProducts();
            closeModal();
            toast('Venta cancelada y stock restaurado.');
        } catch (error) {
            toast(error.message);
        }
    }

    async function archivePosSale(orderId) {
        try {
            await apiPost({ action: 'order_archive', order_id: Number(orderId) });
            closeModal();
            toast('Venta archivada en la lista de ventas.');
        } catch (error) {
            toast(error.message);
        }
    }

    async function loadOrders(silent = false) {
        if (!elements.orderList) {
            return;
        }
        if (!silent) {
            elements.orderList.innerHTML = '<p class="empty-copy">Cargando pedidos…</p>';
        }
        try {
            const data = await apiGet('orders', {
                limit: 150,
                include_archived: state.showArchivedOrders ? 1 : 0,
            });
            state.orders = data.orders;
            const currentIds = new Set(state.orders.map(order => Number(order.id)));
            state.selectedOrderIds = new Set(
                Array.from(state.selectedOrderIds).filter(id => currentIds.has(Number(id)))
            );
            renderOrders();
        } catch (error) {
            toast(error.message);
        }
    }

    function slotTone(slot) {
        const name = String(slot.customer_name || '');
        if (/\bAGREGAR\b/i.test(name)) return 'delivery-slot-add';
        if (/\bARMAR\b/i.test(name)) return 'delivery-slot-build';
        return '';
    }

    function transferTotal(value) {
        const values = String(value || '').split('+').map(part => {
            const normalized = part.trim().replace(/\$/g, '').replace(/\./g, '').replace(',', '.');
            return Number(normalized);
        }).filter(Number.isFinite);
        return values.length ? values.reduce((total, current) => total + current, 0) : null;
    }

    function transferTotalLabel(value) {
        const total = transferTotal(value);
        return total === null ? '' : `Total: ${new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 2 }).format(total)}`;
    }

    function deliveryCustomerKey(value) {
        return fold(String(value || '')
            .replace(/\b(ARMAR|AGREGAR)\b/gi, '')
            .replace(/\s+/g, ' ')
            .trim());
    }

    function deliveryNameSimilarity(first, second) {
        if (!first || !second) return 0;
        if (first === second) return 1;
        const previous = Array.from({ length: second.length + 1 }, (_, index) => index);
        for (let row = 1; row <= first.length; row += 1) {
            let diagonal = previous[0];
            previous[0] = row;
            for (let column = 1; column <= second.length; column += 1) {
                const old = previous[column];
                previous[column] = Math.min(
                    previous[column] + 1,
                    previous[column - 1] + 1,
                    diagonal + (first[row - 1] === second[column - 1] ? 0 : 1)
                );
                diagonal = old;
            }
        }
        return 1 - previous[second.length] / Math.max(first.length, second.length);
    }

    function isLikelySameDeliveryCustomer(first, second) {
        const left = deliveryCustomerKey(first);
        const right = deliveryCustomerKey(second);
        if (!left || !right) return false;
        if (left === right) return true;
        const leftWords = left.split(/[^a-z0-9]+/).filter(word => word.length >= 3);
        const rightWords = right.split(/[^a-z0-9]+/).filter(word => word.length >= 3);
        const equalWords = leftWords.filter(word => rightWords.includes(word)).length;
        if (equalWords >= 2) return true;
        return leftWords.some(leftWord => rightWords.some(rightWord => (
            leftWord.length >= 4 && rightWord.length >= 4 && deliveryNameSimilarity(leftWord, rightWord) >= .78
        )));
    }

    function suggestedDeliverySlots(order) {
        if (!deliveryCustomerKey(order?.customer_name)) return [];
        return state.deliverySlots.filter(slot => (
            String(slot.order_numbers || '').trim()
            && isLikelySameDeliveryCustomer(slot.customer_name, order.customer_name)
        ));
    }

    function pendingDeliveryOrders() {
        const ids = state.pendingDeliveryOrderIds.length
            ? state.pendingDeliveryOrderIds
            : (state.pendingDeliveryOrderId ? [state.pendingDeliveryOrderId] : []);
        return ids.map(id => state.orders.find(order => Number(order.id) === Number(id))).filter(Boolean);
    }

    function renderDeliverySlots() {
        if (!elements.deliverySlots) return;
        const byNumber = new Map(state.deliverySlots.map(slot => [Number(slot.slot_number), slot]));
        const pendingOrders = pendingDeliveryOrders();
        const pending = pendingOrders[0];
        const pendingIds = pendingOrders.map(order => Number(order.id));
        const suggestions = pendingOrders.length === 1 ? suggestedDeliverySlots(pending) : [];
        const suggestedNumbers = new Set(suggestions.map(slot => Number(slot.slot_number)));
        const query = fold(state.deliveryQuery);
        if (elements.deliveryCopyGuide) {
            elements.deliveryCopyGuide.hidden = !pending;
            const suggestedHtml = suggestions.length
                ? `<span class="delivery-suggestion-label">Posibles coincidencias de cliente:</span><span class="delivery-suggestions">${suggestions.map(slot => `<button type="button" class="delivery-suggestion" data-place-delivery-orders="${pendingIds.join(',')}" data-place-delivery-slot="${Number(slot.slot_number)}">FILA ${Number(slot.slot_number)}${slot.location ? ` · ${escapeHtml(slot.location)}` : ''}</button>`).join('')}</span>`
                : '<span>Elegí una fila: una vacía queda marcada <b>ARMAR</b>; si ya tiene pedidos, se suma como <b>AGREGAR</b>.</span>';
            const pendingLabel = pendingOrders.length === 1
                ? `${escapeHtml(pending.public_number)}`
                : `${pendingOrders.length} VENTAS`;
            const customerLabel = pendingOrders.length === 1
                ? `${escapeHtml(pending.customer_name)}.`
                : 'Las ventas seleccionadas se agregarán juntas, separadas por / y con sus importes sumados.';
            elements.deliveryCopyGuide.innerHTML = pending ? `<strong><span class="delivery-guide-arrow" aria-hidden="true">→</span> ${pendingLabel}</strong><span class="delivery-guide-content"><span>${customerLabel}</span>${suggestedHtml}</span><button class="small-button" type="button" data-cancel-delivery-placement>CANCELAR</button>` : '';
        }
        const rows = Array.from({ length: 100 }, (_, index) => {
            const number = index + 1;
            const slot = byNumber.get(number) || { slot_number: number, location: '', order_numbers: '', customer_name: '', transfers: '', cash_due: '', order_total_cents: 0, revision: 0 };
            if (query && !fold(`${slot.order_numbers} ${slot.customer_name}`).includes(query)) return '';
            const tone = slotTone(slot);
            const linkedOrders = Array.isArray(slot.orders) ? slot.orders : [];
            const field = (key, label) => `<input type="text" value="${escapeHtml(slot[key] || '')}" data-delivery-field="${key}" data-delivery-slot="${number}" data-delivery-revision="${Number(slot.revision || 0)}" aria-label="${label} ubicación ${number}">`;
            const location = `<input type="text" value="${escapeHtml(slot.location || '')}" data-delivery-field="location" data-delivery-slot="${number}" data-delivery-revision="${Number(slot.revision || 0)}" aria-label="Ubicación fila ${number}">`;
            const markerButton = /\b(ARMAR|AGREGAR)\b/i.test(String(slot.customer_name || '')) ? `<button class="delivery-marker-clear" type="button" data-clear-delivery-marker="${number}" title="Quitar ARMAR / AGREGAR">⊘</button>` : '';
            const statusAttention = tone === 'delivery-slot-add'
                ? '<span class="delivery-status-attention delivery-add-attention" role="img" aria-label="Atención: pedido agregado a una fila existente" title="Atención: este pedido se agregó a una fila existente">!</span>'
                : (tone === 'delivery-slot-build' ? '<span class="delivery-status-attention delivery-build-attention" role="img" aria-label="Atención: pedido pendiente de armar" title="Atención: este pedido está pendiente de armar">!</span>' : '');
            const printButton = linkedOrders.length ? `<button class="delivery-print" type="button" data-print-delivery-slot="${number}" aria-label="Imprimir ventas de fila ${number}" title="Imprimir ventas de esta fila">⎙</button>` : '';
            const returnButton = linkedOrders.length ? `<button class="delivery-return" type="button" data-open-return-delivery-slot="${number}" aria-label="Mover ventas de fila ${number} a Lista de Ventas" title="Mover a Lista de Ventas">→</button>` : '';
            return `<tr class="${tone} ${pending ? 'delivery-placement-active' : ''} ${suggestedNumbers.has(number) ? 'delivery-placement-suggested' : ''}" data-delivery-row="${number}"><th scope="row">${number}${statusAttention}</th><td>${pending ? `<button class="delivery-place" type="button" data-place-delivery-orders="${pendingIds.join(',')}" data-place-delivery-slot="${number}" aria-label="Ubicar ventas seleccionadas en fila ${number}" title="Ubicar aquí">→</button>` : ''}</td><td>${location}</td><td class="delivery-flow-actions">${printButton}${returnButton}</td><td>${field('order_numbers', 'Órdenes')}</td><td>${field('customer_name', 'Nombre y apellido')}</td><td>${markerButton}</td><td>${field('transfers', 'Transferencias')}<small class="delivery-transfer-total">${escapeHtml(transferTotalLabel(slot.transfers))}</small></td><td>${field('cash_due', 'Efectivo pendiente')}</td><td><strong class="delivery-order-total">${money(slot.order_total_cents)}</strong></td><td><button class="delivery-delete" type="button" data-delete-delivery-slot="${number}" aria-label="Vaciar fila ${number}" title="Vaciar fila">🗑</button></td></tr>`;
        }).filter(Boolean);
        elements.deliverySlots.innerHTML = rows.length
            ? rows.join('')
            : '<tr><td class="delivery-no-results" colspan="11">No encontramos una fila que coincida con esa búsqueda.</td></tr>';
    }

    async function loadDeliverySlots() {
        if (!elements.deliverySlots) return;
        try {
            const data = await apiGet('delivery_slots');
            state.deliverySlots = data.slots || [];
            renderDeliverySlots();
        } catch (error) { toast(error.message); }
    }

    async function saveDeliverySlot(slotNumber, source) {
        const row = source.closest('[data-delivery-row]');
        if (!row) return;
        const field = key => row.querySelector(`[data-delivery-field="${key}"]`);
        const revision = Number(source.dataset.deliveryRevision || 0);
        try {
            const data = await apiPost({
                action: 'delivery_slot_update', slot_number: Number(slotNumber), revision,
                location: field('location')?.value || '',
                order_numbers: field('order_numbers')?.value || '',
                customer_name: field('customer_name')?.value || '',
                transfers: field('transfers')?.value || '',
                cash_due: field('cash_due')?.value || '',
            });
            const next = data.slot;
            state.deliverySlots = state.deliverySlots.filter(slot => Number(slot.slot_number) !== Number(slotNumber));
            if (Number(next.revision || 0) > 0) state.deliverySlots.push(next);
            renderDeliverySlots();
        } catch (error) {
            toast(error.message);
            await loadDeliverySlots();
        }
    }

    async function showCopyToDeliveries(orderId) {
        const order = state.orders.find(item => Number(item.id) === Number(orderId));
        if (!order) return;
        if (order.delivery_slot_number && !order.delivery_reopened_at) { toast('Esta venta ya fue copiada a Entregas.'); return; }
        await loadDeliverySlots();
        state.pendingDeliveryOrderId = Number(orderId);
        state.pendingDeliveryOrderIds = [];
        state.deliveryQuery = '';
        showView('deliveries');
        const suggestions = suggestedDeliverySlots(order);
        toast(suggestions.length
            ? `Encontramos ${suggestions.length === 1 ? 'una ubicación sugerida' : `${suggestions.length} ubicaciones sugeridas`} para este cliente.`
            : 'Elegí la fila donde querés ubicar esta venta.');
    }

    async function showSelectedOrdersToDeliveries(orderIds) {
        const orders = orderIds.map(id => state.orders.find(order => Number(order.id) === Number(id))).filter(Boolean);
        const available = orders.filter(order => !order.delivery_slot_number || order.delivery_reopened_at);
        if (!available.length) { toast('Las ventas seleccionadas ya fueron copiadas a Entregas.'); return; }
        await loadDeliverySlots();
        state.pendingDeliveryOrderId = 0;
        state.pendingDeliveryOrderIds = available.map(order => Number(order.id));
        state.deliveryQuery = '';
        showView('deliveries');
        toast(`Elegí una fila para pasar ${available.length === 1 ? 'la venta seleccionada' : `las ${available.length} ventas seleccionadas`}.`);
    }

    function showDeliveryMatchWarning(orderIds, selectedSlot, matchGroups) {
        const ids = Array.from(new Set(orderIds.map(Number).filter(Number.isFinite)));
        const rows = matchGroups.flatMap(({ order, slots }) => slots.map(item => ({ order, item })));
        const rowCount = new Set(rows.map(({ item }) => Number(item.slot_number))).size;
        openModal(`
            <p class="eyebrow">REVISIÓN DE PEDIDOS EXISTENTES</p>
            <h2 id="modal-title">¿DÓNDE QUERÉS UBICAR LAS VENTAS?</h2>
            <p class="empty-copy">Encontramos <strong>${rowCount === 1 ? 'una fila con una coincidencia' : `${rowCount} filas con coincidencias`}</strong> para las ventas seleccionadas. Revisá cada caso antes de continuar.</p>
            <div class="delivery-match-table-wrap"><table class="delivery-match-table"><thead><tr><th>VENTA NUEVA</th><th>CLIENTE NUEVO</th><th>FILA</th><th>UBICACIÓN</th><th>ÓRDENES CARGADAS</th><th>CLIENTE CARGADO</th><th>TRANSFERENCIAS</th><th>EFECTIVO</th><th>IMPORTE</th><th></th></tr></thead><tbody>
                ${rows.map(({ order, item }) => `<tr><td>${escapeHtml(order.public_number)}</td><td>${escapeHtml(order.customer_name)}</td><td><strong>${Number(item.slot_number)}</strong></td><td>${escapeHtml(item.location || '—')}</td><td>${escapeHtml(item.order_numbers || '—')}</td><td>${escapeHtml(item.customer_name || '—')}</td><td>${escapeHtml(item.transfers || '—')}</td><td>${escapeHtml(item.cash_due || '—')}</td><td>${money(item.order_total_cents)}</td><td><button class="small-button" type="button" data-confirm-delivery-match-slot="${Number(item.slot_number)}" data-delivery-order-ids="${ids.join(',')}">USAR FILA ${Number(item.slot_number)} →</button></td></tr>`).join('')}
            </tbody></table></div>
            <div class="delivery-match-choice"><span>También podés continuar con la fila <strong>${Number(selectedSlot)}</strong> que habías elegido.</span><button class="secondary-button" type="button" data-confirm-delivery-other-slot="${Number(selectedSlot)}" data-delivery-order-ids="${ids.join(',')}">UBICAR IGUAL EN FILA ${Number(selectedSlot)}</button></div>
            <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>VOLVER</button></div>
        `);
    }

    function showDeliveryOccupiedWarning(orderIds, selectedSlot, existingSlot) {
        const ids = Array.from(new Set(orderIds.map(Number).filter(Number.isFinite)));
        const incoming = pendingDeliveryOrders();
        openModal(`
            <p class="eyebrow">FILA CON PEDIDOS CARGADOS</p>
            <h2 id="modal-title">REVISÁ ANTES DE AGREGAR</h2>
            <p class="empty-copy">La fila <strong>${Number(selectedSlot)}</strong> ya tiene pedidos. Si continuás, las nuevas ventas se sumarán separadas por <strong>/</strong> y quedarán marcadas como <strong>AGREGAR</strong>.</p>
            <div class="delivery-match-table-wrap"><table class="delivery-match-table"><thead><tr><th>FILA</th><th>UBICACIÓN</th><th>ÓRDENES YA CARGADAS</th><th>CLIENTE</th><th>IMPORTE ACTUAL</th></tr></thead><tbody><tr><td><strong>${Number(selectedSlot)}</strong></td><td>${escapeHtml(existingSlot.location || '—')}</td><td>${escapeHtml(existingSlot.order_numbers || '—')}</td><td>${escapeHtml(existingSlot.customer_name || '—')}</td><td>${money(existingSlot.order_total_cents)}</td></tr></tbody></table></div>
            <p class="notice"><strong>Ventas a agregar:</strong> ${incoming.map(order => `${escapeHtml(order.public_number)} · ${escapeHtml(order.customer_name)}`).join(' &nbsp;|&nbsp; ')}</p>
            <div class="modal-actions"><button class="primary-button" type="button" data-confirm-delivery-other-slot="${Number(selectedSlot)}" data-delivery-order-ids="${ids.join(',')}">SÍ, AGREGAR A FILA ${Number(selectedSlot)}</button><button class="secondary-button" type="button" data-close-modal>ELEGIR OTRA FILA</button></div>
        `);
    }

    async function copyOrdersToDelivery(orderIds, slot, skipDeliveryMatchWarning = false) {
        const ids = Array.from(new Set(orderIds.map(Number).filter(Number.isFinite)));
        try {
            if (!ids.length) return;
            const pendingOrders = pendingDeliveryOrders();
            const targetSlot = state.deliverySlots.find(item => Number(item.slot_number) === Number(slot));
            if (String(targetSlot?.order_numbers || '').trim() !== '' && !skipDeliveryMatchWarning) {
                showDeliveryOccupiedWarning(ids, slot, targetSlot);
                return;
            }
            const matchGroups = pendingOrders
                .map(order => ({ order, slots: suggestedDeliverySlots(order) }))
                .filter(group => group.slots.length && !group.slots.some(item => Number(item.slot_number) === Number(slot)));
            if (matchGroups.length && !skipDeliveryMatchWarning) {
                showDeliveryMatchWarning(ids, slot, matchGroups);
                return;
            }
            await apiPost(ids.length === 1
                ? { action: 'delivery_copy_order', order_id: ids[0], slot_number: slot }
                : { action: 'delivery_copy_orders', order_ids: ids, slot_number: slot });
            state.pendingDeliveryOrderId = 0;
            state.pendingDeliveryOrderIds = [];
            state.selectedOrderIds.clear();
            await Promise.all([loadOrders(true), loadDeliverySlots()]);
            toast(ids.length === 1
                ? `Venta movida a Entregas · fila ${slot}.`
                : `${ids.length} ventas movidas juntas a Entregas · fila ${slot}.`);
        } catch (error) { toast(error.message); }
    }

    async function copyOrderToDelivery(orderId, slot) { return copyOrdersToDelivery([orderId], slot); }

    async function removeDeliverySlot(slotNumber) {
        try {
            await apiPost({ action: 'delivery_slot_delete', slot_number: Number(slotNumber) });
            await Promise.all([loadDeliverySlots(), loadOrders(true)]);
            toast(`Fila ${slotNumber} vaciada.`);
        } catch (error) { toast(error.message); }
    }

    async function returnOrderFromDeliveries(orderId) {
        try {
            await apiPost({ action: 'delivery_return_order', order_id: Number(orderId) });
            await Promise.all([loadDeliverySlots(), loadOrders(true)]);
            toast('Venta movida a Lista de Ventas.');
        } catch (error) { toast(error.message); }
    }

    async function openDeliverySlotReturn(slotNumber) {
        const slot = state.deliverySlots.find(item => Number(item.slot_number) === Number(slotNumber));
        const orders = Array.isArray(slot?.orders) ? slot.orders : [];
        if (!orders.length) return;
        try {
            const details = await Promise.all(orders.map(async order => {
                try { return (await apiGet('order', { id: Number(order.id) })).order; }
                catch { return order; }
            }));
            openModal(`
                <p class="eyebrow">MOVER A LISTA DE VENTAS</p>
                <h2 id="modal-title">FILA ${Number(slotNumber)}</h2>
                <p class="empty-copy">Elegí las ventas que querés mover. Se reabrirán en LDV y se quitarán de esta fila.</p>
                <div id="delivery-slot-return-choice" class="delivery-slot-print-choice">
                    ${details.map(order => {
                        const products = Array.isArray(order.items) ? sortedOrderItems(order.items).map(item => `${Number(item.quantity)} × ${item.product_name}${fold(item.variant_name) === 'unica' ? '' : ` · ${item.variant_name || ''}`}`).join(' · ') : '';
                        return `<label><input type="checkbox" value="${Number(order.id)}" checked><span><strong>${escapeHtml(order.public_number)}</strong><small>${escapeHtml(order.customer_name || '')} · ${money(order.total_cents)}${products ? `<br>${escapeHtml(products)}` : ''}</small></span></label>`;
                    }).join('')}
                </div>
                <div class="modal-actions"><button class="primary-button" type="button" data-confirm-delivery-slot-return>MOVER A LISTA DE VENTAS</button><button class="secondary-button" type="button" data-close-modal>VOLVER</button></div>
            `);
        } catch (error) { toast(error.message); }
    }

    async function continueDeliverySlotReturn() {
        const ids = Array.from(document.querySelectorAll('#delivery-slot-return-choice input:checked')).map(input => Number(input.value)).filter(Number.isFinite);
        if (!ids.length) { toast('Elegí al menos una venta para devolver.'); return; }
        try {
            for (const orderId of ids) await apiPost({ action: 'delivery_return_order', order_id: orderId });
            closeModal();
            await Promise.all([loadDeliverySlots(), loadOrders(true)]);
            toast(ids.length === 1 ? 'Venta movida a Lista de Ventas.' : `${ids.length} ventas movidas a Lista de Ventas.`);
        } catch (error) { toast(error.message); }
    }

    function openDeliverySlotPrint(slotNumber) {
        const slot = state.deliverySlots.find(item => Number(item.slot_number) === Number(slotNumber));
        const orders = Array.isArray(slot?.orders) ? slot.orders : [];
        if (!orders.length) return;
        if (orders.length === 1) {
            printStoredOrder(Number(orders[0].id));
            return;
        }
        openModal(`
            <p class="eyebrow">IMPRIMIR DESDE ENTREGAS</p>
            <h2 id="modal-title">FILA ${Number(slotNumber)}</h2>
            <p class="empty-copy">Elegí la venta o las ventas que querés imprimir.</p>
            <div id="delivery-slot-print-choice" class="delivery-slot-print-choice">
                ${orders.map(order => `<label><input type="checkbox" value="${Number(order.id)}" checked><span><strong>${escapeHtml(order.public_number)}</strong><small>${escapeHtml(order.customer_name || '')} · ${money(order.total_cents)}</small></span></label>`).join('')}
            </div>
            <div class="modal-actions"><button class="primary-button" type="button" data-confirm-delivery-slot-print="${Number(slotNumber)}">CONTINUAR</button><button class="secondary-button" type="button" data-close-modal>VOLVER</button></div>
        `);
    }

    function continueDeliverySlotPrint() {
        const ids = Array.from(document.querySelectorAll('#delivery-slot-print-choice input:checked')).map(input => Number(input.value)).filter(Number.isFinite);
        if (!ids.length) { toast('Elegí al menos una venta para imprimir.'); return; }
        if (ids.length === 1) { closeModal(); printStoredOrder(ids[0]); return; }
        openModal(`
            <p class="eyebrow">${ids.length} VENTAS SELECCIONADAS</p>
            <h2 id="modal-title">¿CÓMO QUERÉS IMPRIMIRLAS?</h2>
            <div class="delivery-print-choice">
                <button class="primary-button" type="button" data-print-delivery-orders="${ids.join(',')}" data-print-delivery-layout="grouped">IMPRIMIR Y AGRUPAR ${ids.length} VENTAS<small>Una impresión continua, separada por número de orden.</small></button>
                <button class="secondary-button" type="button" data-print-delivery-orders="${ids.join(',')}" data-print-delivery-layout="individual">IMPRIMIR ${ids.length} VENTAS INDIVIDUALES<small>Una venta por hoja.</small></button>
            </div>
            <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>VOLVER</button></div>
        `);
    }

    function deliveryOrderSummary(order) {
        const items = sortedOrderItems(order.items || []).map(item => {
            const variant = fold(item.variant_name) === 'unica' ? '' : ` · ${item.variant_name || ''}`;
            return `${Number(item.quantity)} × ${item.product_name || 'Producto'}${variant}`;
        }).join('<br>');
        const status = order.status === 'cancelled'
            ? 'Cancelada'
            : (statusLabels[order.status] || 'Activa');
        return `<tr>
            <td><strong>${escapeHtml(order.public_number)}</strong></td>
            <td>${escapeHtml(argentinaDateLabel(order.created_at))}</td>
            <td>${items || 'Sin productos'}</td>
            <td><strong>${money(order.total_cents)}</strong></td>
            <td>${escapeHtml(status)}</td>
        </tr>`;
    }

    async function deleteDeliverySlot(slotNumber) {
        const slot = state.deliverySlots.find(item => Number(item.slot_number) === Number(slotNumber));
        const customer = String(slot?.customer_name || '').trim();
        if (!customer || !deliveryCustomerKey(customer)) {
            await removeDeliverySlot(slotNumber);
            return;
        }
        try {
            // Se consulta nuevamente al servidor para no depender del listado que pudo quedar viejo.
            const data = await apiGet('orders', { limit: 150, include_archived: 0 });
            const matches = (data.orders || []).filter(order => (
                !order.archived_at
                && isLikelySameDeliveryCustomer(customer, order.customer_name)
            ));
            if (!matches.length) {
                await removeDeliverySlot(slotNumber);
                return;
            }
            const details = await Promise.all(matches.map(async order => {
                try {
                    return (await apiGet('order', { id: Number(order.id) })).order;
                } catch {
                    return order;
                }
            }));
            openModal(`
                <section class="delivery-delete-warning">
                    <p class="eyebrow">REVISIÓN ANTES DE VACIAR</p>
                    <h2 id="modal-title">PEDIDOS SIN ARCHIVAR</h2>
                    <p>La fila <strong>${Number(slotNumber)}</strong> corresponde a <strong>${escapeHtml(deliveryCustomerKey(customer))}</strong>. Encontramos ${details.length === 1 ? 'una venta activa' : `${details.length} ventas activas`} de esta persona en la Lista de Ventas.</p>
                    <div class="delivery-match-table-wrap"><table class="delivery-match-table delivery-delete-orders"><thead><tr><th>VENTA</th><th>FECHA</th><th>PRODUCTOS</th><th>TOTAL</th><th>ESTADO</th></tr></thead><tbody>${details.map(deliveryOrderSummary).join('')}</tbody></table></div>
                    <p class="notice">Vaciar la fila no archiva ni cancela estas ventas; solamente las libera de Entregas para que puedas ubicarlas de nuevo.</p>
                    <div class="modal-actions"><button class="secondary-button" type="button" data-close-modal>VOLVER</button><button class="danger-button" type="button" data-confirm-delete-delivery-slot="${Number(slotNumber)}">VACIAR FILA ${Number(slotNumber)}</button></div>
                </section>
            `);
        } catch (error) { toast(error.message); }
    }

    function adminHasUnsavedInteraction() {
        if (Date.now() < productActionsMenuPauseUntil) {
            return true;
        }
        if (elements.modal?.classList.contains('open') || quickUpdateTimers.size > 0 || quickUpdateInFlight > 0) {
            return true;
        }
        const active = document.activeElement;
        return Boolean(active?.closest(
            '#admin-product-list input, #admin-product-list select, #category-admin-tree input, #size-guide-rows input, #delivery-slots input, [data-pos-input], form textarea, form input:not([type="search"]), form select'
        ));
    }

    async function refreshActiveAdminView() {
        if (automaticRefreshRunning || document.visibilityState !== 'visible' || adminHasUnsavedInteraction()) {
            return;
        }
        automaticRefreshRunning = true;
        try {
            if (elements.invitationsBadge && state.view !== 'invitations' && Date.now() >= invitationBadgeRefreshAt) {
                invitationBadgeRefreshAt = Date.now() + 10000;
                await loadInvitations();
            }
            if (elements.ordersBadge && state.view !== 'orders' && Date.now() >= orderBadgeRefreshAt) {
                orderBadgeRefreshAt = Date.now() + 10000;
                await loadOrderNotifications();
            }
            if (elements.posProducts || elements.posCartLines) {
                await refreshPosAvailability();
            } else if (state.view === 'orders') {
                await loadOrders(true);
            } else if (state.view === 'deliveries') {
                await loadDeliverySlots();
            } else if (state.view === 'products') {
                await loadProducts();
            } else if (state.view === 'categories') {
                await loadCategories();
            } else if (state.view === 'users') {
                await loadUsers();
            } else if (state.view === 'settings') {
                await loadSettings();
            } else if (state.view === 'contact') {
                await loadContact();
            } else if (state.view === 'whatsapp') {
                await loadEmailSettings();
            } else if (state.view === 'size-guide') {
                await loadSizeGuide();
            } else if (state.view === 'invitations') {
                await loadInvitations();
            }
        } finally {
            automaticRefreshRunning = false;
        }
    }

    const statusLabels = {
        pending_payment: 'Abierta',
        payment_reported: 'Abierta',
        paid_prepare: 'Abierta',
        ready_pickup: 'Abierta',
        delivered: 'Abierta',
        rejected: 'Abierta',
        cancelled: 'Cancelado',
    };

    const channelLabels = {
        web: 'Tienda web',
        whatsapp: 'WhatsApp',
        pos: 'Mostrador',
    };

    const paymentMethodLabels = {
        bank_transfer: 'Transferencia',
        cash: 'Efectivo',
        debit_card: 'D\u00e9bito',
        credit_card: 'Cr\u00e9dito',
    };

    function paymentAiBadge(order) {
        if (!order.payment_proof_id) {
            return '';
        }
        const labels = {
            prevalidated: 'IA: COINCIDENCIA PRELIMINAR',
            review: 'IA: REVISAR DATOS',
            failed: 'IA: NO DISPONIBLE',
            disabled: 'IA: SIN CONFIGURAR',
            not_run: 'IA: PENDIENTE',
        };
        const status = String(order.payment_ai_status || 'not_run');
        return `
            <div class="payment-ai payment-ai-${escapeHtml(status)}">
                <strong>${escapeHtml(labels[status] || labels.not_run)}</strong>
                <small>${escapeHtml(order.payment_ai_summary || 'Siempre verificar la acreditación en el banco.')}</small>
            </div>
        `;
    }

    function orderActions(order) {
        const actions = [
            `<button class="small-button" type="button" data-print-order="${Number(order.id)}">Imprimir</button>`,
        ];
        if (String(order.customer_phone || '').replace(/\D+/g, '').length >= 8) {
            actions.push(`<button class="small-button" type="button" data-whatsapp-order="${Number(order.id)}">WhatsApp</button>`);
        }
        if (['web', 'whatsapp'].includes(order.channel) && order.status !== 'cancelled') {
            actions.push(
                `<button class="small-button" type="button" data-edit-order="${Number(order.id)}">Editar productos</button>`
            );
        }
        return actions.join('');
    }

    async function openOrderWhatsapp(orderId) {
        try {
            const [orderData, settingsData] = await Promise.all([
                apiGet('order', { id: orderId }), apiGet('settings'),
            ]);
            const order = orderData.order;
            const settings = settingsData.settings;
            const key = order.status === 'cancelled' ? 'cancelled'
                : order.status === 'ready_pickup' ? 'ready_pickup'
                : order.payment_method === 'cash' ? 'cash_created'
                : 'order_created';
            const template = String(settings[`whatsapp_message_${key}`] || 'Hola {{cliente}}! Tu pedido {{pedido}} está actualizado.');
            const message = template
                .replaceAll('{{cliente}}', String(order.customer_name || ''))
                .replaceAll('{{pedido}}', String(order.public_number || ''))
                .replaceAll('{{total}}', money(order.total_cents))
                .replaceAll('{{plazo}}', String(order.payment_deadline_at || ''));
            const phone = String(order.customer_phone || '').replace(/\D+/g, '');
            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(message)}`, '_blank', 'noopener');
        } catch (error) { toast(error.message); }
    }

    function selectedOrders() {
        return state.orders.filter(order => state.selectedOrderIds.has(Number(order.id)));
    }

    function setOrderSelection(orderId, selected) {
        if (selected) {
            state.selectedOrderIds.add(Number(orderId));
        } else {
            state.selectedOrderIds.delete(Number(orderId));
        }
        renderOrders();
    }

    function setAllMatchingOrderSelection(orders, selected) {
        orders.forEach(order => {
            if (selected) {
                state.selectedOrderIds.add(Number(order.id));
            } else {
                state.selectedOrderIds.delete(Number(order.id));
            }
        });
        renderOrders();
    }

    function showCancellationDialog(orderIds) {
        const orders = orderIds
            .map(id => state.orders.find(order => Number(order.id) === Number(id)))
            .filter(Boolean);
        const cancellable = orders.filter(order => (
            order.status !== 'cancelled' && !order.archived_at
        ));
        if (!cancellable.length) {
            toast('Seleccioná al menos una venta activa, sin archivar, para cancelarla.');
            return;
        }
        const skipped = orders.length - cancellable.length;
        openModal(`
            <h2 id="modal-title">CANCELAR ${cancellable.length === 1 ? 'VENTA' : 'VENTAS'}</h2>
            <p class="empty-copy">Vas a cancelar ${cancellable.length} ${cancellable.length === 1 ? 'venta' : 'ventas'} seleccionada${cancellable.length === 1 ? '' : 's'}.</p>
            ${skipped ? `<p class="notice">${skipped} venta${skipped === 1 ? '' : 's'} ya cancelada${skipped === 1 ? '' : 's'} quedará sin cambios.</p>` : ''}
            <label class="order-confirm-option">
                <input id="cancel-notify-customer" type="checkbox" checked>
                <span><strong>Abrir WhatsApp para avisar al cliente</strong><small>Se abrirá el mensaje de cancelación listo para revisar y enviar.</small></span>
            </label>
            <label class="order-confirm-option">
                <input id="cancel-restore-stock" type="checkbox" checked>
                <span><strong>Reponer stock</strong><small>Marcado: las unidades vuelven a estar disponibles. Sin marcar: la venta se cancela sin devolverlas al inventario.</small></span>
            </label>
            <div class="modal-actions">
                <button class="danger-button" type="button" data-confirm-cancel-orders="${cancellable.map(order => Number(order.id)).join(',')}">CONFIRMAR CANCELACI&Oacute;N</button>
                <button class="secondary-button" type="button" data-close-modal>VOLVER</button>
            </div>
        `);
    }

    async function cancelOrders(orderIds, notifyCustomer, restoreStock = true) {
        try {
            for (const orderId of orderIds) {
                await apiPost({
                    action: 'order_cancel',
                    order_id: Number(orderId),
                    notify_customer: false,
                    restore_stock: restoreStock,
                });
                if (notifyCustomer) await openOrderWhatsapp(Number(orderId));
            }
            closeModal();
            state.selectedOrderIds.clear();
            await Promise.all([loadOrders(), loadProducts()]);
            toast(restoreStock ? 'Ventas canceladas y stock repuesto.' : 'Ventas canceladas sin reponer stock.');
        } catch (error) {
            toast(error.message);
        }
    }

    async function archiveSelectedOrders(orderIds) {
        const selected = orderIds
            .map(id => state.orders.find(order => Number(order.id) === Number(id)))
            .filter(Boolean);
        if (!selected.length) {
            toast('Para archivar, seleccioná únicamente ventas entregadas.');
            return;
        }
        try {
            for (const order of selected) {
                await apiPost({ action: 'order_archive', order_id: Number(order.id) });
            }
            state.selectedOrderIds.clear();
            await loadOrders();
            closeModal();
            showView('orders');
            toast('Ventas archivadas.');
        } catch (error) {
            toast(error.message);
        }
    }

    async function reopenSelectedOrders(orderIds) {
        if (!window.confirm(`¿Reabrir ${orderIds.length} ${orderIds.length === 1 ? 'venta' : 'ventas'}?`)) return;
        try {
            for (const orderId of orderIds) {
                await apiPost({ action: 'order_reopen', order_id: Number(orderId) });
            }
            state.selectedOrderIds.clear();
            await loadOrders();
            toast('Ventas reabiertas.');
        } catch (error) {
            toast(error.message);
        }
    }

    async function applySelectedOrderStatus() {
        const action = elements.bulkOrderStatus?.value || '';
        const orders = selectedOrders();
        if (!action || !orders.length) {
            toast('Elegí un estado y al menos una venta.');
            return;
        }
        const expectedStatus = { approve: 'payment_reported', ready: 'paid_prepare', deliver: 'ready_pickup' }[action];
        const hasInvalidStatus = orders.some(order => {
            if (action === 'ready') {
                return order.status !== 'paid_prepare'
                    && !(order.status === 'pending_payment' && order.payment_method === 'cash' && order.stock_reserved_at);
            }
            return order.status !== expectedStatus;
        });
        if (hasInvalidStatus) {
            toast('Seleccioná ventas que estén todas en el estado correspondiente antes de actualizarlas.');
            return;
        }
        try {
            for (const order of orders) {
                await handleOrderAction(Number(order.id), action, false);
            }
            state.selectedOrderIds.clear();
            await Promise.all([loadOrders(), loadProducts()]);
            toast('Estado de ventas actualizado.');
        } catch (error) {
            toast(error.message);
        }
    }

    function checkLabel(value) {
        if (value === true) {
            return '<span class="check-match">COINCIDE</span>';
        }
        if (value === false) {
            return '<span class="check-mismatch">REVISAR</span>';
        }
        return '<span class="check-unknown">NO LEÍDO</span>';
    }

    async function showProofAnalysis(proofId) {
        try {
            const data = await apiGet('payment_proof_analysis', { id: proofId });
            const analysis = data.analysis;
            const result = analysis.result || {};
            const extracted = result.extracted || {};
            const checks = result.checks || {};
            const anomalyList = Array.isArray(extracted.visual_anomalies)
                ? extracted.visual_anomalies
                : [];
            openModal(`
                <div class="proof-analysis">
                    <p class="eyebrow">AYUDA PARA LA REVISIÓN</p>
                    <h2 id="modal-title">PREVALIDACIÓN DEL COMPROBANTE</h2>
                    <div class="payment-ai payment-ai-${escapeHtml(analysis.status)}">
                        <strong>${escapeHtml(analysis.summary || 'Sin resultado automático.')}</strong>
                        <small>Esto no confirma que el dinero esté acreditado. Verificá siempre en el banco.</small>
                    </div>
                    ${analysis.result ? `
                        <div class="proof-analysis-grid">
                            <div><span>IMPORTE LEÍDO</span><strong>${extracted.amount_cents === null || extracted.amount_cents === undefined ? 'No leído' : money(extracted.amount_cents)}</strong>${checkLabel(checks.amount)}</div>
                            <div><span>FECHA</span><strong>${escapeHtml(extracted.transfer_date || 'No leída')}</strong>${checkLabel(checks.date_plausible)}</div>
                            <div><span>DESTINATARIO</span><strong>${escapeHtml(extracted.recipient_name || extracted.recipient_account || 'No leído')}</strong>${checkLabel(checks.recipient)}</div>
                            <div><span>OPERACIÓN</span><strong>${escapeHtml(extracted.operation_reference || 'No leída')}</strong>${checkLabel(checks.operation_reference_present)}</div>
                        </div>
                        ${anomalyList.length ? `<div class="proof-anomalies"><strong>SEÑALES PARA REVISAR</strong><ul>${anomalyList.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul></div>` : ''}
                    ` : '<p class="empty-copy">La prevalidación no está configurada o no pudo ejecutarse.</p>'}
                </div>
            `);
        } catch (error) {
            toast(error.message);
        }
    }

    function formatFileSize(bytes) {
        const size = Number(bytes || 0);
        if (size < 1024) {
            return `${size} B`;
        }
        if (size < 1024 * 1024) {
            return `${Math.round(size / 1024)} KB`;
        }
        return `${(size / 1024 / 1024).toFixed(1)} MB`;
    }

    function sortedOrderItems(items) {
        return [...items].sort((first, second) => {
            const byProduct = String(first.product_name || '').localeCompare(
                String(second.product_name || ''),
                'es',
                { sensitivity: 'base', numeric: true }
            );
            if (byProduct !== 0) return byProduct;
            return String(first.variant_name || '').localeCompare(
                String(second.variant_name || ''),
                'es',
                { sensitivity: 'base', numeric: true }
            );
        });
    }

    async function showCustomerHistory(customerName) {
        const name = String(customerName || '').trim();
        state.customerHistoryName = name;
        state.customerHistoryChildOpen = false;
        try {
            const data = await apiGet('orders', { limit: 150, include_archived: 1 });
            const orders = (data.orders || [])
                .filter(order => fold(order.customer_name) === fold(name))
                .sort((first, second) => String(second.created_at || '').localeCompare(String(first.created_at || '')));
            openModal(`
                <section class="customer-history">
                    <header>
                        <p class="eyebrow">HISTORIAL DE VENTAS</p>
                        <h2 id="modal-title">${escapeHtml(name)}</h2>
                        <p class="empty-copy">${orders.length} ${orders.length === 1 ? 'venta registrada' : 'ventas registradas'}.</p>
                    </header>
                    <div class="customer-history-table-wrap">
                        <table class="customer-history-table">
                            <thead><tr><th>Venta</th><th>Fecha</th><th>Productos</th><th>Total</th><th>Estado</th></tr></thead>
                            <tbody>${orders.map(order => `
                                <tr>
                                    <td><button class="customer-history-link" type="button" data-history-order="${Number(order.id)}" data-history-customer="${escapeHtml(name)}">${escapeHtml(order.public_number)}</button></td>
                                    <td>${escapeHtml(argentinaDateLabel(order.created_at))}</td>
                                    <td><button class="customer-history-link" type="button" data-history-products="${Number(order.id)}" data-history-customer="${escapeHtml(name)}">${Number(order.unit_count)} unid.</button></td>
                                    <td><strong>${money(order.total_cents)}</strong></td>
                                    <td>${escapeHtml(order.archived_at ? 'Archivada' : (statusLabels[order.status] || order.status))}</td>
                                </tr>
                            `).join('') || '<tr><td colspan="5">No hay ventas para este cliente.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
            `);
        } catch (error) {
            toast(error.message);
        }
    }

    async function showOrderDetail(orderId, historyName = '') {
        if (historyName) {
            state.customerHistoryName = historyName;
            state.customerHistoryChildOpen = true;
        }
        try {
            const data = await apiGet('order', { id: orderId });
            const order = data.order;
            const actionOrder = { ...order, payment_proof_id: null };

            openModal(`
                <section class="order-detail">
                    <header class="order-detail-head">
                        <div>
                            <p class="eyebrow">DETALLE DE LA VENTA</p>
                            <h2 id="modal-title">${escapeHtml(order.public_number)}</h2>
                            <span class="status-pill status-${escapeHtml(order.archived_at ? 'archived' : order.status)}">${escapeHtml(order.archived_at ? 'Archivada' : (statusLabels[order.status] || order.status))}</span>
                        </div>
                        <div class="order-detail-head-actions">
                            ${historyName ? `<button class="small-button" type="button" data-customer-history="${escapeHtml(historyName)}">← Historial</button>` : ''}
                            ${order.archived_at ? '' : `<button class="small-button" type="button" data-archive-order="${Number(order.id)}">Archivar</button>`}
                            <button class="small-button" type="button" data-reopen-order="${Number(order.id)}">Reabrir</button>
                            ${order.archived_at || order.status === 'cancelled' ? '' : `<button class="small-button danger-button" type="button" data-cancel-order="${Number(order.id)}">Cancelar Venta</button>`}
                        </div>
                    </header>
                    <div class="order-detail-meta">
                        <div><span>CLIENTE</span><strong>${escapeHtml(order.customer_name)}</strong><small>${escapeHtml(order.customer_phone || order.customer_email || 'Sin contacto informado')}</small></div>
                        <div><span>FECHA</span><strong>${escapeHtml(argentinaDateLabel(order.created_at))}</strong><small>${escapeHtml(order.archived_at ? 'Venta archivada' : (order.status === 'cancelled' ? 'Venta cancelada' : 'Venta activa'))}</small></div>
                    </div>
                    <div class="order-detail-lines">
                        ${sortedOrderItems(order.items).map(item => `
                            <div class="order-detail-line">
                                <div>
                                    <div class="order-detail-product">
                                        ${safeImage(item.image_path) ? `<img src="${escapeHtml(safeImage(item.image_path))}" alt="">` : '<span class="order-detail-product-placeholder">SIN FOTO</span>'}
                                        <span class="order-detail-product-copy">
                                            <strong>${escapeHtml(item.product_name)}</strong>
                                            <small class="order-detail-sku">SKU: ${escapeHtml(item.sku || 'Sin SKU')}</small>
                                            ${fold(item.variant_name) === 'unica' ? '' : `<small>${escapeHtml(item.variant_name || '')}</small>`}
                                            <span class="order-detail-quantity">${Number(item.quantity)} &times; ${money(item.unit_price_cents)}</span>
                                        </span>
                                    </div>
                                </div>
                                <strong>${money(item.line_total_cents)}</strong>
                            </div>
                        `).join('')}
                    </div>
                    <div class="order-detail-total"><span>TOTAL</span><strong>${money(order.total_cents)}</strong></div>
                    <div class="order-actions order-detail-actions">${orderActions(actionOrder)}</div>
                    <p class="order-print-note">La impresión incluye la compra y el total.</p>
                </section>
            `);
        } catch (error) {
            toast(error.message);
        }
    }

    async function showOrderProducts(orderId, historyName = '') {
        if (historyName) {
            state.customerHistoryName = historyName;
            state.customerHistoryChildOpen = true;
        }
        try {
            const data = await apiGet('order', { id: orderId });
            const order = data.order;
            openModal(`
                <section class="order-products-preview">
                    <header>
                        <p class="eyebrow">PRODUCTOS DE LA VENTA</p>
                        <h2 id="modal-title">${escapeHtml(order.public_number)}</h2>
                        ${historyName ? `<button class="customer-history-back" type="button" data-customer-history="${escapeHtml(historyName)}">← Volver al historial</button>` : ''}
                    </header>
                    <div class="order-detail-lines">
                        ${sortedOrderItems(order.items).map(item => `
                            <div class="order-detail-line">
                                <div class="order-detail-product">
                                    ${safeImage(item.image_path) ? `<img src="${escapeHtml(safeImage(item.image_path))}" alt="">` : '<span class="order-detail-product-placeholder">SIN FOTO</span>'}
                                    <span class="order-detail-product-copy">
                                        <strong>${escapeHtml(item.product_name)}</strong>
                                        <small class="order-detail-sku">SKU: ${escapeHtml(item.sku || 'Sin SKU')}</small>
                                        ${fold(item.variant_name) === 'unica' ? '' : `<small>${escapeHtml(item.variant_name || '')}</small>`}
                                        <span class="order-detail-quantity">${Number(item.quantity)} &times; ${money(item.unit_price_cents)}</span>
                                    </span>
                                </div>
                                <strong>${money(item.line_total_cents)}</strong>
                            </div>
                        `).join('')}
                    </div>
                    <div class="order-detail-total"><span>TOTAL</span><strong>${money(order.total_cents)}</strong></div>
                </section>
            `);
        } catch (error) {
            toast(error.message);
        }
    }

    function orderIsInDateRange(order) {
        if (!state.orderDateRange) return true;
        const dateText = String(order.created_at || '').slice(0, 10);
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateText)) return false;
        const orderDate = new Date(`${dateText}T00:00:00`);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (state.orderDateRange === 'today') {
            return orderDate.getTime() === today.getTime();
        }
        if (state.orderDateRange === 'week') {
            const firstDay = new Date(today);
            firstDay.setDate(today.getDate() - 6);
            return orderDate >= firstDay && orderDate <= today;
        }
        if (state.orderDateRange === 'month') {
            return orderDate.getFullYear() === today.getFullYear()
                && orderDate.getMonth() === today.getMonth();
        }
        return true;
    }

    function orderMatchesFilters(order) {
        const query = fold(state.orderQuery.trim());
        const matchesQuery = !query || fold([
            order.public_number,
            order.customer_name,
            order.customer_email,
            order.customer_phone,
        ].join(' ')).includes(query);
        return matchesQuery
            && (!state.orderStatus || order.status === state.orderStatus)
            && (!state.orderChannel || order.channel === state.orderChannel)
            && (!state.orderPayment || order.payment_method === state.orderPayment)
            && orderIsInDateRange(order);
    }

    function orderIsInDeliveries(order) {
        return Boolean(order?.delivery_slot_number && !order?.delivery_reopened_at);
    }

    function renderOrders() {
        const matchingOrders = state.orders
            .filter(orderMatchesFilters)
            .sort((first, second) => String(second.created_at || '').localeCompare(String(first.created_at || '')));
        elements.orderList?.classList.toggle('hide-status-column', !state.showArchivedOrders);
        const actionsBar = document.getElementById('order-actions-bar');
        const selectedCount = selectedOrders().length;
        if (actionsBar) actionsBar.hidden = selectedCount === 0;
        const selectedCountLabel = document.getElementById('selected-orders-count');
        if (selectedCountLabel) {
            selectedCountLabel.textContent = `${selectedCount} ${selectedCount === 1 ? 'venta seleccionada' : 'ventas seleccionadas'}`;
        }
        const individualPrintOption = elements.bulkOrderAction?.querySelector('option[value="print_individual"]');
        const groupedPrintOption = elements.bulkOrderAction?.querySelector('option[value="print_grouped"]');
        if (individualPrintOption) individualPrintOption.textContent = selectedCount === 1
            ? 'Imprimir venta'
            : `Imprimir ${selectedCount} ventas individuales`;
        if (groupedPrintOption) {
            groupedPrintOption.textContent = selectedCount > 1
                ? `Imprimir y agrupar ${selectedCount} ventas`
                : 'Imprimir y agrupar ventas (elegí 2 o más)';
            groupedPrintOption.disabled = selectedCount < 2;
        }
        const singularSelection = selectedCount === 1;
        const bulkActionsLabel = document.getElementById('bulk-order-actions-label');
        if (bulkActionsLabel) {
            bulkActionsLabel.textContent = singularSelection
                ? 'ACCIONES SOBRE LA VENTA SELECCIONADA'
                : 'ACCIONES SOBRE LAS VENTAS SELECCIONADAS';
        }
        [
            ['pass_to_deliveries', 'Pasar'],
            ['archive', 'Archivar'],
            ['cancel', 'Cancelar'],
            ['reopen', 'Reabrir'],
        ].forEach(([action, label]) => {
            const option = elements.bulkOrderAction?.querySelector(`option[value="${action}"]`);
            if (option) option.textContent = `${label} ${singularSelection ? 'Venta' : 'Ventas'}`;
        });
        const selected = selectedOrders();
        const cancelOption = elements.bulkOrderAction?.querySelector('option[value="cancel"]');
        if (cancelOption) {
            const hasArchived = selected.some(order => Boolean(order.archived_at));
            const hasActiveCancellable = selected.some(order => !order.archived_at && order.status !== 'cancelled');
            cancelOption.disabled = hasArchived || !hasActiveCancellable;
            if (hasArchived) cancelOption.textContent = 'Cancelar no disponible para archivadas';
        }

        // Las acciones masivas se muestran junto a la selección total.

        if (false && elements.orderOverview) {
            const countStatus = status => state.orders.filter(order => (
                order.status === status
            )).length;
            elements.orderOverview.innerHTML = `
                <article>
                    <span>TOTAL</span>
                    <strong>${state.orders.length}</strong>
                    <small>pedidos y ventas</small>
                </article>
                <article class="attention">
                    <span>NUEVAS</span>
                    <strong>${countStatus('pending_payment')}</strong>
                    <small>por revisar</small>
                </article>
                <article>
                    <span>PREPARAR</span>
                    <strong>${countStatus('paid_prepare')}</strong>
                    <small>en preparación</small>
                </article>
                <article class="ready">
                    <span>LISTOS</span>
                    <strong>${countStatus('ready_pickup')}</strong>
                    <small>esperando retiro</small>
                </article>
            `;
        }

        if (false && elements.orderOverview) {
            const countFor = statuses => state.orders.filter(order => statuses.includes(order.status)).length;
            const openCount = countFor(['pending_payment', 'payment_reported', 'paid_prepare', 'ready_pickup', 'rejected']);
            if (elements.openOrdersCount) {
                elements.openOrdersCount.textContent = `${openCount} abiertas`;
            }
            elements.orderOverview.innerHTML = `
                <button class="order-state-tab ${!state.orderStatus ? 'active' : ''}" type="button" data-order-status="">Todas <b>${state.orders.length}</b></button>
                <button class="order-state-tab ${['pending_payment', 'payment_reported'].includes(state.orderStatus) ? 'active' : ''}" type="button" data-order-status="pending_payment">Nuevas <b>${countFor(['pending_payment', 'payment_reported'])}</b></button>
                <button class="order-state-tab ${state.orderStatus === 'paid_prepare' ? 'active' : ''}" type="button" data-order-status="paid_prepare">Por empaquetar <b>${countFor(['paid_prepare'])}</b></button>
                <button class="order-state-tab ${state.orderStatus === 'ready_pickup' ? 'active' : ''}" type="button" data-order-status="ready_pickup">Por retirar <b>${countFor(['ready_pickup'])}</b></button>
                <button class="order-state-tab ${state.orderStatus === 'delivered' ? 'active' : ''}" type="button" data-order-status="delivered">Por archivar <b>${countFor(['delivered'])}</b></button>
            `;
        }

        const openOrdersInDeliveries = state.orders.filter(order => (
            !order.archived_at
            && order.status !== 'cancelled'
            && orderIsInDeliveries(order)
        ));

        if (matchingOrders.length) {
            elements.orderList.innerHTML = `
                ${openOrdersInDeliveries.length ? `
                    <div class="order-delivery-reminder" role="status">
                        <span class="order-delivery-reminder-icon" aria-hidden="true">✓</span>
                        <span><strong>${openOrdersInDeliveries.length === 1 ? '1 VENTA ABIERTA YA ESTÁ EN ENTREGAS' : `${openOrdersInDeliveries.length} VENTAS ABIERTAS YA ESTÁN EN ENTREGAS`}</strong><small>Quedaron señaladas en violeta. Cuando el pedido esté listo, archivá la venta para que no quede pendiente.</small></span>
                    </div>
                ` : ''}
                <div class="order-list-head" aria-hidden="true">
                    <span class="order-select-control"></span><span>VENTA</span><span>CLIENTE</span><span>TOTAL</span><span>PRODUCTOS</span><span>ESTADO</span><span></span><span></span><span></span><span>FECHA</span>
                </div>
                ${matchingOrders.map(order => {
                    const inDeliveries = orderIsInDeliveries(order);
                    const deliverySlot = inDeliveries ? Number(order.delivery_slot_number) : null;
                    return `
                    <div class="order-list-row ${inDeliveries && !order.archived_at && order.status !== 'cancelled' ? 'order-list-row-in-deliveries' : ''}" role="button" tabindex="0" data-view-order="${Number(order.id)}">
                        <span class="order-select-control"><input data-select-order="${Number(order.id)}" type="checkbox" ${state.selectedOrderIds.has(Number(order.id)) ? 'checked' : ''} aria-label="Seleccionar ${escapeHtml(order.public_number)}"></span>
                        <span class="order-list-number"><strong>${escapeHtml(order.public_number)}</strong>${order.archived_at ? '<small>Archivada</small>' : `<button class="order-list-archive" type="button" data-archive-order="${Number(order.id)}" aria-label="Archivar ${escapeHtml(order.public_number)}" title="Archivar venta"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5h16v12H4zM3 4h18v3.5H3zM9 12h6"/></svg></button>`}${inDeliveries && !order.archived_at && order.status !== 'cancelled' ? `<small class="order-delivery-note">✓ EN ENTREGAS · FILA ${deliverySlot}</small>` : ''}</span>
                        <button class="order-list-customer" type="button" data-customer-history="${escapeHtml(order.customer_name)}" aria-label="Ver historial de ${escapeHtml(order.customer_name)}">${escapeHtml(order.customer_name)}</button>
                        <strong class="order-list-total">${money(order.total_cents)}</strong>
                        <button class="order-list-units" type="button" data-preview-order="${Number(order.id)}" aria-label="Ver productos de ${escapeHtml(order.public_number)}">${Number(order.unit_count)} unid.⌄</button>
                        <span><span class="status-pill status-${escapeHtml(order.archived_at ? 'archived' : (inDeliveries ? 'copied' : order.status))}">${escapeHtml(order.archived_at ? 'Archivada' : (inDeliveries ? `EN ENTREGAS · ${deliverySlot}` : (statusLabels[order.status] || order.status)))}</span></span>
                        <button class="order-list-copy" type="button" data-copy-order-delivery="${Number(order.id)}" ${inDeliveries ? 'disabled' : ''} aria-label="Copiar ${escapeHtml(order.public_number)} a Entregas" title="Copiar a Entregas">⇠</button>
                        <button class="order-list-print" type="button" data-print-order="${Number(order.id)}" aria-label="Imprimir ${escapeHtml(order.public_number)}" title="Imprimir">⎙</button>
                        ${order.status !== 'cancelled' && !order.archived_at
                            ? `<button class="order-list-delete" type="button" data-cancel-order="${Number(order.id)}" aria-label="Cancelar ${escapeHtml(order.public_number)}" title="Cancelar venta">🗑</button>`
                            : '<span class="order-list-delete-placeholder" aria-hidden="true"></span>'}
                        <span class="order-list-date">${escapeHtml(argentinaDateParts(order.created_at).date)}<small>${escapeHtml(argentinaDateParts(order.created_at).time)}</small></span>
                    </div>
                `}).join('')}
            `;
            return;
        }

        // Conservamos la estructura de la tabla cuando no hay resultados: así la lista no
        // "salta" ni cambia de tamaño mientras se escribe una búsqueda.
        elements.orderList.innerHTML = `
            <div class="order-list-head" aria-hidden="true">
                <span></span><span>VENTA</span><span>CLIENTE</span><span>TOTAL</span><span>PRODUCTOS</span><span>ESTADO</span><span></span><span></span><span></span><span>FECHA</span>
            </div>
            <div class="order-list-empty" role="status">
                <strong>${state.orders.length ? 'NO HAY COINCIDENCIAS' : 'TODAVÍA NO HAY PEDIDOS'}</strong>
                <span>${state.orders.length
                    ? 'Probá con otro número, nombre o apellido.'
                    : 'Los pedidos web y las ventas de mostrador aparecerán aquí.'}</span>
            </div>
        `;
    }

    async function showOrderEditor(orderId) {
        try {
            const data = await apiGet('order', { id: orderId });
            const order = data.order;
            const quantities = new Map();
            const originalQuantities = new Map();
            const snapshots = new Map();

            order.items.forEach(item => {
                const variantId = Number(item.variant_id);
                const quantity = Number(item.quantity);
                quantities.set(variantId, quantity);
                originalQuantities.set(variantId, quantity);
                snapshots.set(variantId, item);
            });

            state.editOrder = {
                order,
                quantities,
                originalQuantities,
                snapshots,
                query: '',
            };

            openModal(`
                <h2 id="modal-title">EDITAR ${escapeHtml(order.public_number)}</h2>
                <p class="empty-copy">
                    Agregá, quitá o cambiá cantidades. Si el pedido ya reservó
                    stock, la reserva se ajustará al guardar.
                </p>
                <form id="order-edit-form">
                    <div class="search-wrap order-edit-search-wrap">
                        <label for="order-edit-search">AGREGAR PRODUCTO</label>
                        <input
                            id="order-edit-search"
                            type="search"
                            autocomplete="off"
                            autocorrect="off"
                            autocapitalize="none"
                            spellcheck="false"
                            aria-autocomplete="none"
                            placeholder="Producto o variante"
                        >
                        <div id="order-edit-suggestions" class="suggestions"></div>
                    </div>
                    <div id="order-edit-lines" class="order-edit-lines"></div>
                    <div class="order-total">
                        <span>NUEVO TOTAL</span>
                        <strong id="order-edit-total">$ 0</strong>
                    </div>
                    <button class="primary-button" id="save-order-edit" type="submit">
                        GUARDAR CAMBIOS
                    </button>
                </form>
            `);
            renderOrderEditor();
        } catch (error) {
            toast(error.message);
        }
    }

    function renderOrderEditor() {
        const editing = state.editOrder;
        const linesElement = document.getElementById('order-edit-lines');
        const totalElement = document.getElementById('order-edit-total');
        const saveButton = document.getElementById('save-order-edit');
        if (!editing || !linesElement || !totalElement || !saveButton) {
            return;
        }

        const index = allVariantIndex();
        let total = 0;
        const lines = Array.from(editing.quantities, ([variantId, quantity]) => {
            const indexed = index.get(Number(variantId));
            const snapshot = editing.snapshots.get(Number(variantId));
            const unitPrice = Number(
                indexed?.variant.price_cents ?? snapshot?.unit_price_cents ?? 0
            );
            const originalQuantity = Number(
                editing.originalQuantities.get(Number(variantId)) || 0
            );
            const reservationCredit = editing.order.stock_reserved_at
                ? originalQuantity
                : 0;
            const available = Number(indexed?.variant.available_stock || 0)
                + reservationCredit;
            const max = Math.max(quantity, available);
            total += unitPrice * quantity;

            return {
                variantId,
                quantity,
                max,
                unitPrice,
                productName: indexed?.product.name || snapshot?.product_name || 'Producto',
                variantName: indexed
                    ? variantDisplayName(indexed.product, indexed.variant)
                    : (
                        fold(snapshot?.variant_name || '') === 'unica'
                            ? ''
                            : snapshot?.variant_name || 'Variante'
                    ),
                active: Boolean(indexed?.product.active && indexed?.variant.active),
            };
        });

        linesElement.innerHTML = lines.length ? lines.map(line => `
            <div class="order-edit-line">
                <div>
                    <strong>${escapeHtml(line.productName)}</strong><br>
                    <small>
                        ${line.variantName ? `${escapeHtml(line.variantName)} · ` : ''}${money(line.unitPrice)}
                        ${line.active ? '' : ' · INACTIVA'}
                    </small>
                </div>
                <div class="quantity-control">
                    <button type="button" data-order-edit-quantity="${line.variantId}" data-value="${line.quantity - 1}">−</button>
                    <input
                        type="number"
                        min="1"
                        max="${line.max}"
                        value="${line.quantity}"
                        data-order-edit-input="${line.variantId}"
                    >
                    <button type="button" data-order-edit-quantity="${line.variantId}" data-value="${line.quantity + 1}" ${line.quantity >= line.max ? 'disabled' : ''}>+</button>
                </div>
                <strong>${money(line.unitPrice * line.quantity)}</strong>
                <button class="small-button danger-button" type="button" data-order-edit-remove="${line.variantId}">
                    Quitar
                </button>
            </div>
        `).join('') : '<p class="empty-copy">El pedido debe conservar al menos un producto.</p>';
        totalElement.textContent = money(total);
        saveButton.disabled = lines.length === 0 || lines.some(line => !line.active);
    }

    function setOrderEditQuantity(variantId, requested) {
        const editing = state.editOrder;
        if (!editing) {
            return;
        }
        const indexed = allVariantIndex().get(Number(variantId));
        const originalQuantity = Number(
            editing.originalQuantities.get(Number(variantId)) || 0
        );
        const reservationCredit = editing.order.stock_reserved_at
            ? originalQuantity
            : 0;
        const max = Number(indexed?.variant.available_stock || 0)
            + reservationCredit;
        const quantity = Math.max(1, Math.min(max, Number(requested) || 1));
        editing.quantities.set(Number(variantId), quantity);
        renderOrderEditor();
    }

    function showOrderEditSuggestions() {
        const editing = state.editOrder;
        const container = document.getElementById('order-edit-suggestions');
        if (!editing || !container) {
            return;
        }
        const query = fold(editing.query.trim());
        if (!query) {
            container.classList.remove('open');
            container.innerHTML = '';
            return;
        }

        const matches = [];
        state.products.filter(product => product.active).forEach(product => {
            product.variants.filter(variant => (
                variant.active
                && Number(variant.available_stock) > 0
                && fold([
                    product.name,
                    product.category?.name,
                    variant.name,
                    variant.sku,
                    variant.barcode,
                ].join(' ')).includes(query)
            )).forEach(variant => {
                matches.push({ product, variant });
            });
        });

        container.innerHTML = matches.slice(0, 8).map(({ product, variant }) => `
            <button class="suggestion-button" type="button" data-order-edit-add="${Number(variant.id)}">
                ${safeImage(product.image_path)
                    ? `<img src="${escapeHtml(safeImage(product.image_path))}" alt="">`
                    : '<span class="suggestion-placeholder"></span>'}
                <span>
                    <strong>${escapeHtml(product.name)}</strong>
                    <small>${variantDisplayName(product, variant)
                        ? `${escapeHtml(variantDisplayName(product, variant))} · `
                        : ''}${Number(variant.available_stock)} disponibles</small>
                </span>
                <strong>${money(variant.price_cents)}</strong>
            </button>
        `).join('') || '<p class="empty-copy">No encontramos variantes disponibles.</p>';
        container.classList.add('open');
    }

    async function saveOrderEditor(form) {
        const editing = state.editOrder;
        if (!editing || editing.quantities.size === 0) {
            toast('El pedido debe contener al menos un producto.');
            return;
        }
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            await apiPost({
                action: 'order_update_items',
                order_id: Number(editing.order.id),
                items: Array.from(
                    editing.quantities,
                    ([variantId, quantity]) => ({
                        variant_id: Number(variantId),
                        quantity: Number(quantity),
                    })
                ),
            });
            closeModal();
            await Promise.all([loadOrders(), loadProducts()]);
            toast('Productos del pedido actualizados.');
        } catch (error) {
            toast(error.message);
            button.disabled = false;
            button.textContent = 'GUARDAR CAMBIOS';
        }
    }

    async function handleOrderAction(orderId, action, shouldRefresh = true) {
        const payloads = {
            approve: { action: 'payment_review', order_id: orderId, decision: 'approve' },
            reject: { action: 'payment_review', order_id: orderId, decision: 'reject' },
            ready: { action: 'order_ready', order_id: orderId },
            deliver: { action: 'order_deliver', order_id: orderId },
            cancel: { action: 'order_cancel', order_id: orderId },
        };
        try {
            await apiPost(payloads[action]);
            if (shouldRefresh) {
                closeModal();
                await Promise.all([loadOrders(), loadProducts()]);
                toast('Pedido actualizado.');
            }
        } catch (error) {
            if (shouldRefresh) {
                toast(error.message);
                return;
            }
            throw error;
        }
    }

    function printStoredOrder(orderId) {
        printReceipt({ id: orderId });
    }

    function printStoredOrders(orderIds, layout = 'grouped') {
        const ids = Array.from(new Set(orderIds.map(Number).filter(id => id > 0)));
        if (!ids.length) return;
        const url = new URL('receipt.php', window.location.href);
        url.searchParams.set('ids', ids.join(','));
        url.searchParams.set('layout', layout === 'individual' ? 'individual' : 'grouped');
        window.open(url.href, '_blank', 'noopener');
    }

    async function loadReports() {
        if (!elements.reportContent) {
            return;
        }
        elements.reportContent.innerHTML = '<p class="empty-copy">Calculando reportes…</p>';
        try {
            const [reportData, backupData] = await Promise.all([
                apiGet('reports'),
                app.user?.role === 'admin'
                    ? apiGet('backups')
                    : Promise.resolve({ backups: [] }),
            ]);
            state.reports = reportData.reports;
            state.backups = backupData.backups;
            renderReports();
        } catch (error) {
            toast(error.message);
        }
    }

    async function createBackup() {
        const button = document.getElementById('create-backup');
        if (button) {
            button.disabled = true;
            button.textContent = 'CREANDO…';
        }
        try {
            await apiPost({ action: 'backup_create' });
            await loadReports();
            toast('Respaldo completo creado en el almacenamiento privado.');
        } catch (error) {
            toast(error.message);
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = 'CREAR RESPALDO';
            }
        }
    }

    function signedNumber(value) {
        const number = Number(value || 0);
        return number > 0 ? `+${number}` : String(number);
    }

    function renderReports() {
        if (!elements.reportContent || !state.reports) {
            return;
        }
        const report = state.reports;
        const summary = report.summary;
        const statusRows = report.status_counts.map(row => `
            <tr>
                <td>${escapeHtml(statusLabels[row.status] || row.status)}</td>
                <td>${Number(row.order_count)}</td>
            </tr>
        `).join('');
        const dailyRows = report.daily_sales.map(row => `
            <tr>
                <td>${escapeHtml(row.sale_day)}</td>
                <td>${Number(row.sale_count)}</td>
                <td>${money(row.total_cents)}</td>
            </tr>
        `).join('');
        const topRows = report.top_products.map(row => `
            <tr>
                <td>${escapeHtml(row.product_name)}<br><small>${escapeHtml(row.variant_name)}</small></td>
                <td>${Number(row.units)}</td>
                <td>${money(row.total_cents)}</td>
            </tr>
        `).join('');
        const lowStockRows = report.low_stock.map(row => `
            <tr>
                <td>${escapeHtml(row.product_name)}<br><small>${escapeHtml(row.variant_name)}</small></td>
                <td>${Number(row.stock_on_hand)}</td>
                <td>${Number(row.stock_reserved)}</td>
                <td><strong>${Number(row.available_stock)}</strong></td>
                <td>${Number(row.min_stock)}</td>
            </tr>
        `).join('');
        const movementRows = report.recent_movements.map(row => `
            <tr>
                <td>${escapeHtml(row.created_at)}</td>
                <td>${escapeHtml(row.product_name)}<br><small>${escapeHtml(row.variant_name)}</small></td>
                <td>${signedNumber(row.on_hand_delta)}</td>
                <td>${signedNumber(row.reserved_delta)}</td>
                <td>${escapeHtml(row.reason)}${row.reference ? `<br><small>${escapeHtml(row.reference)}</small>` : ''}</td>
            </tr>
        `).join('');
        const backupRows = state.backups.map(backup => `
            <tr>
                <td>${escapeHtml(backup.created_at)}</td>
                <td>${escapeHtml(backup.name)}</td>
                <td>${Number(backup.proof_file_count || 0)}</td>
                <td>${Math.max(0, Math.round(Number(backup.database_bytes || 0) / 1024))} KB</td>
            </tr>
        `).join('');

        elements.reportContent.innerHTML = `
            <div class="report-metrics">
                <article>
                    <small>VENTAS DE HOY</small>
                    <strong>${money(summary.today_sale_total_cents)}</strong>
                    <span>${Number(summary.today_sale_count)} operaciones</span>
                </article>
                <article>
                    <small>PEDIDOS ACTIVOS</small>
                    <strong>${Number(summary.active_order_count)}</strong>
                    <span>requieren seguimiento</span>
                </article>
                <article>
                    <small>UNIDADES RESERVADAS</small>
                    <strong>${Number(summary.reserved_unit_count)}</strong>
                    <span>no disponibles para POS</span>
                </article>
                <article>
                    <small>VARIANTES CON STOCK BAJO</small>
                    <strong>${Number(summary.low_stock_variant_count)}</strong>
                    <span>según stock mínimo</span>
                </article>
            </div>
            <div class="report-grid">
                <section class="report-card">
                    <h2>ESTADO DE PEDIDOS</h2>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Estado</th><th>Cantidad</th></tr></thead>
                            <tbody>${statusRows || '<tr><td colspan="2">Sin pedidos.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
                <section class="report-card">
                    <h2>VENTAS DE LOS ÚLTIMOS 14 DÍAS</h2>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Fecha</th><th>Operaciones</th><th>Total</th></tr></thead>
                            <tbody>${dailyRows || '<tr><td colspan="3">Sin ventas.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
                <section class="report-card">
                    <h2>PRODUCTOS MÁS VENDIDOS · 30 DÍAS</h2>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Producto</th><th>Unidades</th><th>Total</th></tr></thead>
                            <tbody>${topRows || '<tr><td colspan="3">Sin ventas.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
                <section class="report-card report-wide">
                    <h2>STOCK BAJO</h2>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Producto</th><th>Físico</th><th>Reservado</th><th>Disponible</th><th>Mínimo</th></tr></thead>
                            <tbody>${lowStockRows || '<tr><td colspan="5">No hay alertas de stock.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
                <section class="report-card report-wide">
                    <h2>MOVIMIENTOS RECIENTES</h2>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Fecha</th><th>Producto</th><th>Físico</th><th>Reserva</th><th>Motivo</th></tr></thead>
                            <tbody>${movementRows || '<tr><td colspan="5">Sin movimientos.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
                <section class="report-card report-wide">
                    <h2>RESPALDOS PRIVADOS</h2>
                    <p class="empty-copy">
                        Cada respaldo incluye una copia consistente de la base
                        y los comprobantes cargados hasta ese momento.
                    </p>
                    <div class="report-table-wrap">
                        <table>
                            <thead><tr><th>Fecha</th><th>Identificador</th><th>Comprobantes</th><th>Base</th></tr></thead>
                            <tbody>${backupRows || '<tr><td colspan="4">Todavía no se creó ningún respaldo.</td></tr>'}</tbody>
                        </table>
                    </div>
                </section>
            </div>
        `;
    }

    async function loadUsers() {
        if (!elements.userList || app.user?.role !== 'admin') {
            return;
        }
        elements.userList.innerHTML = '<p class="empty-copy">Cargando usuarios…</p>';
        try {
            const data = await apiGet('users');
            state.users = data.users;
            renderUsers();
        } catch (error) {
            toast(error.message);
        }
    }

    function renderUsers() {
        if (!elements.userList) {
            return;
        }
        elements.userList.innerHTML = state.users.map(user => `
            <article class="user-card">
                <div>
                    <strong>${escapeHtml(user.name)}</strong><br>
                    <small>${escapeHtml(user.email)}</small>
                </div>
                <span class="status-pill ${Number(user.active) ? '' : 'status-cancelled'}">
                    ${Number(user.active) ? 'ACTIVO' : 'INACTIVO'}
                </span>
                <span>${escapeHtml(user.role === 'admin' ? 'Administrador' : 'Vendedor')}</span>
                <small>Último ingreso: ${escapeHtml(user.last_login_at || 'Nunca')}</small>
                <button class="small-button" type="button" data-edit-user="${Number(user.id)}">
                    Editar
                </button>
            </article>
        `).join('') || '<p class="empty-copy">No hay usuarios.</p>';
    }

    function showUserForm(user = null) {
        openModal(`
            <h2 id="modal-title">${user ? 'EDITAR USUARIO' : 'NUEVO USUARIO'}</h2>
            <form id="user-form" data-user-id="${Number(user?.id || 0)}">
                <label>
                    NOMBRE
                    <input name="name" value="${escapeHtml(user?.name || '')}" required>
                </label>
                <label>
                    EMAIL
                    <input name="email" type="email" value="${escapeHtml(user?.email || '')}" required>
                </label>
                <label>
                    ROL
                    <select name="role">
                        <option value="seller" ${user?.role === 'seller' ? 'selected' : ''}>Vendedor</option>
                        <option value="admin" ${user?.role === 'admin' ? 'selected' : ''}>Administrador</option>
                    </select>
                </label>
                ${user ? `
                    <label>
                        ESTADO
                        <select name="active">
                            <option value="1" ${Number(user.active) ? 'selected' : ''}>Activo</option>
                            <option value="0" ${Number(user.active) ? '' : 'selected'}>Inactivo</option>
                        </select>
                    </label>
                ` : ''}
                <label>
                    ${user ? 'NUEVA CONTRASEÑA · DEJAR VACÍA PARA CONSERVAR' : 'CONTRASEÑA'}
                    <input
                        name="password"
                        type="password"
                        minlength="12"
                        autocomplete="new-password"
                        ${user ? '' : 'required'}
                    >
                </label>
                <button class="primary-button" type="submit">
                    ${user ? 'GUARDAR USUARIO' : 'CREAR USUARIO'}
                </button>
            </form>
        `);
    }

    async function saveUser(form) {
        const data = new FormData(form);
        const userId = Number(form.dataset.userId);
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        try {
            await apiPost({
                action: userId ? 'user_update' : 'user_create',
                user_id: userId || undefined,
                name: data.get('name'),
                email: data.get('email'),
                role: data.get('role'),
                active: userId ? data.get('active') === '1' : true,
                password: data.get('password'),
            });
            closeModal();
            await loadUsers();
            toast(userId ? 'Usuario actualizado.' : 'Usuario creado.');
        } catch (error) {
            toast(error.message);
            button.disabled = false;
        }
    }

    async function loadSettings() {
        const form = document.getElementById('settings-form');
        if (!form || app.user?.role !== 'admin') {
            return;
        }
        try {
            const data = await apiGet('settings');
            state.settings = data.settings;
            Object.entries(data.settings).forEach(([key, value]) => {
                const field = form.elements.namedItem(key);
                if (field) {
                    field.value = value;
                }
            });
        } catch (error) {
            toast(error.message);
        }
    }

    async function loadMaintenance() {
        const form = document.getElementById('maintenance-form');
        if (!form || app.user?.role !== 'admin') return;
        try {
            const data = await apiGet('settings');
            state.settings = data.settings;
            form.elements.cart_maintenance_enabled.checked = ['1', 'true', 'on'].includes(String(data.settings.cart_maintenance_enabled || '0'));
        } catch (error) {
            toast(error.message);
        }
    }

    async function saveMaintenance(form) {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            if (!state.settings) state.settings = (await apiGet('settings')).settings;
            const response = await apiPost({
                action: 'settings_update',
                settings: {
                    ...state.settings,
                    cart_maintenance_enabled: form.elements.cart_maintenance_enabled.checked ? '1' : '0',
                },
            });
            state.settings = response.settings;
            toast(form.elements.cart_maintenance_enabled.checked ? 'Carrito bloqueado para mantenimiento.' : 'Carrito habilitado nuevamente.');
        } catch (error) {
            toast(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'GUARDAR MANTENIMIENTO';
        }
    }

    async function loadContact() {
        const form = document.getElementById('contact-form');
        if (!form || app.user?.role !== 'admin') return;
        try {
            const data = await apiGet('settings');
            state.settings = data.settings;
            Object.entries(data.settings).forEach(([key, value]) => {
                const field = form.elements.namedItem(key);
                if (field) field.value = value;
            });
        } catch (error) {
            toast(error.message);
        }
    }

    async function loadDesign() {
        const form = document.getElementById('design-form');
        if (!form || app.user?.role !== 'admin') return;
        try {
            const data = await apiGet('design');
            Object.entries(data.design).forEach(([key, value]) => {
                const field = form.elements.namedItem(key);
                if (field) field.value = value;
            });
            const preview = document.getElementById('design-logo-preview');
            if (preview) preview.src = data.design.logo_path;
            [1, 2, 3].forEach(number => {
                const image = document.getElementById(`design-hero-${number}-preview`);
                if (image) image.src = data.design[`hero_${number}_path`];
            });
        } catch (error) { toast(error.message); }
    }

    async function saveDesign(form) {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            const logoFile = form.elements.namedItem('logo_file')?.files?.[0];
            if (logoFile) {
                form.elements.namedItem('logo_path').value = await uploadProductImage(logoFile);
            }
            for (const number of [1, 2, 3]) {
                const file = form.elements.namedItem(`hero_${number}_file`)?.files?.[0];
                if (file) form.elements.namedItem(`hero_${number}_path`).value = await uploadProductImage(file);
            }
            const data = new FormData(form);
            data.delete('logo_file');
            data.delete('hero_1_file');
            data.delete('hero_2_file');
            data.delete('hero_3_file');
            const response = await apiPost({ action: 'design_update', design: Object.fromEntries(data.entries()) });
            const preview = document.getElementById('design-logo-preview');
            if (preview) preview.src = response.design.logo_path;
            [1, 2, 3].forEach(number => {
                const image = document.getElementById(`design-hero-${number}-preview`);
                if (image) image.src = response.design[`hero_${number}_path`];
            });
            toast('Diseño guardado. Actualizá la tienda para verlo.');
        } catch (error) { toast(error.message); }
        finally { button.disabled = false; button.textContent = 'GUARDAR DISEÑO'; }
    }

    function previewDesignImage(input, preview) {
        const file = input?.files?.[0];
        if (!file || !preview) return;
        const reader = new FileReader();
        reader.addEventListener('load', () => { preview.src = String(reader.result || ''); });
        reader.readAsDataURL(file);
    }

    async function saveContact(form) {
        const data = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            if (!state.settings) {
                const current = await apiGet('settings');
                state.settings = current.settings;
            }
            const response = await apiPost({
                action: 'settings_update',
                settings: { ...state.settings, ...Object.fromEntries(data.entries()) },
            });
            state.settings = response.settings;
            toast('Contacto guardado.');
        } catch (error) {
            toast(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'GUARDAR CONTACTO';
        }
    }

    async function saveSettings(form) {
        const data = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            const response = await apiPost({
                action: 'settings_update',
                settings: Object.fromEntries(data.entries()),
            });
            state.settings = response.settings;
            toast('Configuración guardada.');
        } catch (error) {
            toast(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'GUARDAR CONFIGURACIÓN';
        }
    }

    async function sendSesTest(form) {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'ENVIANDO…';
        try {
            await apiPost({
                action: 'mail_test',
                recipient: form.elements.recipient.value.trim(),
            });
            toast('Prueba enviada. Revisá la bandeja de entrada y también Correo no deseado.');
        } catch (error) {
            toast(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'ENVIAR PRUEBA';
        }
    }

    async function loadEmailSettings() {
        const form = document.getElementById('whatsapp-settings-form');
        if (!form || app.user?.role !== 'admin') return;
        try {
            const data = await apiGet('settings');
            state.settings = data.settings;
            Object.entries(data.settings).forEach(([key, value]) => {
                const field = form.elements.namedItem(key);
                if (!field) return;
                if (field.type === 'checkbox') {
                    field.checked = String(value) === '1' || String(value) === 'true';
                } else {
                    field.value = value;
                }
            });
        } catch (error) { toast(error.message); }
    }

    async function loadMailDiagnostics() {
        const target = document.getElementById('email-diagnostics');
        if (!target || app.user?.role !== 'admin') return;
        try {
            const { diagnostics } = await apiGet('mail_diagnostics');
            const counts = diagnostics.counts || {};
            const ready = diagnostics.enabled && diagnostics.smtp_ready;
            target.className = `email-diagnostics ${ready ? 'ready' : 'warning'}`;
            target.innerHTML = `<strong>${ready ? 'ConfiguraciÃ³n lista para enviar' : 'Falta completar la configuraciÃ³n SMTP'}</strong><span>${escapeHtml(diagnostics.host || 'Sin servidor')} Â· puerto ${Number(diagnostics.port || 0)} Â· ${escapeHtml(String(diagnostics.encryption || '').toUpperCase())}</span><span>Cola: ${Number(counts.pending || 0)} pendientes, ${Number(counts.sent || 0)} enviados, ${Number(counts.failed || 0)} fallidos.</span>${diagnostics.latest_error ? `<small>Ãšltimo error: ${escapeHtml(diagnostics.latest_error)}</small>` : ''}`;
        } catch (error) {
            target.className = 'email-diagnostics warning';
            target.textContent = `No pudimos leer el diagnÃ³stico: ${error.message}`;
        }
    }

    async function saveEmailSettings(form) {
        const data = new FormData(form);
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO…';
        try {
            if (!state.settings) {
                state.settings = (await apiGet('settings')).settings;
            }
            const values = Object.fromEntries(data.entries());
            const response = await apiPost({
                action: 'settings_update',
                settings: { ...state.settings, ...values },
            });
            state.settings = response.settings;
            toast('Configuración de e-mails guardada.');
        } catch (error) { toast(error.message); }
        finally {
            button.disabled = false;
            button.textContent = 'GUARDAR MENSAJES';
        }
    }

    function sizeGuideRowTemplate(row, index) {
        return `
            <tr data-size-guide-index="${index}">
                <td><input aria-label="Prenda" data-size-guide-field="group" value="${escapeHtml(row.group || '')}" placeholder="Ej.: Remeras adulto" required></td>
                <td><input aria-label="Talle" data-size-guide-field="size" value="${escapeHtml(row.size || '')}" placeholder="Ej.: M" required></td>
                <td><input aria-label="Ancho" data-size-guide-field="width" value="${escapeHtml(row.width || '')}" placeholder="Ej.: 53 cm"></td>
                <td><input aria-label="Largo" data-size-guide-field="length" value="${escapeHtml(row.length || '')}" placeholder="Ej.: 62 cm"></td>
                <td><input aria-label="Observaciones" data-size-guide-field="note" value="${escapeHtml(row.note || '')}" placeholder="Opcional"></td>
                <td class="size-guide-row-actions">
                    <button class="icon-button" type="button" title="Duplicar esta fila" aria-label="Duplicar esta fila" data-duplicate-size-guide-row="${index}">⧉</button>
                    <button class="icon-button danger-button" type="button" title="Eliminar esta fila" aria-label="Eliminar esta fila" data-remove-size-guide-row="${index}">🗑</button>
                </td>
            </tr>
        `;
    }

    function renderSizeGuideRows() {
        if (!elements.sizeGuideRows) {
            return;
        }
        elements.sizeGuideRows.innerHTML = `
            <div class="size-guide-table-wrap">
                <table class="size-guide-edit-table">
                    <thead><tr><th>PRENDA</th><th>TALLE</th><th>ANCHO</th><th>LARGO</th><th>OBSERVACIONES</th><th><span class="sr-only">Acciones</span></th></tr></thead>
                    <tbody>${state.sizeGuide.rows.length
                        ? state.sizeGuide.rows.map(sizeGuideRowTemplate).join('')
                        : '<tr><td class="size-guide-empty-cell" colspan="6">Todavía no cargaste medidas. Usá “Agregar fila” para comenzar.</td></tr>'}</tbody>
                </table>
            </div>`;
    }

    function readSizeGuideRows() {
        if (!elements.sizeGuideRows) {
            return [];
        }
        return Array.from(
            elements.sizeGuideRows.querySelectorAll('[data-size-guide-index]')
        ).map(container => Object.fromEntries(
            Array.from(container.querySelectorAll('[data-size-guide-field]')).map(field => [
                field.dataset.sizeGuideField,
                field.value.trim(),
            ])
        ));
    }

    async function loadSizeGuide() {
        if (!elements.sizeGuideRows || app.user?.role !== 'admin') {
            return;
        }
        try {
            const data = await apiGet('size_guide');
            state.sizeGuide = data.size_guide;
            elements.sizeGuideIntro.value = state.sizeGuide.intro || '';
            renderSizeGuideRows();
        } catch (error) {
            toast(error.message);
        }
    }

    async function saveSizeGuide(form) {
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.textContent = 'GUARDANDO...';
        try {
            const response = await apiPost({
                action: 'size_guide_update',
                size_guide: {
                    intro: elements.sizeGuideIntro.value.trim(),
                    rows: readSizeGuideRows(),
                },
            });
            state.sizeGuide = response.size_guide;
            renderSizeGuideRows();
            toast('Tabla de talles guardada.');
        } catch (error) {
            toast(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'GUARDAR TABLA DE TALLES';
        }
    }

    async function loadCash() {
        if (!elements.cashContent) {
            return;
        }
        try {
            const data = await apiGet('cash');
            state.cash = data.cash;
            renderCash();
        } catch (error) {
            toast(error.message);
        }
    }

    function renderCash() {
        if (!state.cash) {
            elements.cashContent.innerHTML = `
                <div class="cash-card">
                    <p class="eyebrow">ESTADO</p>
                    <h2>CAJA CERRADA</h2>
                    <p class="empty-copy">Abrí la caja para comenzar a registrar ventas de mostrador.</p>
                    <button class="primary-button fit-button" type="button" data-cash-action="open">ABRIR CAJA</button>
                </div>
            `;
            return;
        }
        elements.cashContent.innerHTML = `
            <div class="cash-card">
                <p class="eyebrow">CAJA ABIERTA</p>
                <h2>EFECTIVO ESPERADO</h2>
                <div class="cash-metric">${money(state.cash.expected_now_cents)}</div>
                <p class="empty-copy">
                    Apertura: ${money(state.cash.opening_cents)}<br>
                    Abierta por ${escapeHtml(state.cash.opened_by_name)} · ${escapeHtml(state.cash.opened_at)}
                </p>
                <div class="cash-actions">
                    <button class="secondary-button" type="button" data-cash-action="income">INGRESO</button>
                    <button class="secondary-button" type="button" data-cash-action="expense">EGRESO</button>
                    <button class="primary-button" type="button" data-cash-action="close">ARQUEO Y CIERRE</button>
                </div>
            </div>
        `;
    }

    function showCashForm(action) {
        const labels = {
            open: ['ABRIR CAJA', 'Importe inicial'],
            close: ['ARQUEO Y CIERRE', 'Efectivo contado'],
            income: ['INGRESO DE CAJA', 'Importe'],
            expense: ['EGRESO DE CAJA', 'Importe'],
        };
        const [title, amountLabel] = labels[action];
        openModal(`
            <h2 id="modal-title">${title}</h2>
            <form id="cash-form" data-cash-form="${action}">
                <label>
                    ${amountLabel}
                    <input name="amount" type="number" min="0" step="1" required autofocus>
                </label>
                ${['income', 'expense'].includes(action) ? `
                    <label>
                        DETALLE
                        <input name="detail" required>
                    </label>
                ` : ''}
                <button class="primary-button" type="submit">CONFIRMAR</button>
            </form>
        `);
    }

    async function submitCashForm(form) {
        const action = form.dataset.cashForm;
        const data = new FormData(form);
        const cents = Math.round(Number(data.get('amount')) * 100);
        const payloads = {
            open: { action: 'cash_open', opening_cents: cents },
            close: { action: 'cash_close', counted_cents: cents },
            income: {
                action: 'cash_movement',
                type: 'income',
                amount_cents: cents,
                detail: data.get('detail'),
            },
            expense: {
                action: 'cash_movement',
                type: 'expense',
                amount_cents: cents,
                detail: data.get('detail'),
            },
        };
        try {
            await apiPost(payloads[action]);
            closeModal();
            await loadCash();
            toast('Caja actualizada.');
        } catch (error) {
            toast(error.message);
        }
    }

    document.addEventListener('submit', async event => {
        if (event.target.id === 'login-form') {
            event.preventDefault();
            authenticate(event.target, 'login');
        }
        if (event.target.id === 'setup-form') {
            event.preventDefault();
            authenticate(event.target, 'setup_admin');
        }
        if (event.target.id === 'product-form') {
            event.preventDefault();
            saveProduct(event.target);
        }
        if (event.target.id === 'category-form') {
            event.preventDefault();
            saveCategory(event.target).catch(error => toast(error.message));
        }
        if (event.target.id === 'order-edit-form') {
            event.preventDefault();
            saveOrderEditor(event.target);
        }
        if (event.target.id === 'settings-form') {
            event.preventDefault();
            saveSettings(event.target);
        }
        if (event.target.id === 'ses-test-form') {
            event.preventDefault();
            sendSesTest(event.target);
        }
        if (event.target.id === 'maintenance-form') {
            event.preventDefault();
            saveMaintenance(event.target);
        }
        if (event.target.id === 'whatsapp-settings-form') {
            event.preventDefault();
            saveEmailSettings(event.target);
        }
        if (event.target.id === 'contact-form') {
            event.preventDefault();
            saveContact(event.target);
        }
        if (event.target.id === 'design-form') {
            event.preventDefault();
            saveDesign(event.target);
        }
        if (event.target.id === 'size-guide-form') {
            event.preventDefault();
            saveSizeGuide(event.target);
        }
        if (event.target.id === 'user-form') {
            event.preventDefault();
            saveUser(event.target);
        }
        if (event.target.id === 'product-filters-form') {
            event.preventDefault();
            const data = new FormData(event.target);
            state.productCategoryId = String(data.get('category') || '');
            state.productAvailability = String(data.get('availability') || '');
            state.productVisibility = String(data.get('visibility') || '');
            closeModal();
            renderProducts();
        }
        if (event.target.id === 'featured-products-form') {
            event.preventDefault();
            const ids = Array.from(new FormData(event.target).getAll('product_ids')).map(Number);
            if (ids.length > 8) {
                toast('Podés destacar hasta 8 productos.');
                return;
            }
            try {
                const response = await apiPost({ action: 'featured_products_update', product_ids: ids });
                state.featuredProductIds = new Set((response.featured_product_ids || []).map(Number));
                closeModal();
                renderProducts();
                toast(ids.length ? 'Productos destacados guardados.' : 'No hay productos destacados.');
            } catch (error) {
                toast(error.message);
            }
        }
    });

    document.addEventListener('click', async event => {
        const copyInvitation = event.target.closest('[data-copy-invitation-email]');
        if (copyInvitation) {
            await copyInvitationEmail(String(copyInvitation.dataset.copyInvitationEmail || ''));
            return;
        }
        const invitationStatus = event.target.closest('[data-invitation-status]');
        if (invitationStatus) {
            try {
                await apiPost({
                    action: 'invitation_mark_sent',
                    invitation_id: Number(invitationStatus.dataset.invitationStatus),
                    sent: invitationStatus.dataset.invitationSent === '1',
                });
                await loadInvitations();
                toast(invitationStatus.dataset.invitationSent === '1'
                    ? 'Invitación marcada como enviada.'
                    : 'Invitación marcada nuevamente como pendiente.');
            } catch (error) {
                toast(error.message);
            }
            return;
        }
        const filterButton = event.target.closest('[data-product-filter-button]');
        if (filterButton) {
            const [field, value] = String(filterButton.dataset.productFilterButton).split(':');
            const form = filterButton.closest('#product-filters-form');
            const input = form?.elements.namedItem(field);
            if (input) {
                input.value = input.value === value ? '' : value;
                form.querySelectorAll(`[data-product-filter-button^="${field}:"]`).forEach(button => button.classList.toggle('is-selected', button === filterButton && input.value === value));
            }
            return;
        }
        if (event.target.closest('[data-open-product-filters]')) {
            showProductFilters();
            return;
        }
        if (event.target.closest('[data-open-featured-products]')) {
            showFeaturedProducts();
            return;
        }
        if (event.target.closest('[data-clear-product-filters]')) {
            state.productCategoryId = '';
            state.productAvailability = '';
            state.productVisibility = '';
            closeModal();
            renderProducts();
            return;
        }
        if (event.target.closest('[data-dismiss-pos-stock-warning]')) {
            state.posStockConflicts.clear();
            state.posChangedAvailability.clear();
            renderPos();
            renderPosCart();
            return;
        }
        const whatsappOrder = event.target.closest('[data-whatsapp-order]');
        if (whatsappOrder) {
            openOrderWhatsapp(Number(whatsappOrder.dataset.whatsappOrder));
            return;
        }
        if (event.target.closest('#refresh-mail-diagnostics')) {
            loadMailDiagnostics();
            return;
        }
        const passwordToggle = event.target.closest('.password-toggle');
        if (passwordToggle) {
            const field = passwordToggle.closest('.password-field')?.querySelector('input');
            if (!field) return;
            const showing = field.type === 'text';
            field.type = showing ? 'password' : 'text';
            passwordToggle.textContent = showing ? '◉' : '◉̸';
            passwordToggle.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
            passwordToggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
            return;
        }
        if (event.target.closest('#add-size-guide-row')) {
            state.sizeGuide = {
                intro: elements.sizeGuideIntro?.value.trim() || '',
                rows: readSizeGuideRows(),
            };
            state.sizeGuide.rows.push({
                group: '',
                size: '',
                width: '',
                length: '',
                note: '',
            });
            renderSizeGuideRows();
            window.requestAnimationFrame(() => {
                elements.sizeGuideRows
                    ?.querySelector('tbody tr:last-child input')
                    ?.focus();
            });
            return;
        }
        const duplicateSizeGuideRow = event.target.closest('[data-duplicate-size-guide-row]');
        if (duplicateSizeGuideRow) {
            state.sizeGuide = {
                intro: elements.sizeGuideIntro?.value.trim() || '',
                rows: readSizeGuideRows(),
            };
            const index = Number(duplicateSizeGuideRow.dataset.duplicateSizeGuideRow);
            const source = state.sizeGuide.rows[index];
            if (!source) return;
            state.sizeGuide.rows.splice(index + 1, 0, { ...source, size: '' });
            renderSizeGuideRows();
            window.requestAnimationFrame(() => {
                elements.sizeGuideRows
                    ?.querySelector(`tbody tr:nth-child(${index + 2}) [data-size-guide-field="size"]`)
                    ?.focus();
            });
            return;
        }
        const removeSizeGuideRow = event.target.closest('[data-remove-size-guide-row]');
        if (removeSizeGuideRow) {
            state.sizeGuide = {
                intro: elements.sizeGuideIntro?.value.trim() || '',
                rows: readSizeGuideRows(),
            };
            state.sizeGuide.rows.splice(
                Number(removeSizeGuideRow.dataset.removeSizeGuideRow),
                1
            );
            renderSizeGuideRows();
            return;
        }
        const view = event.target.closest('[data-view]');
        if (view) {
            showView(view.dataset.view);
            return;
        }
        const orderStatusTab = event.target.closest('[data-order-status]');
        if (orderStatusTab) {
            state.orderStatus = orderStatusTab.dataset.orderStatus || '';
            if (elements.orderStatusFilter) elements.orderStatusFilter.value = state.orderStatus;
            renderOrders();
            return;
        }
        if (event.target.closest('#order-auto-cancel-info')) {
            toast('Las reservas en efectivo se cancelan automáticamente al cumplirse las 2 horas.');
            return;
        }
        if (event.target.closest('[data-close-modal]')) {
            closeModal();
            return;
        }
        if (event.target.id === 'new-category-button') {
            showCategoryForm();
            return;
        }
        const editCategory = event.target.closest('[data-edit-category]');
        if (editCategory) {
            const category = flatCategories().find(item => Number(item.id) === Number(editCategory.dataset.editCategory));
            showCategoryForm(category);
            return;
        }
        const deleteCategory = event.target.closest('[data-delete-category]');
        if (deleteCategory) {
            const category = flatCategories().find(item => Number(item.id) === Number(deleteCategory.dataset.deleteCategory));
            if (category && window.confirm(`¿Borrar la categoría “${category.name}”? Los productos quedarán sin categoría y las subcategorías pasarán a ser principales.`)) {
                apiPost({ action: 'category_delete', category_id: category.id }).then(async () => { await loadCategories(); await loadProducts(); toast('Categoría eliminada.'); }).catch(error => toast(error.message));
            }
            return;
        }
        if (event.target.closest('[data-add-variant]')) {
            document.getElementById('variant-form-list')
                .insertAdjacentHTML('beforeend', variantFormRow({ active: true }));
            updateVariantEditorMode();
            return;
        }
        const removeVariant = event.target.closest('[data-remove-variant]');
        if (removeVariant) {
            const list = document.getElementById('variant-form-list');
            if (list.querySelectorAll('[data-variant-row]').length <= 1) {
                toast('El producto debe conservar al menos una variante.');
                return;
            }
            removeVariant.closest('[data-variant-row]').remove();
            updateVariantEditorMode();
            return;
        }
        const editProduct = event.target.closest('[data-edit-product]');
        if (editProduct) {
            const product = state.products.find(item => (
                Number(item.id) === Number(editProduct.dataset.editProduct)
            ));
            showProductForm(product);
            return;
        }
        const productVisibility = event.target.closest('[data-product-visibility]');
        if (productVisibility) {
            setProductsVisibility(
                [Number(productVisibility.dataset.productId)],
                productVisibility.dataset.productVisibility === 'show'
            ).then(closeModal).catch(error => toast(error.message));
            return;
        }
        const deleteProduct = event.target.closest('[data-delete-product]');
        if (deleteProduct) {
            deleteProducts([Number(deleteProduct.dataset.deleteProduct)]).then(deleted => {
                if (deleted && elements.modal.classList.contains('open')) closeModal();
            }).catch(error => toast(error.message));
            return;
        }
        const duplicate = event.target.closest('[data-duplicate-product]');
        if (duplicate) {
            duplicateProduct(Number(duplicate.dataset.duplicateProduct));
            return;
        }
        const share = event.target.closest('[data-share-product]');
        if (share) {
            shareProduct(Number(share.dataset.shareProduct));
            return;
        }
        const editUser = event.target.closest('[data-edit-user]');
        if (editUser) {
            const user = state.users.find(item => (
                Number(item.id) === Number(editUser.dataset.editUser)
            ));
            showUserForm(user);
            return;
        }
        const posQuantity = event.target.closest('[data-pos-quantity]');
        if (posQuantity) {
            setPosQuantity(
                Number(posQuantity.dataset.posQuantity),
                Number(posQuantity.dataset.value)
            );
            return;
        }
        const posOpenProduct = event.target.closest('[data-pos-open-product]');
        if (posOpenProduct) {
            const productId = Number(posOpenProduct.dataset.posOpenProduct);
            state.posProductId = Number(state.posProductId) === productId ? null : productId;
            renderPos();
            return;
        }
        const assignBarcode = event.target.closest('[data-assign-barcode-variant]');
        if (assignBarcode) {
            assignScannedBarcode(Number(assignBarcode.dataset.assignBarcodeVariant));
            return;
        }
        const suggestion = event.target.closest('[data-pos-suggestion]');
        if (suggestion) {
            choosePosProduct(Number(suggestion.dataset.posSuggestion));
            return;
        }
        const confirmCancelOrders = event.target.closest('[data-confirm-cancel-orders]');
        if (confirmCancelOrders) {
            const ids = String(confirmCancelOrders.dataset.confirmCancelOrders || '')
                .split(',')
                .map(Number)
                .filter(Number.isFinite);
            cancelOrders(ids, Boolean(document.getElementById('cancel-notify-customer')?.checked), Boolean(document.getElementById('cancel-restore-stock')?.checked));
            return;
        }
        const cancelOrder = event.target.closest('[data-cancel-order]');
        if (cancelOrder) {
            showCancellationDialog([Number(cancelOrder.dataset.cancelOrder)]);
            return;
        }
        const copyOrder = event.target.closest('[data-copy-order-delivery]');
        if (copyOrder) {
            event.preventDefault();
            event.stopPropagation();
            showCopyToDeliveries(Number(copyOrder.dataset.copyOrderDelivery));
            return;
        }
        const deleteDelivery = event.target.closest('[data-delete-delivery-slot]');
        if (deleteDelivery) {
            deleteDeliverySlot(Number(deleteDelivery.dataset.deleteDeliverySlot));
            return;
        }
        const openReturnDeliverySlot = event.target.closest('[data-open-return-delivery-slot]');
        if (openReturnDeliverySlot) {
            openDeliverySlotReturn(Number(openReturnDeliverySlot.dataset.openReturnDeliverySlot));
            return;
        }
        const printDeliverySlot = event.target.closest('[data-print-delivery-slot]');
        if (printDeliverySlot) {
            openDeliverySlotPrint(Number(printDeliverySlot.dataset.printDeliverySlot));
            return;
        }
        if (event.target.closest('[data-confirm-delivery-slot-print]')) {
            continueDeliverySlotPrint();
            return;
        }
        if (event.target.closest('[data-confirm-delivery-slot-return]')) {
            continueDeliverySlotReturn();
            return;
        }
        const confirmDeleteDelivery = event.target.closest('[data-confirm-delete-delivery-slot]');
        if (confirmDeleteDelivery) {
            const slotNumber = Number(confirmDeleteDelivery.dataset.confirmDeleteDeliverySlot);
            closeModal();
            removeDeliverySlot(slotNumber);
            return;
        }
        const placeDelivery = event.target.closest('[data-place-delivery-orders]');
        if (placeDelivery) {
            const ids = String(placeDelivery.dataset.placeDeliveryOrders || '').split(',').map(Number).filter(Number.isFinite);
            copyOrdersToDelivery(ids, Number(placeDelivery.dataset.placeDeliverySlot));
            return;
        }
        const confirmOtherDeliverySlot = event.target.closest('[data-confirm-delivery-other-slot]');
        if (confirmOtherDeliverySlot) {
            const ids = String(confirmOtherDeliverySlot.dataset.deliveryOrderIds || '').split(',').map(Number).filter(Number.isFinite);
            copyOrdersToDelivery(ids, Number(confirmOtherDeliverySlot.dataset.confirmDeliveryOtherSlot), true);
            return;
        }
        const confirmDeliveryMatchSlot = event.target.closest('[data-confirm-delivery-match-slot]');
        if (confirmDeliveryMatchSlot) {
            const ids = String(confirmDeliveryMatchSlot.dataset.deliveryOrderIds || '').split(',').map(Number).filter(Number.isFinite);
            copyOrdersToDelivery(ids, Number(confirmDeliveryMatchSlot.dataset.confirmDeliveryMatchSlot), true);
            return;
        }
        const clearMarker = event.target.closest('[data-clear-delivery-marker]');
        if (clearMarker) {
            const row = clearMarker.closest('[data-delivery-row]');
            const input = row?.querySelector('[data-delivery-field="customer_name"]');
            if (input) { input.value = String(input.value).replace(/\s*·?\s*(ARMAR|AGREGAR)\s*$/i, '').trim(); saveDeliverySlot(Number(clearMarker.dataset.clearDeliveryMarker), input); }
            return;
        }
        const printDelivery = event.target.closest('[data-print-delivery-order]');
        if (printDelivery) { printStoredOrder(Number(printDelivery.dataset.printDeliveryOrder)); return; }
        const printDeliveryOrders = event.target.closest('[data-print-delivery-orders]');
        if (printDeliveryOrders) {
            const ids = String(printDeliveryOrders.dataset.printDeliveryOrders || '')
                .split(',').map(Number).filter(Number.isFinite);
            printStoredOrders(ids, printDeliveryOrders.dataset.printDeliveryLayout === 'individual' ? 'individual' : 'grouped');
            return;
        }
        const archiveDelivery = event.target.closest('[data-archive-delivery-order]');
        if (archiveDelivery) {
            apiPost({ action: 'order_archive', order_id: Number(archiveDelivery.dataset.archiveDeliveryOrder) })
                .then(async () => { closeModal(); await loadOrders(true); toast('Venta archivada.'); })
                .catch(error => toast(error.message));
            return;
        }
        if (event.target.closest('[data-cancel-delivery-placement]')) {
            state.pendingDeliveryOrderId = 0;
            state.pendingDeliveryOrderIds = [];
            renderDeliverySlots();
            return;
        }
        const confirmCopyDelivery = event.target.closest('[data-confirm-copy-delivery]');
        if (confirmCopyDelivery) {
            copyOrderToDelivery(Number(confirmCopyDelivery.dataset.confirmCopyDelivery), Number(document.getElementById('copy-delivery-slot')?.value || 0));
            return;
        }
        const archiveOrder = event.target.closest('[data-archive-order]');
        if (archiveOrder) {
            archiveSelectedOrders([Number(archiveOrder.dataset.archiveOrder)]);
            return;
        }
        const reopenOrder = event.target.closest('[data-reopen-order]');
        if (reopenOrder) {
            reopenSelectedOrders([Number(reopenOrder.dataset.reopenOrder)]);
            return;
        }
        const posFinish = event.target.closest('[data-pos-sale-finish]');
        if (posFinish) {
            const orderId = Number(posFinish.dataset.orderId);
            if (posFinish.dataset.posSaleFinish === 'print') {
                printReceipt({ id: orderId });
            } else if (posFinish.dataset.posSaleFinish === 'cancel') {
                cancelPosSale(orderId);
            } else {
                archivePosSale(orderId);
            }
            return;
        }
        const posCheckoutAction = event.target.closest('[data-pos-checkout-action]');
        if (posCheckoutAction) {
            const action = posCheckoutAction.dataset.posCheckoutAction;
            if (action === 'cancel') {
                state.posCart.clear();
                persistPosCart();
                renderPosCart();
                closeModal();
                toast('Venta cancelada sin modificar el stock.');
                return;
            }
            posCheckoutAction.disabled = true;
            const receiptWindow = action === 'print' ? window.open('about:blank', '_blank') : null;
            const sale = await createPosSale();
            if (!sale) {
                if (receiptWindow) receiptWindow.close();
                posCheckoutAction.disabled = false;
                return;
            }
            if (action === 'archive') {
                await archivePosSale(sale.id);
            } else if (action === 'print') {
                printReceipt(sale, receiptWindow);
                showPosSaleFinished(sale);
            } else {
                closeModal();
                toast(`Venta ${sale.public_number} registrada.`);
            }
            return;
        }
        if (event.target.closest('[data-select-order], #select-all-orders')) {
            return;
        }
        const printOrder = event.target.closest('[data-print-order]');
        if (printOrder) {
            event.preventDefault();
            event.stopPropagation();
            printStoredOrder(Number(printOrder.dataset.printOrder));
            return;
        }
        const previewOrder = event.target.closest('[data-preview-order]');
        if (previewOrder) {
            event.preventDefault();
            event.stopPropagation();
            showOrderProducts(Number(previewOrder.dataset.previewOrder));
            return;
        }
        const historyOrder = event.target.closest('[data-history-order]');
        if (historyOrder) {
            event.preventDefault();
            event.stopPropagation();
            showOrderDetail(
                Number(historyOrder.dataset.historyOrder),
                historyOrder.dataset.historyCustomer || ''
            );
            return;
        }
        const historyProducts = event.target.closest('[data-history-products]');
        if (historyProducts) {
            event.preventDefault();
            event.stopPropagation();
            showOrderProducts(
                Number(historyProducts.dataset.historyProducts),
                historyProducts.dataset.historyCustomer || ''
            );
            return;
        }
        const customerHistory = event.target.closest('[data-customer-history]');
        if (customerHistory) {
            event.preventDefault();
            event.stopPropagation();
            showCustomerHistory(customerHistory.dataset.customerHistory);
            return;
        }
        const viewOrder = event.target.closest('[data-view-order]');
        if (viewOrder) {
            showOrderDetail(Number(viewOrder.dataset.viewOrder));
            return;
        }
        const orderAction = event.target.closest('[data-order-action]');
        if (orderAction) {
            handleOrderAction(
                Number(orderAction.dataset.orderId),
                orderAction.dataset.orderAction
            );
            return;
        }
        const proofAnalysis = event.target.closest('[data-proof-analysis]');
        if (proofAnalysis) {
            showProofAnalysis(Number(proofAnalysis.dataset.proofAnalysis));
            return;
        }
        const editOrder = event.target.closest('[data-edit-order]');
        if (editOrder) {
            showOrderEditor(Number(editOrder.dataset.editOrder));
            return;
        }
        const addOrderVariant = event.target.closest('[data-order-edit-add]');
        if (addOrderVariant && state.editOrder) {
            const variantId = Number(addOrderVariant.dataset.orderEditAdd);
            const current = Number(state.editOrder.quantities.get(variantId) || 0);
            setOrderEditQuantity(variantId, current + 1);
            state.editOrder.query = '';
            const search = document.getElementById('order-edit-search');
            if (search) {
                search.value = '';
            }
            const suggestions = document.getElementById('order-edit-suggestions');
            if (suggestions) {
                suggestions.classList.remove('open');
                suggestions.innerHTML = '';
            }
            return;
        }
        const orderEditQuantity = event.target.closest('[data-order-edit-quantity]');
        if (orderEditQuantity) {
            setOrderEditQuantity(
                Number(orderEditQuantity.dataset.orderEditQuantity),
                Number(orderEditQuantity.dataset.value)
            );
            return;
        }
        const removeOrderVariant = event.target.closest('[data-order-edit-remove]');
        if (removeOrderVariant && state.editOrder) {
            state.editOrder.quantities.delete(
                Number(removeOrderVariant.dataset.orderEditRemove)
            );
            renderOrderEditor();
            return;
        }
    });

    document.addEventListener('change', event => {
        if (event.target.matches('[data-delivery-field]')) {
            saveDeliverySlot(Number(event.target.dataset.deliverySlot), event.target);
            return;
        }
        if (event.target.matches('[data-select-product]')) {
            const id = Number(event.target.dataset.selectProduct);
            if (event.target.checked) state.selectedProductIds.add(id);
            else state.selectedProductIds.delete(id);
            renderProducts();
            return;
        }
        if (event.target.id === 'select-all-products') {
            const query = fold(elements.productSearch?.value || '');
            const matching = state.products.filter(product => (
                productSearchText(product).includes(query)
                && (state.productCategoryId === '__unassigned__' ? !product.category?.id : (!state.productCategoryId || Number(product.category?.id) === Number(state.productCategoryId)))
                && (!state.productAvailability || (state.productAvailability === 'in_stock' ? product.variants.some(variant => Number(variant.stock_on_hand || 0) > 0) : !product.variants.some(variant => Number(variant.stock_on_hand || 0) > 0)))
                && (!state.productVisibility || (state.productVisibility === 'visible'
                    ? (product.active === true || product.active === 1 || product.active === '1')
                    : !(product.active === true || product.active === 1 || product.active === '1')))
            ));
            matching.forEach(product => {
                if (event.target.checked) state.selectedProductIds.add(Number(product.id));
                else state.selectedProductIds.delete(Number(product.id));
            });
            renderProducts();
            return;
        }
        if (event.target.matches('[data-product-category-filter]')) {
            state.productCategoryId = event.target.value;
            renderProducts();
            return;
        }
        if (event.target.matches('[data-product-availability-filter]')) {
            state.productAvailability = event.target.value;
            renderProducts();
            return;
        }
        if (event.target.matches('[data-product-visibility-filter]')) {
            state.productVisibility = event.target.value;
            renderProducts();
            return;
        }
        if (event.target.matches('[data-bulk-product-action]') && event.target.value) {
            const action = event.target.value;
            const ids = selectedProducts().map(product => Number(product.id));
            event.target.value = '';
            if (!ids.length) return;
            if (action === 'show') setProductsVisibility(ids, true).catch(error => toast(error.message));
            if (action === 'hide') setProductsVisibility(ids, false).catch(error => toast(error.message));
            if (action === 'delete') deleteProducts(ids).catch(error => toast(error.message));
            return;
        }
        if (event.target.matches('[data-select-order]')) {
            setOrderSelection(
                Number(event.target.dataset.selectOrder),
                Boolean(event.target.checked)
            );
            return;
        }
        if (event.target.id === 'select-all-orders') {
            const matching = state.orders.filter(orderMatchesFilters);
            setAllMatchingOrderSelection(matching, Boolean(event.target.checked));
            return;
        }
        if (event.target.matches('[data-quick-price], [data-quick-stock]')) {
            const variantId = Number(
                event.target.dataset.quickPrice || event.target.dataset.quickStock
            );
            window.clearTimeout(quickUpdateTimers.get(variantId));
            quickUpdateTimers.delete(variantId);
            quickUpdate(variantId, event.target);
        }
        if (event.target.matches('[data-pos-input]')) {
            setPosQuantity(
                Number(event.target.dataset.posInput),
                Number(event.target.value)
            );
        }
        if (event.target.matches('[data-order-edit-input]')) {
            setOrderEditQuantity(
                Number(event.target.dataset.orderEditInput),
                Number(event.target.value)
            );
        }
    });

    document.addEventListener('input', event => {
        if (event.target === elements.deliverySearch) {
            state.deliveryQuery = event.target.value;
            renderDeliverySlots();
            return;
        }
        if (event.target.matches('[data-delivery-field="transfers"]')) {
            const total = event.target.closest('td')?.querySelector('.delivery-transfer-total');
            if (total) total.textContent = transferTotalLabel(event.target.value);
        }
        if (event.target.matches('[data-quick-price], [data-quick-stock]')) {
            scheduleQuickUpdate(event.target);
        }
        if (event.target.id === 'order-edit-search' && state.editOrder) {
            state.editOrder.query = event.target.value;
            showOrderEditSuggestions();
        }
        if (event.target.id === 'barcode-assignment-search') {
            barcodeAssignmentResults(event.target.value);
        }
    });

    document.addEventListener('keydown', event => {
        if (document.getElementById('view-orders') && !event.ctrlKey && !event.altKey && !event.metaKey && event.key === 'F2') {
            event.preventDefault();
            showView('deliveries');
            return;
        }
        if (document.getElementById('view-orders') && !event.ctrlKey && !event.altKey && !event.metaKey && event.key === 'F3') {
            event.preventDefault();
            window.open('pos.php', '_blank');
            return;
        }
        if (event.key !== 'Enter' || !event.target.matches('[data-pos-input]')) return;
        event.preventDefault();
        setPosQuantity(Number(event.target.dataset.posInput), Number(event.target.value));
        event.target.blur();
    });

    document.getElementById('open-deliveries')?.addEventListener('click', () => showView('deliveries'));
    document.getElementById('archive-consumer-final-orders')?.addEventListener('click', () => {
        const consumerFinalOrders = state.orders.filter(order => (
            !order.archived_at
            && fold(order.customer_name) === 'consumidor final'
        ));
        if (!consumerFinalOrders.length) {
            toast('No hay ventas activas de CONSUMIDOR FINAL para archivar.');
            return;
        }
        archiveSelectedOrders(consumerFinalOrders.map(order => Number(order.id)));
    });

    document.addEventListener('click', event => {
        const zone = event.target.closest('[data-image-drop]');
        if (zone) zone.closest('label')?.querySelector('[name="image_file"]')?.click();
    });
    document.addEventListener('dragover', event => {
        const zone = event.target.closest('[data-image-drop]');
        if (zone) { event.preventDefault(); zone.classList.add('dragging'); }
    });
    document.addEventListener('drop', async event => {
        const zone = event.target.closest('[data-image-drop]');
        if (!zone) return;
        event.preventDefault();
        zone.classList.remove('dragging');
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        try {
            await validateProductImage(file);
            const input = zone.closest('label')?.querySelector('[name="image_file"]');
            const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files;
            zone.textContent = `Foto lista: ${file.name}`;
            zone.classList.add('ready');
            showSelectedImage(input, file);
        } catch (error) { toast(error.message); }
    });
    document.addEventListener('change', async event => {
        if (!event.target.matches('[name="image_file"], .variant-image-file')) return;
        const file = event.target.files?.[0];
        if (!file) return;
        try {
            await validateProductImage(file);
            showSelectedImage(event.target, file);
            const zone = event.target.closest('label')?.querySelector('[data-image-drop]');
            if (zone) {
                zone.textContent = `Foto lista: ${file.name}`;
                zone.classList.add('ready');
            }
        } catch (error) {
            event.target.value = '';
            toast(error.message);
        }
    });
    elements.productSearch?.addEventListener('input', () => {
        const canShare = String(elements.productSearch.value || '').trim().length >= 3;
        if (elements.productSearchShare) elements.productSearchShare.disabled = !canShare;
        renderProducts();
    });
    elements.productSearchShare?.addEventListener('click', shareProductSearch);
    elements.orderSearch?.addEventListener('input', event => {
        state.orderQuery = event.target.value;
        renderOrders();
    });
    elements.orderStatusFilter?.addEventListener('change', event => {
        state.orderStatus = event.target.value;
        renderOrders();
    });
    elements.orderChannelFilter?.addEventListener('change', event => {
        state.orderChannel = event.target.value;
        renderOrders();
    });
    elements.orderPaymentFilter?.addEventListener('change', event => {
        state.orderPayment = event.target.value;
        renderOrders();
    });
    elements.orderDateFilter?.addEventListener('change', event => {
        state.orderDateRange = event.target.value;
        renderOrders();
    });
    elements.showArchivedOrders?.addEventListener('change', async event => {
        state.showArchivedOrders = Boolean(event.target.checked);
        state.selectedOrderIds.clear();
        await loadOrders();
    });
    elements.bulkOrderStatus?.addEventListener('change', async () => {
        if (!elements.bulkOrderStatus.value) return;
        await applySelectedOrderStatus();
        elements.bulkOrderStatus.value = '';
    });
    elements.bulkOrderAction?.addEventListener('change', () => {
        const action = elements.bulkOrderAction?.value || '';
        if (!action) return;
        const ids = selectedOrders().map(order => Number(order.id));
        if (!action || !ids.length) {
            toast('Elegí una acción y al menos una venta.');
            return;
        }
        if (action === 'print_individual') {
            printStoredOrders(ids, 'individual');
        } else if (action === 'print_grouped') {
            if (ids.length < 2) {
                toast('Elegí al menos 2 ventas para imprimirlas agrupadas.');
                return;
            }
            printStoredOrders(ids, 'grouped');
        } else if (action === 'pass_to_deliveries') {
            showSelectedOrdersToDeliveries(ids);
        } else if (action === 'cancel') {
            showCancellationDialog(ids);
        } else if (action === 'archive') {
            archiveSelectedOrders(ids);
        } else if (action === 'reopen') {
            reopenSelectedOrders(ids);
        }
        elements.bulkOrderAction.value = '';
    });
    document.addEventListener('change', event => {
        const selector = event.target.closest('[data-bulk-order-action]');
        if (!selector || !selector.value) return;
        const action = selector.value;
        const ids = selectedOrders().map(order => Number(order.id));
        selector.value = '';
        if (!ids.length) {
            toast('Elegí al menos una venta.');
            return;
        }
        if (action === 'print_individual') {
            printStoredOrders(ids, 'individual');
        } else if (action === 'print_grouped') {
            if (ids.length < 2) {
                toast('Elegí al menos 2 ventas para imprimirlas agrupadas.');
                return;
            }
            printStoredOrders(ids, 'grouped');
        } else if (action === 'pass_to_deliveries') {
            showSelectedOrdersToDeliveries(ids);
        } else if (action === 'cancel') {
            showCancellationDialog(ids);
        } else if (action === 'archive') {
            archiveSelectedOrders(ids);
        } else if (action === 'reopen') {
            reopenSelectedOrders(ids);
        }
    });
    elements.posSearch?.addEventListener('input', event => {
        state.posQuery = event.target.value;
        state.posProductId = null;
        renderPos();
        closePosSuggestions();
    });
    elements.posSearch?.addEventListener('keydown', event => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (!scanBarcode(event.target.value)) {
                const value = event.target.value.trim();
                const looksLikeCode = /^[A-Za-z0-9._\-]{3,80}$/.test(value)
                    && (
                        /\d/.test(value)
                        || value === value.toUpperCase()
                    );
                if (looksLikeCode) {
                    offerBarcodeAssignment(value);
                } else {
                    toast('Elegí el producto en la lista de resultados.');
                }
            }
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            if (document.body.classList.contains('pos-search-page')) {
                window.location.href = 'pos.php';
            } else {
                closePosSuggestions();
            }
        }
    });
    document.addEventListener('keydown', event => {
        const isPos = state.view === 'pos' || Boolean(document.querySelector('.pos-page'));
        const target = event.target instanceof Element ? event.target : null;
        const isEditing = Boolean(target?.closest('input, textarea, select, [contenteditable="true"]'));
        const isInteractive = Boolean(target?.closest('button, a'));
        if (
            !isPos
            || elements.modal?.classList.contains('open')
            || event.defaultPrevented
            || event.ctrlKey
            || event.altKey
            || event.metaKey
            || isEditing
        ) {
            return;
        }

        // Espacio siempre lleva a la búsqueda de productos. No modifica cantidades.
        if (event.key === ' ' || event.key === 'Spacebar') {
            event.preventDefault();
            if (elements.posSearch) {
                elements.posSearch.focus();
            } else {
                window.location.href = 'pos-products.php';
            }
            return;
        }

    });
    // Esc es contextual en el PDV: vuelve de la búsqueda a la venta que se
    // estaba armando, pero nunca abandona el Punto de Venta hacia el admin.
    // Las ventanas emergentes se dejan al manejador general para que se cierren.
    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape'
            && !event.defaultPrevented
            && document.body.classList.contains('pos-search-page')
            && !elements.modal?.classList.contains('open')
        ) {
            event.preventDefault();
            event.stopImmediatePropagation();
            window.location.href = 'pos.php';
        }
    }, true);
    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape'
            && !event.defaultPrevented
            && elements.modal?.classList.contains('open')
        ) {
            event.preventDefault();
            closeModal();
        } else if (
            event.key === 'Escape'
            && !event.defaultPrevented
            && state.view === 'deliveries'
        ) {
            event.preventDefault();
            showView('orders');
        }
    });
    document.addEventListener('keydown', captureGlobalBarcode);
    document.addEventListener('click', event => {
        if (!event.target.closest('.pos-search-wrap')) {
            closePosSuggestions();
        }
    });
    elements.completeSale?.addEventListener('click', finishPosSaleDirectly);
    elements.categoryTree?.addEventListener('dragstart', event => {
        const row = event.target.closest('[data-category-row]');
        if (!row) return;
        state.draggedCategoryId = Number(row.dataset.categoryRow);
        row.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(state.draggedCategoryId));
    });
    elements.categoryTree?.addEventListener('dragover', event => {
        const row = event.target.closest('[data-category-row]');
        if (!row || !state.draggedCategoryId || Number(row.dataset.categoryRow) === Number(state.draggedCategoryId)) return;
        event.preventDefault();
        elements.categoryTree.querySelectorAll('.is-drop-target, .is-drop-after').forEach(item => item.classList.remove('is-drop-target', 'is-drop-after'));
        row.classList.add('is-drop-target');
        row.dataset.dropPosition = event.clientY > row.getBoundingClientRect().top + row.getBoundingClientRect().height / 2 ? 'after' : 'before';
        row.classList.toggle('is-drop-after', row.dataset.dropPosition === 'after');
        event.dataTransfer.dropEffect = 'move';
    });
    elements.categoryTree?.addEventListener('drop', async event => {
        const row = event.target.closest('[data-category-row]');
        event.preventDefault();
        elements.categoryTree.querySelectorAll('.is-drop-target, .is-drop-after').forEach(item => item.classList.remove('is-drop-target', 'is-drop-after'));
        if (!row || !state.draggedCategoryId) return;
        const draggedId = Number(state.draggedCategoryId);
        const position = row.dataset.dropPosition || 'before';
        state.draggedCategoryId = null;
        try {
            await moveCategory(draggedId, Number(row.dataset.categoryRow), position);
        } catch (error) {
            toast(error.message);
        }
    });
    elements.categoryTree?.addEventListener('dragend', () => {
        state.draggedCategoryId = null;
        elements.categoryTree.querySelectorAll('.is-dragging, .is-drop-target, .is-drop-after').forEach(item => item.classList.remove('is-dragging', 'is-drop-target', 'is-drop-after'));
    });
    elements.posClearCart?.addEventListener('click', () => {
        state.posCart.clear();
        persistPosCart();
        renderPosCart();
    });
    document.getElementById('new-product-button')?.addEventListener('click', () => {
        showProductForm();
    });
    document.getElementById('new-user-button')?.addEventListener('click', () => {
        showUserForm();
    });
    document.getElementById('create-backup')?.addEventListener('click', createBackup);
    document.getElementById('refresh-orders')?.addEventListener('click', loadOrders);
    const designForm = document.getElementById('design-form');
    designForm?.elements.namedItem('logo_file')?.addEventListener('change', event => {
        previewDesignImage(event.target, document.getElementById('design-logo-preview'));
    });
    [1, 2, 3].forEach(number => {
        designForm?.elements.namedItem(`hero_${number}_file`)?.addEventListener('change', event => {
            previewDesignImage(event.target, document.getElementById(`design-hero-${number}-preview`));
        });
    });
    document.getElementById('restore-default-logo')?.addEventListener('click', () => {
        if (!designForm) return;
        const path = '/v1/assets/brand/logo-laboratorio-digital.png';
        designForm.elements.namedItem('logo_path').value = path;
        const file = designForm.elements.namedItem('logo_file');
        if (file) file.value = '';
        const preview = document.getElementById('design-logo-preview');
        if (preview) preview.src = path;
        toast('Logo preparado. Presioná GUARDAR DISEÑO para publicarlo.');
    });
    elements.mobileView?.addEventListener('change', event => showView(event.target.value));
    document.getElementById('logout-button')?.addEventListener('click', async () => {
        try {
            await apiPost({ action: 'logout' });
            window.location.reload();
        } catch (error) {
            toast(error.message);
        }
    });
    window.addEventListener('storage', event => {
        if (event.key !== POS_CART_STORAGE_KEY || !app.user) {
            return;
        }
        state.posCartRestored = false;
        restoreOrReconcilePosCart(false);
        renderPos();
        renderPosCart();
    });

    window.addEventListener('message', event => {
        if (event.origin !== window.location.origin
            || event.data?.type !== 'laboratorio-pos-sale-completed'
            || !document.getElementById('view-orders')) {
            return;
        }
        const url = new URL(window.location.href);
        url.searchParams.set('view', 'orders');
        window.history.replaceState({}, '', url);
        showView('orders');
        window.focus();
    });

    // Algunos navegadores no conservan document.activeElement mientras un <select>
    // nativo está abierto. Pausamos la sincronización un momento para que el menú
    // de acciones no sea reemplazado antes de poder elegir una opción.
    document.addEventListener('pointerdown', event => {
        if (event.target.closest('[data-bulk-product-action]')) {
            productActionsMenuPauseUntil = Date.now() + 30000;
        }
    }, true);
    document.addEventListener('focusin', event => {
        if (event.target.closest('[data-bulk-product-action]')) {
            productActionsMenuPauseUntil = Date.now() + 30000;
        }
    });

    if (app.user) {
        // Las páginas independientes del PDV no usan la navegación del admin;
        // marcarlas explícitamente permite capturar escaneos desde cualquier foco.
        if (document.querySelector('.pos-page')) {
            state.view = 'pos';
        }
        restorePosCustomer();
        loadProducts();
        if (elements.invitationsBadge) loadInvitations();
        if (elements.ordersBadge) loadOrderNotifications();
        renderPosCart();
        if (document.getElementById('view-orders')) {
            const requestedView = new URL(window.location.href).searchParams.get('view') || 'orders';
            showView(requestedView);
        }
        window.setInterval(refreshActiveAdminView, 2000);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                refreshActiveAdminView();
            }
        });
        window.addEventListener('focus', refreshActiveAdminView);
        window.addEventListener('pageshow', refreshActiveAdminView);
    }
})();
