(() => {
    'use strict';

    const app = JSON.parse(document.getElementById('admin-app-data').textContent);
    const state = {
        products: [],
        categories: [],
        orders: [],
        orderQuery: '',
        orderStatus: '',
        orderChannel: '',
        orderPayment: '',
        orderDateRange: '',
        selectedOrderIds: new Set(),
        showArchivedOrders: false,
        settings: null,
        sizeGuide: { intro: '', rows: [] },
        users: [],
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
        editOrder: null,
        view: 'products',
    };

    const money = cents => new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        maximumFractionDigits: 0,
    }).format(Number(cents || 0) / 100);

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
        categoryTree: document.getElementById('category-admin-tree'),
        posSearch: document.getElementById('pos-search'),
        posSuggestions: document.getElementById('pos-suggestions'),
        posProducts: document.getElementById('pos-products'),
        posCartLines: document.getElementById('pos-cart-lines'),
        posTotal: document.getElementById('pos-total'),
        completeSale: document.getElementById('complete-sale-button'),
        orderList: document.getElementById('order-list'),
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
    };
    const POS_CART_STORAGE_KEY = `laboratorio-digital:pos-cart:v1:${Number(app.user?.id || 0)}`;

    async function apiGet(action, parameters = {}) {
        const url = new URL(app.api_url, window.location.href);
        url.searchParams.set('action', action);
        Object.entries(parameters).forEach(([key, value]) => {
            url.searchParams.set(key, String(value));
        });
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
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
        const bitmap = await createImageBitmap(file);
        const valid = bitmap.width === 800 && bitmap.height === 800;
        bitmap.close();
        if (!valid) {
            throw new Error('Las fotos deben medir exactamente 800 × 800 píxeles.');
        }
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
        elements.modal.classList.remove('open');
        elements.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        state.editOrder = null;
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
        state.view = view;
        document.querySelectorAll('.admin-view').forEach(section => {
            section.classList.toggle('active', section.id === `view-${view}`);
        });
        document.querySelectorAll('[data-view]').forEach(button => {
            button.classList.toggle('active', button.dataset.view === view);
        });
        if (elements.mobileView) {
            elements.mobileView.value = view;
        }
        if (view === 'orders') {
            loadOrders();
        }
        if (view === 'settings') {
            loadSettings();
        }
        if (view === 'contact') {
            loadContact();
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

    async function loadProducts() {
        try {
            const data = await apiGet('admin_products');
            state.products = data.products;
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
        elements.categoryTree.innerHTML = rows.length ? rows.map(category => `
            <article class="category-admin-row" style="--category-depth:${Number(category.depth)}">
                <div><strong>${Number(category.depth) > 0 ? '↳ ' : ''}${escapeHtml(category.name)}</strong><small>${category.parent_id ? `Subcategoría de ${escapeHtml(namesById.get(Number(category.parent_id)) || 'otra categoría')} · ` : ''}${Number(category.product_count)} productos · orden ${Number(category.sort_order)}${category.active ? '' : ' · inactiva'}</small></div>
                <div class="category-admin-actions"><button class="small-button" type="button" data-edit-category="${Number(category.id)}">Editar</button><button class="small-button danger-button" type="button" data-delete-category="${Number(category.id)}">Borrar</button></div>
            </article>`).join('') : '<p class="empty-copy">Todavía no hay categorías.</p>';
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
        const query = fold(elements.productSearch?.value || '');
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
                                STOCK FÍSICO
                                <input
                                    type="number"
                                    min="${Number(variant.stock_reserved)}"
                                    step="1"
                                    value="${Number(variant.stock_on_hand)}"
                                    data-quick-stock="${Number(variant.id)}"
                                >
                            </label>
                            <div class="reserved-value">
                                ${Number(variant.stock_reserved)} reservadas<br>
                                ${Number(variant.available_stock)} disponibles
                            </div>
                        </div>
                    `).join('')}
                </div>
            </article>
        `).join('') : '<p class="empty-copy">No encontramos productos.</p>';
    }

    function variantFormRow(variant = {}) {
        return `
            <div class="variant-form-row" data-variant-row data-variant-id="${Number(variant.id || 0)}">
                <label>
                    VARIANTE
                    <input class="variant-name" value="${escapeHtml(variant.name || '')}" placeholder="Talle 1" required>
                </label>
                <label>
                    SKU
                    <input class="variant-sku" value="${escapeHtml(variant.sku || '')}" placeholder="REM-VER-1" required>
                </label>
                <label>
                    PRECIO
                    <input class="variant-price" type="number" min="0" value="${Number(variant.price_cents || 0) / 100}" required>
                </label>
                <label>
                    STOCK
                    <input class="variant-stock" type="number" min="${Number(variant.stock_reserved || 0)}" value="${Number(variant.stock_on_hand || 0)}" required>
                </label>
                <button class="small-button danger-button" type="button" data-remove-variant>Quitar</button>
                <label class="variant-image-field">
                    FOTO DE ESTA VARIANTE
                    <input class="variant-image-file" type="file" accept="image/jpeg,image/png,image/webp">
                    <input class="variant-image-path" type="hidden" value="${escapeHtml(variant.image_path || '')}">
                    ${variant.image_path ? `<img class="variant-image-preview" src="${escapeHtml(variant.image_path)}" alt="Foto actual de la variante">` : '<small>Opcional: si no cargás una, se usa la foto del producto.</small>'}
                </label>
                <label>
                    CÓDIGO DE BARRAS
                    <input class="variant-barcode" value="${escapeHtml(variant.barcode || '')}" placeholder="Opcional">
                </label>
                <label>
                    STOCK MÍNIMO
                    <input class="variant-min" type="number" min="0" value="${Number(variant.min_stock || 0)}">
                </label>
                <label>
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
                <label>
                    FOTO DEL PRODUCTO
                    <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
                    <span class="image-drop-zone" data-image-drop data-image-input="image_file">Arrastrá la foto aquí o hacé clic para elegirla<br><small>La imagen debe medir 800 × 800 px.</small></span>
                    <small>JPG, PNG o WebP · máximo 8 MB. Se sube automáticamente al alojamiento.</small>
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
                <h3>VARIANTES</h3>
                <div class="variant-form-list" id="variant-form-list">
                    ${(product?.variants?.length ? product.variants : [{ name: 'Talle 1', active: true }])
                        .map(variantFormRow).join('')}
                </div>
                <div class="button-row">
                    <button class="secondary-button" type="button" data-add-variant>+ VARIANTE</button>
                    <button class="primary-button fit-button" type="submit">GUARDAR PRODUCTO</button>
                </div>
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
            price_cents: Math.round(Number(row.querySelector('.variant-price').value) * 100),
            stock_on_hand: Number(row.querySelector('.variant-stock').value),
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
        const product = readProductForm(form);
        const imageFile = form.querySelector('[name="image_file"]').files[0];
        button.disabled = true;
        try {
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
            toast(productId ? 'Producto actualizado.' : 'Producto creado.');
        } catch (error) {
            toast(error.message);
            button.disabled = false;
            button.textContent = 'GUARDAR PRODUCTO';
        }
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
        try {
            await apiPost({
                action: 'variant_quick_update',
                variant_id: variantId,
                changes: {
                    price_cents: Math.round(Number(priceInput.value) * 100),
                    stock_on_hand: Number(stockInput.value),
                },
            });
            await loadProducts();
            toast('Variante actualizada.');
        } catch (error) {
            toast(error.message);
            await loadProducts();
        }
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
            product.variants.filter(variant => variant.active).forEach(variant => {
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
        persistPosCart();
        renderPos();
        renderPosCart();
        if (state.posQuery.trim()) {
            showPosSuggestions();
        }
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
            state.products.filter(product => product.active)
        ).filter(product => (
            query || product.variants.some(variant => (
                variant.active && Number(variant.available_stock) > 0
            ))
        ));
        elements.posProducts.innerHTML = products.length ? `
            ${query ? `<div class="pos-search-summary"><strong>${products.length}</strong> productos encontrados en todo el catálogo</div>` : ''}
            ${products.map(product => `
            <article class="pos-product ${product.variants.length === 1 || Number(state.posProductId) === Number(product.id) ? 'pos-expanded' : ''}">
                ${safeImage(product.image_path)
                    ? `<img src="${escapeHtml(safeImage(product.image_path))}" alt="">`
                    : '<div class="pos-product-placeholder">SIN FOTO</div>'}
                <div>
                    <div class="pos-product-head">
                        <strong>${escapeHtml(product.name)}</strong>
                        <small>${escapeHtml(product.category?.name || '')}</small>
                    </div>
                    ${product.variants.length > 1 ? `<button class="pos-open-product-button" type="button" data-pos-open-product="${Number(product.id)}">${product.variants.length} variantes ${Number(state.posProductId) === Number(product.id) ? '⌃' : '⌄'}</button>` : ''}
                    ${product.variants.filter(variant => (
                        variant.active
                        && Number(variant.available_stock) > 0
                    )).map(variant => {
                        const quantity = posQuantity(variant.id);
                        const remaining = Math.max(
                            0,
                            Number(variant.available_stock) - quantity
                        );
                        return `
                            <div class="pos-variant-row">
                                <span>
                                    ${variantDisplayName(product, variant)
                                        ? `<strong>${escapeHtml(variantDisplayName(product, variant))}</strong><br>`
                                        : ''}
                                    <small>${escapeHtml(variant.sku)} · ${remaining > 0 ? `${remaining} disponibles` : '<span class="stock-zero">SIN STOCK</span>'}</small>
                                </span>
                                <span>${money(variant.price_cents)}</span>
                                <div class="quantity-control">
                                    <button type="button" data-pos-quantity="${Number(variant.id)}" data-value="${quantity - 1}" ${quantity < 1 ? 'disabled' : ''}>−</button>
                                    <input type="number" min="0" max="${Number(variant.available_stock)}" value="${quantity}" data-pos-input="${Number(variant.id)}">
                                    <button type="button" data-pos-quantity="${Number(variant.id)}" data-value="${quantity + 1}" ${remaining < 1 ? 'disabled' : ''}>+</button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </article>
        `).join('')}` : '<p class="empty-copy">No encontramos productos.</p>';
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
        elements.posCartLines.innerHTML = items.length ? items.map(item => `
            <div class="cart-line">
                <div class="cart-line-head">
                    <div>
                        <strong>${escapeHtml(item.product.name)}</strong><br>
                        ${variantDisplayName(item.product, item.variant)
                            ? `<small>${escapeHtml(variantDisplayName(item.product, item.variant))}</small>`
                            : ''}
                    </div>
                    <strong>${money(Number(item.variant.price_cents) * item.quantity)}</strong>
                </div>
                <div class="cart-line-bottom">
                    <div class="quantity-control">
                        <button type="button" data-pos-quantity="${item.variantId}" data-value="${item.quantity - 1}">−</button>
                        <input type="number" value="${item.quantity}" min="0" max="${Number(item.variant.available_stock)}" data-pos-input="${item.variantId}">
                        <button type="button" data-pos-quantity="${item.variantId}" data-value="${item.quantity + 1}" ${item.quantity >= Number(item.variant.available_stock) ? 'disabled' : ''}>+</button>
                    </div>
                </div>
            </div>
        `).join('') : '<p class="empty-copy">Sin productos.</p>';
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
                <input id="barcode-assignment-search" type="search" autocomplete="off" placeholder="Nombre, talle, SKU o descripción">
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

    async function completeSale() {
        const items = Array.from(state.posCart, ([variantId, quantity]) => ({
            variant_id: variantId,
            quantity,
        }));
        if (!items.length) {
            return;
        }
        elements.completeSale.disabled = true;
        elements.completeSale.textContent = 'COBRANDO…';
        try {
            const data = await apiPost({
                action: 'pos_sale',
                items,
                customer_name: document.getElementById('pos-customer').value,
                payment_method: 'pos',
            });
            const sale = data.order;
            state.posCart.clear();
            persistPosCart();
            await loadProducts();
            renderPosCart();
            showPosSaleFinished(sale);
            toast(`Venta ${sale.public_number} registrada.`);
        } catch (error) {
            toast(error.message);
        } finally {
            elements.completeSale.disabled = state.posCart.size === 0;
            elements.completeSale.textContent = 'COBRAR E IMPRIMIR';
        }
    }

    function printReceipt(order) {
        const receiptUrl = new URL('receipt.php', window.location.href);
        receiptUrl.searchParams.set('id', String(order.id));
        const receipt = window.open(receiptUrl, '_blank');
        if (!receipt) {
            toast('La venta se guardó, pero el navegador bloqueó la impresión.');
        }
    }

    function showPosSaleFinished(sale) {
        openModal(`
            <h2 id="modal-title">VENTA REGISTRADA</h2>
            <p class="checkout-lead">${escapeHtml(sale.public_number)} quedó guardada en Ventas.</p>
            <div class="button-row">
                <button class="secondary-button" type="button" data-pos-sale-finish="archive" data-order-id="${Number(sale.id)}">ARCHIVAR</button>
                <button class="primary-button" type="button" data-pos-sale-finish="print" data-order-id="${Number(sale.id)}">IMPRIMIR</button>
            </div>
            <p class="checkout-footnote">Podés cerrar esta ventana con Esc sin hacer nada más.</p>
        `);
    }

    async function loadOrders() {
        if (!elements.orderList) {
            return;
        }
        elements.orderList.innerHTML = '<p class="empty-copy">Cargando pedidos…</p>';
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
        const cancellable = orders.filter(order => !['delivered', 'cancelled'].includes(order.status));
        if (!cancellable.length) {
            toast('Solo se pueden cancelar ventas que todavía no fueron entregadas.');
            return;
        }
        const skipped = orders.length - cancellable.length;
        openModal(`
            <h2 id="modal-title">CANCELAR ${cancellable.length === 1 ? 'VENTA' : 'VENTAS'}</h2>
            <p class="empty-copy">Vas a cancelar ${cancellable.length} ${cancellable.length === 1 ? 'venta' : 'ventas'} seleccionada${cancellable.length === 1 ? '' : 's'}.</p>
            ${skipped ? `<p class="notice">${skipped} venta${skipped === 1 ? '' : 's'} entregada${skipped === 1 ? '' : 's'} no se puede${skipped === 1 ? '' : 'n'} cancelar y quedará${skipped === 1 ? '' : 'n'} sin cambios.</p>` : ''}
            <label class="order-confirm-option">
                <input id="cancel-notify-customer" type="checkbox" checked>
                <span><strong>Abrir WhatsApp para avisar al cliente</strong><small>Se abrirá el mensaje de cancelación listo para revisar y enviar.</small></span>
            </label>
            <label class="order-confirm-option">
                <input type="checkbox" checked disabled>
                <span><strong>Actualizar stock</strong><small>Las unidades reservadas se liberan automáticamente para que vuelvan a estar disponibles.</small></span>
            </label>
            <div class="modal-actions">
                <button class="danger-button" type="button" data-confirm-cancel-orders="${cancellable.map(order => Number(order.id)).join(',')}">CONFIRMAR CANCELACI&Oacute;N</button>
                <button class="secondary-button" type="button" data-close-modal>VOLVER</button>
            </div>
        `);
    }

    async function cancelOrders(orderIds, notifyCustomer) {
        try {
            for (const orderId of orderIds) {
                await apiPost({
                    action: 'order_cancel',
                    order_id: Number(orderId),
                    notify_customer: false,
                });
                if (notifyCustomer) await openOrderWhatsapp(Number(orderId));
            }
            closeModal();
            state.selectedOrderIds.clear();
            await Promise.all([loadOrders(), loadProducts()]);
            toast('Ventas canceladas y stock actualizado.');
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
        if (!window.confirm(`¿Archivar ${selected.length} ${selected.length === 1 ? 'venta entregada' : 'ventas entregadas'}? Podrás verlas activando “Ver archivadas”.`)) {
            return;
        }
        try {
            for (const order of selected) {
                await apiPost({ action: 'order_archive', order_id: Number(order.id) });
            }
            state.selectedOrderIds.clear();
            await loadOrders();
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

    async function showOrderDetail(orderId) {
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
                        </div>
                        <div class="order-detail-head-actions">
                            <button class="small-button" type="button" data-archive-order="${Number(order.id)}">Archivar</button>
                            <button class="small-button danger-button" type="button" data-cancel-order="${Number(order.id)}">Cancelar</button>
                        </div>
                    </header>
                    <div class="order-detail-meta">
                        <div><span>CLIENTE</span><strong>${escapeHtml(order.customer_name)}</strong><small>${escapeHtml(order.customer_phone || order.customer_email || 'Sin contacto informado')}</small></div>
                        <div><span>FECHA</span><strong>${escapeHtml(order.created_at)}</strong><small>${escapeHtml(channelLabels[order.channel] || order.channel)}</small></div>
                        <div><span>FORMA DE PAGO</span><strong>${escapeHtml(paymentMethodLabels[order.payment_method] || order.payment_method)}</strong><small>${order.payment_method === 'cash' ? `Reserva hasta ${escapeHtml(order.payment_deadline_at || '')}` : 'El cliente avisa la transferencia por WhatsApp.'}</small></div>
                    </div>
                    <div class="order-detail-lines">
                        ${order.items.map(item => `
                            <div class="order-detail-line">
                                <div>
                                    <div class="order-detail-product">
                                        ${safeImage(item.image_path) ? `<img src="${escapeHtml(safeImage(item.image_path))}" alt="">` : '<span class="order-detail-product-placeholder">SIN FOTO</span>'}
                                        <strong>${escapeHtml(item.product_name)}</strong>
                                    </div>
                                    ${fold(item.variant_name) === 'unica' ? '' : `<small>${escapeHtml(item.variant_name || '')}</small>`}
                                </div>
                                <span>${Number(item.quantity)} &times; ${money(item.unit_price_cents)}</span>
                                <strong>${money(item.line_total_cents)}</strong>
                            </div>
                        `).join('')}
                    </div>
                    <div class="order-detail-total"><span>TOTAL</span><strong>${money(order.total_cents)}</strong></div>
                    <section class="order-payment-detail">
                        <div class="order-payment-head">
                            <div><p class="eyebrow">PAGO</p><h3>${order.payment_method === 'cash' ? 'EFECTIVO AL RETIRAR' : 'TRANSFERENCIA'}</h3></div>
                        </div>
                        ${order.payment_method === 'cash'
                            ? `<div class="notice"><strong>Reserva automática por 6 horas.</strong><br>Vence: ${escapeHtml(order.payment_deadline_at || '')}. Si no se entrega, la tarea programada cancela la venta y devuelve las unidades al stock.</div>`
                            : `<p class="empty-copy">El cliente coordina la transferencia por WhatsApp.</p>`}
                    </section>
                    <div class="order-actions order-detail-actions">${orderActions(actionOrder)}</div>
                    <p class="order-print-note">La impresión incluye la compra y el total.</p>
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

    function renderOrders() {
        const matchingOrders = state.orders.filter(orderMatchesFilters);

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

        if (matchingOrders.length) {
            const allMatchingSelected = matchingOrders.every(order => (
                state.selectedOrderIds.has(Number(order.id))
            ));
            elements.orderList.innerHTML = `
                <div class="order-list-head" aria-hidden="true">
                    <label class="order-select-control"><input id="select-all-orders" type="checkbox" ${allMatchingSelected ? 'checked' : ''}><span class="visually-hidden">TODO</span></label><span>VENTA</span><span>FECHA</span><span>CLIENTE</span><span>TOTAL</span><span>PRODUCTOS</span><span>PAGO</span><span>ENVÍO</span><span></span>
                </div>
                ${matchingOrders.map(order => `
                    <div class="order-list-row" role="button" tabindex="0" data-view-order="${Number(order.id)}">
                        <span class="order-select-control"><input data-select-order="${Number(order.id)}" type="checkbox" ${state.selectedOrderIds.has(Number(order.id)) ? 'checked' : ''} aria-label="Seleccionar ${escapeHtml(order.public_number)}"></span>
                        <span class="order-list-number"><strong>${escapeHtml(order.public_number)}</strong>${order.archived_at ? '<small>Archivada</small>' : ''}</span>
                        <span class="order-list-date">${escapeHtml(String(order.created_at || '').replace('T', ' '))}</span>
                        <span><strong>${escapeHtml(order.customer_name)}</strong></span>
                        <strong class="order-list-total">${money(order.total_cents)}</strong>
                        <span class="order-list-units">${Number(order.unit_count)} unid.⌄</span>
                        <span><span class="status-pill status-${escapeHtml(order.status)}">${escapeHtml(order.payment_method === 'cash' ? 'Efectivo' : 'Transferencia')}</span><small>${escapeHtml(paymentMethodLabels[order.payment_method] || order.payment_method)}</small></span>
                        <span><span class="status-pill status-${escapeHtml(order.status)}">${escapeHtml(statusLabels[order.status] || order.status)}</span><small>${escapeHtml(channelLabels[order.channel] || order.channel)}</small></span>
                        <button class="order-list-print" type="button" data-print-order="${Number(order.id)}" aria-label="Imprimir ${escapeHtml(order.public_number)}" title="Imprimir">⎙</button>
                        <span class="order-list-chevron" aria-hidden="true">&rsaquo;</span>
                    </div>
                `).join('')}
            `;
            const selector = document.createElement('select');
            selector.className = 'bulk-actions-inline';
            selector.dataset.bulkOrderAction = '';
            selector.innerHTML = '<option value="">Acciones</option><option value="archive">Archivar</option><option value="cancel">Cancelar</option><option value="reopen">Reabrir</option>';
            document.getElementById('select-all-orders')?.insertAdjacentElement('afterend', selector);
            return;
        }

        elements.orderList.innerHTML = matchingOrders.length ? matchingOrders.map(order => `
            <article class="order-card">
                <div class="order-card-head">
                    <h2>${escapeHtml(order.public_number)}</h2>
                    <span class="status-pill status-${escapeHtml(order.status)}">
                        ${escapeHtml(statusLabels[order.status] || order.status)}
                    </span>
                </div>
                <p>
                    <strong>${escapeHtml(order.customer_name)}</strong><br>
                    ${escapeHtml(channelLabels[order.channel] || order.channel)} ·
                    ${Number(order.unit_count)} unidades<br>
                    ${escapeHtml(order.created_at)}
                </p>
                <h3>${money(order.total_cents)}</h3>
                ${paymentAiBadge(order)}
                <div class="order-actions">${orderActions(order)}</div>
            </article>
        `).join('') : `
            <div class="empty-admin-state">
                <strong>${state.orders.length ? 'NO HAY COINCIDENCIAS' : 'TODAVÍA NO HAY PEDIDOS'}</strong>
                <p>${state.orders.length
                    ? 'Probá cambiando los filtros o la búsqueda.'
                    : 'Los pedidos web y las ventas de mostrador aparecerán aquí.'}</p>
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

    document.addEventListener('submit', event => {
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
        if (event.target.id === 'whatsapp-settings-form') {
            event.preventDefault();
            saveEmailSettings(event.target);
        }
        if (event.target.id === 'contact-form') {
            event.preventDefault();
            saveContact(event.target);
        }
        if (event.target.id === 'size-guide-form') {
            event.preventDefault();
            saveSizeGuide(event.target);
        }
        if (event.target.id === 'user-form') {
            event.preventDefault();
            saveUser(event.target);
        }
    });

    document.addEventListener('click', event => {
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
            cancelOrders(ids, Boolean(document.getElementById('cancel-notify-customer')?.checked));
            return;
        }
        const cancelOrder = event.target.closest('[data-cancel-order]');
        if (cancelOrder) {
            showCancellationDialog([Number(cancelOrder.dataset.cancelOrder)]);
            return;
        }
        const archiveOrder = event.target.closest('[data-archive-order]');
        if (archiveOrder) {
            archiveSelectedOrders([Number(archiveOrder.dataset.archiveOrder)]);
            return;
        }
        const posFinish = event.target.closest('[data-pos-sale-finish]');
        if (posFinish) {
            const orderId = Number(posFinish.dataset.orderId);
            if (posFinish.dataset.posSaleFinish === 'print') {
                printReceipt({ id: orderId });
                closeModal();
            } else {
                archiveSelectedOrders([orderId]);
                closeModal();
            }
            return;
        }
        if (event.target.closest('[data-select-order], #select-all-orders')) {
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
        const printOrder = event.target.closest('[data-print-order]');
        if (printOrder) {
            printStoredOrder(Number(printOrder.dataset.printOrder));
            return;
        }
    });

    document.addEventListener('change', event => {
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
        if (event.target.id === 'order-edit-search' && state.editOrder) {
            state.editOrder.query = event.target.value;
            showOrderEditSuggestions();
        }
        if (event.target.id === 'barcode-assignment-search') {
            barcodeAssignmentResults(event.target.value);
        }
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
        } catch (error) { toast(error.message); }
    });
    elements.productSearch?.addEventListener('input', renderProducts);
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
        if (action === 'cancel') {
            showCancellationDialog(ids);
        } else if (action === 'archive') {
            archiveSelectedOrders(ids);
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
        if (action === 'cancel') {
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
            closePosSuggestions();
        }
    });
    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape'
            && !event.defaultPrevented
            && elements.modal?.classList.contains('open')
        ) {
            event.preventDefault();
            closeModal();
        }
    });
    document.addEventListener('keydown', captureGlobalBarcode);
    document.addEventListener('click', event => {
        if (!event.target.closest('.pos-search-wrap')) {
            closePosSuggestions();
        }
    });
    elements.completeSale?.addEventListener('click', completeSale);
    document.getElementById('new-product-button')?.addEventListener('click', () => {
        showProductForm();
    });
    document.getElementById('new-user-button')?.addEventListener('click', () => {
        showUserForm();
    });
    document.getElementById('refresh-orders')?.addEventListener('click', loadOrders);
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

    if (app.user) {
        loadProducts();
        renderPosCart();
    }
})();
