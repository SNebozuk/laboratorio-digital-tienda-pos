(() => {
    'use strict';
    const panel = document.getElementById('view-meli');
    if (!panel) return;
    const app = JSON.parse(document.getElementById('admin-app-data').textContent);
    let defaults = app.meli_defaults;
    async function openSettings() {
        if (busy || bulkBusy) return;
        const url = new URL(endpoint);
        url.searchParams.set('action', 'defaults');
        try {
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudieron cargar las opciones.');
            defaults = data.defaults;
        } catch (error) { progress(error.message); return; }
        const dialog = document.createElement('dialog');
        dialog.className = 'meli-settings-dialog';
        dialog.setAttribute('aria-label', 'Opciones generales de MeLi');
        const select = (key, label, options) => `<label>${label}<select name="${key}">${options.map(([value, text]) => `<option value="${value}" ${String(defaults[key]) === String(value) ? 'selected' : ''}>${text}</option>`).join('')}</select></label>`;
        const cost = (key, label, cents = true) => `<label>${label}<input name="${key}" type="number" min="0" ${cents ? 'max="10000000"' : 'max="99.99"'} step="0.01" value="${(defaults[key] || 0) / (cents ? 100 : 1)}" required></label>`;
        const check = (key, text) => `<label class="meli-settings-check"><input type="checkbox" name="${key}" ${defaults[key] ? 'checked' : ''}>${text}</label>`;
        dialog.innerHTML = `<header><h2>OPCIONES GENERALES DE MELI</h2><button class="icon-action-button" type="button" aria-label="Cerrar opciones MeLi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg></button></header><p>Se usan en todas las próximas publicaciones. Las publicaciones existentes conservan sus opciones.</p><form><div class="meli-product-grid">${select('condition', 'Condición', [['new','Nuevo'],['used','Usado'],['not_specified','Sin especificar']])}${select('listing_type_id', 'Tipo de publicación', [['gold_special','Clásica'],['gold_pro','Premium'],['free','Gratuita']])}${select('shipping_mode', 'Modalidad de envío', [['me2','Mercado Envíos'],['me1','Mercado Envíos 1'],['custom','Envío propio'],['not_specified','A convenir']])}<label>Logística<input name="logistic_type" value="${escape(defaults.logistic_type)}" required maxlength="40" pattern="[a-z_]{1,40}" list="meli-default-logistics"><datalist id="meli-default-logistics"><option value="xd_drop_off">Despacho en punto MeLi</option><option value="drop_off">Despacho en sucursal</option><option value="cross_docking">Colecta</option><option value="fulfillment">Full</option><option value="self_service">Flex</option><option value="custom">Envío propio</option><option value="not_specified">A convenir</option></datalist></label>${select('rounding_pesos', 'Redondear precio hacia arriba', [[1,'Cada $1'],[10,'Cada $10'],[100,'Cada $100']])}<label>Máximo adicional de cuotas a absorber · %<input name="financing_max_percent" type="number" min="0.01" max="5" step="0.01" value="${defaults.financing_max_percent}" required></label></div><h3>CÁLCULO DEL PRECIO</h3><p>Se conserva el precio base de cada variante como neto objetivo, sumando los gastos siguientes y las comisiones vigentes de MeLi. Las cuotas se descuentan de ese neto, hasta el límite autorizado.</p><div class="meli-product-grid">${cost('packaging_cents','Embalaje por unidad · $')}${cost('shipping_cents','Envío a tu cargo por unidad · $')}${cost('other_fixed_cents','Otros gastos por unidad · $')}${cost('other_percentage','Otros gastos sobre la venta · %',false)}</div><p>Estos gastos se aplican por igual a todos los productos. El peso y las medidas se conservan en cada ficha; las comisiones se consultan al publicar.</p>${check('local_pick_up','Permitir retiro en persona')}${check('free_shipping','Envío gratis a cargo del vendedor')}${check('publish_all_variants','Publicar todas las variantes activas con stock')}${check('installments','Ofrecer 3 a 12 cuotas con interés bajo')}<p>Las cuotas se absorben sin aumentar el precio calculado. Se verifica el costo vigente de MeLi antes de publicar; si supera el límite o la categoría no admite la modalidad, se detiene la publicación.</p><p role="status" data-settings-result></p><button class="primary-button" type="submit">GUARDAR OPCIONES</button></form>`;
        const close = dialog.querySelector('header button');
        close.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => dialog.remove());
        dialog.querySelector('form').addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.target;
            const button = form.querySelector('[type="submit"]');
            const values = {};
            form.querySelectorAll('[name]').forEach(el => { values[el.name] = el.type === 'checkbox' ? el.checked : el.type === 'number' || el.name === 'rounding_pesos' ? Number(el.value) : el.value; if (el.name.endsWith('_cents')) values[el.name] = Math.round(Number(el.value) * 100); });
            button.disabled = true;
            close.disabled = true;
            const blockCancel = event => event.preventDefault();
            dialog.addEventListener('cancel', blockCancel);
            try {
                const data = await post({action: 'save_defaults', defaults: values});
                defaults = data.defaults;
                form.querySelector('[data-settings-result]').textContent = data.message;
                window.dispatchEvent(new CustomEvent('meli-defaults-changed'));
            } catch (error) { form.querySelector('[data-settings-result]').textContent = error.message; }
            finally { button.disabled = false; close.disabled = false; dialog.removeEventListener('cancel', blockCancel); }
        });
        document.body.append(dialog);
        dialog.showModal();
    }
    const endpoint = new URL('meli.php', window.location.href);
    const connect = document.getElementById('meli-connect');
    const verify = document.getElementById('meli-verify');
    const footer = document.getElementById('meli-progress-footer');
    const progressText = document.getElementById('meli-progress-text');
    const products = document.getElementById('meli-products');
    const previous = document.getElementById('meli-products-previous');
    const next = document.getElementById('meli-products-next');
    let offset = 0;
    const quotes = new Map();
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    const icons = {
        pause: '<path d="M8 5v14M16 5v14"/>',
        play: '<path d="m8 5 11 7-11 7Z"/>',
        price: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h2M14 11h2M8 15h2M14 15h2M8 18h2M14 18h2"/>',
        stock: '<path d="m3 7 9-4 9 4-9 4Z M3 7v10l9 4 9-4V7M12 11v10"/>',
        apply: '<path d="m5 12 4 4L19 6"/>',
        cancel: '<path d="m6 6 12 12M18 6 6 18"/>',
        trash: '<path d="M4 7h16M9 7V4h6v3M6.5 7l1 13h9l1-13M10 11v5M14 11v5"/>',
        external: '<path d="M14 3h7v7M21 3l-11 11M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>'
    };
    const icon = name => `<svg viewBox="0 0 24 24" aria-hidden="true">${icons[name]}</svg>`;
    const actionButton = (action, label, name, attributes = '') => `<button type="button" class="icon-action-button meli-listing-action" data-action="${action}" title="${label}" aria-label="${label}" ${attributes}>${icon(name)}</button>`;
    let busy = false;
    let bulkBusy = false;
    const publishAll = document.getElementById('meli-publish-all');
    function progress(text, active = false) {
        progressText.textContent = `Mercado Libre: ${text}`;
        footer.classList.toggle('is-active', active);
    }
    function indicator(connected, text) {
        document.querySelectorAll('[data-meli-indicator]').forEach(dot => {
            dot.classList.toggle('is-connected', connected);
            dot.setAttribute('aria-label', connected ? 'Conexión exitosa' : 'Sin conexión verificada');
            dot.title = text;
        });
    }
    async function request(options = {}) {
        const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store', ...options });
        const data = await response.json();
        if (!response.ok || data.connected === false || data.ok === false) {
            if (data.connected === false && data.configured === true && !options.method && !endpoint.search) return data;
            throw new Error(data.message || 'No se pudo verificar la conexión.');
        }
        return data;
    }
    async function loadProducts() {
        progress('consultando publicaciones, precio y stock…', true);
        const url = new URL(endpoint);
        url.search = new URLSearchParams({action: 'published_products', offset});
        const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudieron consultar los productos publicados.');
        const states = {active: 'Activo', paused: 'Pausado', closed: 'Finalizado', under_review: 'En revisión', inactive: 'Inactivo', payment_required: 'Pago pendiente'};
        products.innerHTML = data.products.length ? `<p>${Number(data.total)} publicaciones · ${Number(data.offset) + 1}–${Number(data.offset) + data.products.length}</p><div class="meli-products-table-wrap"><table class="meli-products-table"><thead><tr><th>Producto</th><th>Precio Meli</th><th>Stock Meli</th><th>Vendidos</th><th>Estado</th><th>Publicación</th><th>Acciones</th></tr></thead><tbody>${data.products.map(item => {
            let href = '';
            try { const link = new URL(item.permalink); if (['https:', 'http:'].includes(link.protocol) && (link.hostname === 'mercadolibre.com.ar' || link.hostname.endsWith('.mercadolibre.com.ar'))) href = link.href.replace(/^http:/, 'https:'); } catch {}
            const statusAction = ['active', 'paused'].includes(item.status) ? actionButton('listing_status', item.status === 'active' ? 'Pausar' : 'Reactivar', item.status === 'active' ? 'pause' : 'play', `data-status="${item.status === 'active' ? 'paused' : 'active'}"`) : '';
            const quote = quotes.get(item.id);
            const pricing = quote ? `<div class="meli-price-review" role="status"><span>Nuevo precio: ${escape(money(quote.pricing.price_cents))} · Comisiones: ${escape(money(quote.pricing.sale_fee_cents + quote.pricing.listing_fee_cents))} · Neto estimado: ${escape(money(quote.pricing.net_cents))}</span>${actionButton('apply_listing_price', 'Aplicar precio', 'apply')}${actionButton('cancel_quote', 'Cancelar', 'cancel')}</div>` : '';
            return `<tr data-item-id="${escape(item.id)}"><td>${escape(item.title || item.family_name)}<small>${escape(item.id)} · ${escape(({gold_special: 'Clásica', gold_pro: 'Premium', free: 'Gratuita'})[item.listing_type_id] || item.listing_type_id)}</small></td><td>${escape(Number(item.price).toLocaleString('es-AR', {style: 'currency', currency: item.currency_id || 'ARS'}))}</td><td>${Number(item.available_quantity)}</td><td>${Number(item.sold_quantity)}</td><td>${escape(states[item.status] || item.status)}</td><td>${href ? `<a class="icon-action-button" href="${escape(href)}" target="_blank" rel="noopener noreferrer" title="Ver en MeLi" aria-label="Ver en MeLi">${icon('external')}</a>` : '—'}</td><td><div class="meli-listing-actions">${statusAction}${item.linked ? actionButton('preview_listing_price', 'Recalcular precio', 'price') + actionButton('sync_listing_stock', 'Sincronizar stock', 'stock') : '<small>Precio y stock: sin producto vinculado.</small>'}${actionButton('delete_listing', 'Eliminar publicación', 'trash')}${pricing}</div></td></tr>`;
        }).join('')}</tbody></table></div>` : '<p>Todavía no hay productos publicados en esta cuenta.</p>';
        previous.hidden = data.offset <= 0;
        next.hidden = data.offset + data.products.length >= data.total || data.offset >= 980;
        progress('productos actualizados.');
    }
    const money = cents => (Number(cents) / 100).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'});
    products.addEventListener('click', async event => {
        const button = event.target.closest('button[data-action]');
        if (!button || busy || bulkBusy) return;
        const row = button.closest('[data-item-id]');
        const itemId = row.dataset.itemId;
        const action = button.dataset.action;
        if (action === 'delete_listing' && !window.confirm('¿Eliminar esta publicación de Mercado Libre? Si está vinculada a un producto, se eliminarán también las publicaciones de sus otros talles.')) return;
        if (action === 'cancel_quote') { quotes.delete(itemId); row.querySelector('.meli-price-review')?.remove(); return; }
        const labels = {delete_listing: 'eliminando publicación y talles vinculados…', listing_status: button.dataset.status === 'active' ? 'reactivando publicación…' : 'pausando publicación…', preview_listing_price: 'consultando comisiones y recalculando precio…', apply_listing_price: 'aplicando el precio revisado…', sync_listing_stock: 'reconciliando el stock de la tienda y Meli…'};
        busy = true;
        verify.disabled = true;
        products.querySelectorAll('button').forEach(control => { control.disabled = true; });
        progress(labels[action], true);
        try {
            const data = await request({method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({
                csrf_token: app.csrf_token, action, item_id: itemId, status: button.dataset.status,
                quote_token: quotes.get(itemId)?.quote_token
            })});
            if (action === 'preview_listing_price') quotes.set(itemId, data);
            else quotes.delete(itemId);
            if (action === 'delete_listing') window.dispatchEvent(new CustomEvent('meli-publication-changed'));
            await loadProducts();
            progress(data.message || 'Precio calculado. Revisá el importe antes de aplicarlo.');
        } catch (error) {
            if (action === 'delete_listing') {
                window.dispatchEvent(new CustomEvent('meli-publication-changed'));
                try { await loadProducts(); } catch {}
            }
            progress(error.message || 'No se pudo completar la operación.');
        } finally {
            busy = false;
            verify.disabled = false;
            products.querySelectorAll('button').forEach(control => { control.disabled = false; });
        }
    });
    async function check(showProducts = panel.classList.contains('active')) {
        if (busy || bulkBusy) return;
        busy = true;
        verify.disabled = true;
        connect.disabled = true;
        indicator(false, 'Verificando conexión…');
        progress('consultando el acceso a la cuenta…', true);
        try {
            const data = await request();
            indicator(data.connected === true, data.message);
            progress(data.connected ? 'acceso verificado.' : data.message);
            connect.disabled = !data.configured;
            connect.hidden = data.connected === true;
            document.getElementById('meli-authorization-result').textContent = data.authorization_result || '';
            if (data.connected && showProducts) await loadProducts();
        } catch (error) {
            indicator(false, error.message || 'No se pudo verificar la conexión.');
            progress(error.message || 'No se pudo verificar la conexión.');
        } finally {
            busy = false;
            verify.disabled = false;
        }
    }
    connect.addEventListener('click', async () => {
        if (busy || bulkBusy) return;
        busy = true;
        connect.disabled = true;
        verify.disabled = true;
        progress('preparando la autorización segura de la cuenta…', true);
        try {
            const data = await request({ method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: app.csrf_token }) });
            if (!data.authorization_url) throw new Error(data.message || 'No se pudo iniciar la autorización.');
            const url = new URL(data.authorization_url);
            if (url.origin !== 'https://auth.mercadolibre.com.ar') throw new Error('URL de autorización inválida.');
            progress('abriendo la autorización en Mercado Libre…', true);
            window.location.assign(url.href);
        } catch (error) {
            indicator(false, error.message);
            progress(error.message);
            connect.disabled = false;
            verify.disabled = false;
            busy = false;
        }
    });
    verify.addEventListener('click', () => check(true));
    previous.addEventListener('click', () => { if (!busy && !bulkBusy) { offset = Math.max(0, offset - 20); check(true); } });
    next.addEventListener('click', () => { if (!busy && !bulkBusy) { offset += 20; check(true); } });
    const post = payload => request({method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({csrf_token: app.csrf_token, ...payload})});
    async function publish(productId, onProgress = () => {}, fromBulk = false) {
        if (busy || (bulkBusy && !fromBulk)) throw new Error('MeLi está realizando otra operación. Intentá nuevamente en unos segundos.');
        busy = true;
        verify.disabled = true;
        const report = (text, active = true) => { progress(text, active); onProgress(`Mercado Libre: ${text}`, active); };
        try {
            const base = {product_id: productId};
            report('consultando ficha y talles disponibles…');
            const {variants} = await post({...base, action: 'publication_variants'});
            // Validate every size before creating the first listing.
            for (const variant of variants) {
                report(`validando ${variant.name} y calculando comisiones…`);
                const result = await post({...base, variant_id: variant.id, action: 'validate_publication'});
                if (!result.valid) {
                    const errors = (result.validation?.cause || []).filter(cause => cause.type !== 'warning').map(cause => cause.message || cause.code);
                    throw new Error(`${variant.name}: ${errors.join(' · ') || 'MeLi rechazó la ficha.'}`);
                }
            }
            for (const variant of variants) {
                report(`publicando ${variant.name}…`);
                await post({...base, variant_id: variant.id, action: 'publish_product'});
            }
            report('confirmando las publicaciones…');
            const result = await post({...base, action: 'finish_publication'});
            offset = 0;
            report('actualizando la tabla MeLi…');
            try { await loadProducts(); } catch { /* Publication remains confirmed if the list cannot refresh. */ }
            report(result.message, false);
            return result;
        } catch (error) { report(error.message, false); throw error; }
        finally { busy = false; verify.disabled = bulkBusy; }
    }
    function readyForBulk(product) {
        const visible = [true, 1, '1'].includes(product.active);
        const state = product.meli_publication?.state || 'unpublished';
        return visible && state === 'unpublished' && !product.meli_publication?.items?.length
            && window.MeliProductEditor && window.MeliProductEditor.pending(product).length === 0
            && product.variants?.some(variant => [true, 1, '1'].includes(variant.active) && Number(variant.stock_on_hand) > 0);
    }
    publishAll.addEventListener('click', async () => {
        if (busy || bulkBusy) return;
        bulkBusy = true;
        publishAll.disabled = true;
        publishAll.setAttribute('aria-busy', 'true');
        document.getElementById('meli-settings').disabled = true;
        verify.disabled = true;
        let completed = 0;
        let failed = 0;
        try {
            progress('buscando productos completos pendientes de publicación…', true);
            const url = new URL(app.api_url, window.location.href);
            url.searchParams.set('action', 'admin_products');
            const response = await fetch(url, {credentials: 'same-origin', cache: 'no-store'});
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'No se pudieron consultar los productos.');
            const queue = data.products.filter(readyForBulk);
            if (!queue.length) { progress('no hay productos completos con icono gris y stock para publicar.'); return; }
            for (const [index, product] of queue.entries()) {
                publishAll.textContent = `PUBLICANDO ${index + 1}/${queue.length}`;
                try {
                    await publish(product.id, (text, active) => {
                        progress(`${index + 1}/${queue.length} · ${product.name} · ${text.replace(/^Mercado Libre: /, '')}`, active);
                    }, true);
                    completed++;
                } catch { failed++; }
                // Refresh local icons after success or partial failure; never retry a creation.
                window.dispatchEvent(new CustomEvent('meli-publication-changed'));
            }
            progress(`publicación finalizada: ${completed} productos publicados, ${failed} con error.`);
        } catch (error) { progress(error.message); }
        finally {
            bulkBusy = false;
            publishAll.disabled = false;
            publishAll.removeAttribute('aria-busy');
            publishAll.textContent = 'PUBLICAR TODO';
            document.getElementById('meli-settings').disabled = false;
            verify.disabled = false;
        }
    });
    document.getElementById('meli-settings').addEventListener('click', openSettings);
    window.MeliWorkspace = { activate: () => check(true), publish, defaults: () => defaults };
    check();
    window.setInterval(() => {
        if (!document.hidden && panel.classList.contains('active')) check();
    }, 30000);
})();
