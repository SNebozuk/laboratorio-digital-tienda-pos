(() => {
    'use strict';

    const config = window.aiSearchPreview || {};
    const input = document.getElementById('ai-search-input');
    const submit = document.getElementById('ai-search-submit');
    const messages = document.getElementById('ai-search-messages');
    const products = document.getElementById('ai-search-products');
    const interpretation = document.getElementById('ai-search-interpretation');
    let catalog = [];

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    const fold = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const words = value => fold(value).match(/[a-z0-9]+(?:[.,][0-9]+)*/g) || [];
    const money = cents => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(Number(cents || 0) / 100);

    function productMatches(product, tokens) {
        const searchable = fold([
            product.name,
            product.description,
            product.category?.name,
            ...(product.variants || []).map(variant => variant.name),
        ].join(' '));
        return tokens.every(token => searchable.includes(token));
    }

    function variantTokens(query) {
        const tokens = words(query);
        const markers = new Set(['talle', 'talles', 'color', 'colores', 'variante', 'variantes', 'atributo', 'atributos']);
        return tokens.filter((token, index) => markers.has(tokens[index - 1] || '') && !markers.has(token));
    }

    function render() {
        const query = String(input?.value || '').trim();
        const tokens = words(query).filter(token => !['talle', 'talles', 'color', 'colores', 'de', 'del', 'para', 'con', 'en', 'la', 'el', 'los', 'las', 'un', 'una'].includes(token));
        const filters = variantTokens(query);
        if (!query) {
            messages.innerHTML = '<div class="ai-search-message ai-search-message-assistant"><small>ASISTENTE</small><p>Escribí una búsqueda para consultar el catálogo real.</p></div>';
            products.innerHTML = '';
            interpretation.innerHTML = '<div><dt>Búsqueda</dt><dd>Sin consulta</dd></div><div><dt>Resultados</dt><dd>—</dd></div>';
            return;
        }
        const cards = tokens.length ? catalog.filter(product => productMatches(product, tokens)).flatMap(product => (product.variants || [])
            .filter(variant => filters.every(token => words(variant.name).some(word => word === token || word.startsWith(token))))
            .map(variant => ({ product, variant }))
        ).sort((left, right) => Number(right.variant.available_stock || 0) - Number(left.variant.available_stock || 0)).slice(0, 12) : [];
        messages.innerHTML = `<div class="ai-search-message ai-search-message-client"><small>CLIENTE</small><p>${escapeHtml(query)}</p></div><div class="ai-search-message ai-search-message-assistant"><small>ASISTENTE</small><p>${cards.length ? `Encontré ${cards.length} coincidencia${cards.length === 1 ? '' : 's'} en el catálogo.` : 'No encontré coincidencias en el catálogo.'}</p></div>`;
        products.innerHTML = cards.map(({ product, variant }) => {
            const image = String(product.image_path || '').startsWith('/') ? product.image_path : '';
            const price = variant.price_cents === null ? 'Precio a consultar' : money(variant.price_cents);
            const stock = variant.available_stock === null ? 'Stock a consultar' : Number(variant.available_stock) > 0 ? `Stock: ${Number(variant.available_stock)}` : 'Sin stock';
            const url = new URL(config.storeUrl || '/', window.location.href);
            url.searchParams.set('producto', String(product.id));
            return `<article class="ai-search-product-card">${image ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(product.name)}">` : '<div class="product-admin-placeholder">SIN FOTO</div>'}<div><strong>${escapeHtml(product.name)}</strong><small>${escapeHtml(product.category?.name || 'Sin categoría')}</small><span>Variante: ${escapeHtml(variant.name || 'Única')}</span><b>${escapeHtml(price)}</b><em>${escapeHtml(stock)}</em></div><a class="secondary-button" href="${escapeHtml(url.href)}">VER PRODUCTO</a></article>`;
        }).join('');
        interpretation.innerHTML = `<div><dt>Búsqueda</dt><dd>${escapeHtml(query)}</dd></div><div><dt>Resultados</dt><dd>${cards.length}</dd></div><div><dt>Palabras</dt><dd>${escapeHtml(tokens.join(', ') || '—')}</dd></div>`;
    }

    fetch(`${config.apiUrl}?action=catalog`, { headers: { Accept: 'application/json' }, cache: 'no-store' })
        .then(response => response.json())
        .then(data => { catalog = Array.isArray(data.products) ? data.products : []; render(); })
        .catch(() => { messages.innerHTML = '<div class="ai-search-message ai-search-message-assistant"><small>ASISTENTE</small><p>No pudimos consultar el catálogo.</p></div>'; });
    input?.addEventListener('input', render);
    submit?.addEventListener('click', render);
    input?.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); render(); }
    });
})();
