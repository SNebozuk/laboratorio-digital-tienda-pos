(() => {
    'use strict';
    const panel = document.getElementById('view-meli');
    if (!panel) return;
    const app = JSON.parse(document.getElementById('admin-app-data').textContent);
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
        external: '<path d="M14 3h7v7M21 3l-11 11M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5"/>'
    };
    const icon = name => `<svg viewBox="0 0 24 24" aria-hidden="true">${icons[name]}</svg>`;
    const actionButton = (action, label, name, attributes = '') => `<button type="button" class="icon-action-button meli-listing-action" data-action="${action}" title="${label}" aria-label="${label}" ${attributes}>${icon(name)}</button>`;
    let busy = false;
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
            return `<tr data-item-id="${escape(item.id)}"><td>${escape(item.title || item.family_name)}<small>${escape(item.id)} · ${escape(({gold_special: 'Clásica', gold_pro: 'Premium', free: 'Gratuita'})[item.listing_type_id] || item.listing_type_id)}</small></td><td>${escape(Number(item.price).toLocaleString('es-AR', {style: 'currency', currency: item.currency_id || 'ARS'}))}</td><td>${Number(item.available_quantity)}</td><td>${Number(item.sold_quantity)}</td><td>${escape(states[item.status] || item.status)}</td><td>${href ? `<a class="icon-action-button" href="${escape(href)}" target="_blank" rel="noopener noreferrer" title="Ver en MeLi" aria-label="Ver en MeLi">${icon('external')}</a>` : '—'}</td><td><div class="meli-listing-actions">${statusAction}${item.linked ? actionButton('preview_listing_price', 'Recalcular precio', 'price') + actionButton('sync_listing_stock', 'Sincronizar stock', 'stock') : '<small>Precio y stock: sin producto vinculado.</small>'}${pricing}</div></td></tr>`;
        }).join('')}</tbody></table></div>` : '<p>Todavía no hay productos publicados en esta cuenta.</p>';
        previous.hidden = data.offset <= 0;
        next.hidden = data.offset + data.products.length >= data.total || data.offset >= 980;
        progress('productos actualizados.');
    }
    const money = cents => (Number(cents) / 100).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'});
    products.addEventListener('click', async event => {
        const button = event.target.closest('button[data-action]');
        if (!button || busy) return;
        const row = button.closest('[data-item-id]');
        const itemId = row.dataset.itemId;
        const action = button.dataset.action;
        if (action === 'cancel_quote') { quotes.delete(itemId); row.querySelector('.meli-price-review')?.remove(); return; }
        const labels = {listing_status: button.dataset.status === 'active' ? 'reactivando publicación…' : 'pausando publicación…', preview_listing_price: 'consultando comisiones y recalculando precio…', apply_listing_price: 'aplicando el precio revisado…', sync_listing_stock: 'reconciliando el stock de la tienda y Meli…'};
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
            await loadProducts();
            progress(data.message || 'Precio calculado. Revisá el importe antes de aplicarlo.');
        } catch (error) {
            progress(error.message || 'No se pudo completar la operación.');
        } finally {
            busy = false;
            verify.disabled = false;
            products.querySelectorAll('button').forEach(control => { control.disabled = false; });
        }
    });
    async function check(showProducts = panel.classList.contains('active')) {
        if (busy) return;
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
        if (busy) return;
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
    previous.addEventListener('click', () => { if (!busy) { offset = Math.max(0, offset - 20); check(true); } });
    next.addEventListener('click', () => { if (!busy) { offset += 20; check(true); } });
    window.MeliWorkspace = { activate: () => check(true) };
    check();
    window.setInterval(() => {
        if (!document.hidden && panel.classList.contains('active')) check();
    }, 30000);
})();
