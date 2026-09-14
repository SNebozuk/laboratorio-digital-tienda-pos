(() => {
    const app = JSON.parse(document.getElementById('app-data').textContent);
    const chat = document.getElementById('vendor-ai');
    const launcher = document.getElementById('vendor-ai-launcher');
    const messages = document.getElementById('vendor-ai-messages');
    const input = document.getElementById('vendor-ai-input');
    const form = document.getElementById('vendor-ai-form');
    const typing = document.getElementById('vendor-ai-typing');
    const status = document.getElementById('vendor-ai-status');
    const testing = new URLSearchParams(window.location.search).get('chat_ia') === '1';
    if (!testing) { chat.hidden = true; launcher.hidden = true; return; }

    const id = (() => {
        const key = 'ld_vendor_ai_conversation';
        let value = localStorage.getItem(key);
        if (!value || new URLSearchParams(window.location.search).get('nueva') === '1') { value = crypto.randomUUID(); localStorage.setItem(key, value); }
        return value;
    })();
    let pendingProduct = null;
    const escape = value => String(value || '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    const appendMessage = (role, content) => {
        messages.insertAdjacentHTML('beforeend', `<div class="vendor-ai-message ${role === 'user' ? 'is-user' : ''}">${escape(content)}</div>`);
        messages.scrollTop = messages.scrollHeight;
    };
    const render = history => {
        messages.innerHTML = history.length ? history.map(item => `<div class="vendor-ai-message ${item.role === 'user' ? 'is-user' : ''}">${escape(item.content)}</div>`).join('') : '<div class="vendor-ai-message">¡Hola! Soy el Vendedor IA. ¿En qué te ayudo?</div>';
        messages.scrollTop = messages.scrollHeight;
    };
    const renderResults = rows => {
        rows.forEach(row => {
            const image = row.imagen ? `<img src="${escape(row.imagen)}" alt="">` : '';
            const price = row.precio === null ? 'Precio a consultar' : `$ ${Number(row.precio).toLocaleString('es-AR')}`;
            const product = escape(JSON.stringify({ id: row.variante_id, name: `${row.producto} ${row.variante || ''}`.trim() }));
            messages.insertAdjacentHTML('beforeend', `<article class="vendor-ai-result">${image}<div><strong>${escape(row.producto)}</strong><small>${escape(row.variante || 'Única')} · ${escape(price)}</small><button type="button" data-vendor-ai-product='${product}'>ELEGIR</button></div></article>`);
        });
        messages.scrollTop = messages.scrollHeight;
    };
    const refresh = async () => {
        try {
            const data = await fetch(`${app.api_url}?action=ai_public_history&conversation=${encodeURIComponent(id)}`).then(response => response.json());
            render(data.history || []);
            status.textContent = data.status?.connected ? '● IA conectada' : '● IA desconectada';
            status.classList.toggle('offline', !data.status?.connected);
        } catch (_) { status.textContent = '● IA desconectada'; }
    };
    const addPendingProduct = quantity => {
        window.dispatchEvent(new CustomEvent('laboratorio:ai-add-to-cart', { detail: { variantId: pendingProduct.id, quantity } }));
        appendMessage('assistant', `Listo, agregué ${quantity} unidad${quantity === 1 ? '' : 'es'} de ${pendingProduct.name} al carrito. ¿Querés seguir buscando o finalizar la compra?`);
        messages.insertAdjacentHTML('beforeend', '<button class="vendor-ai-cart-action" type="button" data-vendor-ai-cart>VER CARRITO</button>');
        messages.scrollTop = messages.scrollHeight;
        pendingProduct = null;
    };

    launcher.hidden = true;
    chat.dataset.state = 'open';
    window.requestAnimationFrame(() => input.focus());
    launcher.addEventListener('click', () => { chat.dataset.state = 'open'; launcher.hidden = true; input.focus(); });
    document.querySelector('[data-vendor-ai-minimize]').addEventListener('click', () => { chat.dataset.state = 'minimized'; launcher.hidden = false; });
    document.querySelector('[data-vendor-ai-close]').addEventListener('click', () => { chat.dataset.state = 'closed'; launcher.hidden = false; });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); }
    });
    messages.addEventListener('wheel', event => {
        if (messages.scrollHeight <= messages.clientHeight) return;
        event.preventDefault();
        messages.scrollTop += event.deltaY;
    }, { passive: false });
    messages.addEventListener('click', event => {
        const product = event.target.closest('[data-vendor-ai-product]');
        if (product) {
            pendingProduct = JSON.parse(product.dataset.vendorAiProduct);
            appendMessage('assistant', `¿Cuántas unidades de ${pendingProduct.name} querés agregar al carrito?`);
            input.focus();
            return;
        }
        if (event.target.closest('[data-vendor-ai-cart]')) window.dispatchEvent(new Event('laboratorio:ai-open-cart'));
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        appendMessage('user', text);
        if (pendingProduct && /^\d+$/.test(text) && Number(text) > 0) { addPendingProduct(Number(text)); return; }
        typing.hidden = false;
        try {
            const response = await fetch(app.api_url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'ai_public_chat', conversation: id, message: text }) });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            await refresh();
            const rows = data.reply?.display_results || [];
            if (rows.length) {
                renderResults(rows);
                window.dispatchEvent(new CustomEvent('laboratorio:ai-search', { detail: { query: text } }));
            }
            if (data.reply?.human_help) {
                messages.insertAdjacentHTML('beforeend', `<a class="vendor-ai-human" target="_blank" rel="noopener" href="https://wa.me/5493415699338?text=${encodeURIComponent(data.reply.human_help.message)}">${escape(data.reply.human_help.message)} Consultar a Allessandra</a>`);
                messages.scrollTop = messages.scrollHeight;
            }
        } catch (error) { appendMessage('assistant', error.message || 'No pude responder.'); }
        finally { typing.hidden = true; }
    });
    let start;
    const drag = document.querySelector('[data-vendor-ai-drag]');
    drag.addEventListener('pointerdown', event => {
        if (event.target.closest('button')) return;
        start = { x: event.clientX, y: event.clientY, left: chat.offsetLeft, top: chat.offsetTop };
        event.currentTarget.setPointerCapture(event.pointerId);
    });
    drag.addEventListener('pointermove', event => {
        if (!start) return;
        chat.style.right = 'auto'; chat.style.bottom = 'auto';
        chat.style.left = `${Math.max(0, start.left + event.clientX - start.x)}px`;
        chat.style.top = `${Math.max(0, start.top + event.clientY - start.y)}px`;
    });
    drag.addEventListener('pointerup', () => { start = null; });
    refresh();
})();
