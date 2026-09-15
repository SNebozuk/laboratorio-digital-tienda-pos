(() => {
    const app = JSON.parse(document.getElementById('app-data').textContent);
    const chat = document.getElementById('vendor-ai');
    const launcher = document.getElementById('vendor-ai-launcher');
    const messages = document.getElementById('vendor-ai-messages');
    const input = document.getElementById('vendor-ai-input');
    const form = document.getElementById('vendor-ai-form');
    const typing = document.getElementById('vendor-ai-typing');
    const status = document.getElementById('vendor-ai-status');
    const results = document.getElementById('vendor-ai-results');
    const testing = new URLSearchParams(window.location.search).get('chat_ia') === '1';
    if (!testing) { chat.hidden = true; launcher.hidden = true; return; }

    const id = (() => {
        const key = 'ld_vendor_ai_conversation';
        let value = localStorage.getItem(key);
        if (!value || new URLSearchParams(window.location.search).get('nueva') === '1') { value = crypto.randomUUID(); localStorage.setItem(key, value); }
        return value;
    })();
    let pendingProduct = null;
    let checkoutStep = null;
    const checkoutCustomer = {};
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
        if (!rows.length) { results.innerHTML = '<span>Sin resultados para esta búsqueda.</span>'; return; }
        results.innerHTML = `<table><thead><tr><th></th><th>Producto</th><th>Variante</th><th></th></tr></thead><tbody>${rows.map(row => {
            const image = row.imagen ? `<img src="${escape(row.imagen)}" alt="">` : '';
            const price = row.precio === null ? 'Precio a consultar' : `$ ${Number(row.precio).toLocaleString('es-AR')}`;
            const product = escape(JSON.stringify({ id: row.variante_id, name: `${row.producto} ${row.variante || ''}`.trim() }));
            const measures = row.medidas ? `<button type="button" data-vendor-ai-measures="${escape(row.medidas)}" data-vendor-ai-measures-name="${escape(`${row.producto} ${row.variante || ''}`.trim())}">MEDIDAS</button>` : '';
            return `<tr><td>${image}</td><td><strong>${escape(row.producto)}</strong></td><td>${escape(row.variante || 'Única')}<br><small>${escape(price)}</small></td><td><button type="button" data-vendor-ai-product='${product}'>ELEGIR</button>${measures}</td></tr>`;
        }).join('')}</tbody></table>`;
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
        messages.insertAdjacentHTML('beforeend', '<button class="vendor-ai-cart-action" type="button" data-vendor-ai-continue>SEGUIR BUSCANDO</button><button class="vendor-ai-cart-action" type="button" data-vendor-ai-finish>FINALIZAR COMPRA</button>');
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
    chat.addEventListener('click', event => {
        const measures = event.target.closest('[data-vendor-ai-measures]');
        if (measures) {
            appendMessage('assistant', `${measures.dataset.vendorAiMeasuresName}: ${measures.dataset.vendorAiMeasures}`);
            return;
        }
        const product = event.target.closest('[data-vendor-ai-product]');
        if (product) {
            pendingProduct = JSON.parse(product.dataset.vendorAiProduct);
            appendMessage('assistant', `¿Cuántas unidades de ${pendingProduct.name} querés agregar al carrito?`);
            input.focus();
            return;
        }
        if (event.target.closest('[data-vendor-ai-cart]')) window.dispatchEvent(new Event('laboratorio:ai-open-cart'));
        if (event.target.closest('[data-vendor-ai-continue]')) { appendMessage('assistant', 'Perfecto, ¿qué más necesitás buscar?'); input.focus(); }
        if (event.target.closest('[data-vendor-ai-finish]')) { checkoutStep = 'name'; appendMessage('assistant', 'Para finalizar, decime tu nombre y apellido.'); input.focus(); }
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        appendMessage('user', text);
        renderResults([]);
        if (checkoutStep === 'name') {
            if (text.trim().split(/\s+/).length < 2) { appendMessage('assistant', 'Necesito nombre y apellido completos para continuar.'); return; }
            checkoutCustomer.name = text;
            checkoutStep = 'phone';
            appendMessage('assistant', '¿Cuál es tu WhatsApp?');
            return;
        }
        if (checkoutStep === 'phone') {
            const phone = text.replace(/\D+/g, '');
            if (phone.length < 8) { appendMessage('assistant', 'Pasame un WhatsApp válido para continuar.'); return; }
            window.dispatchEvent(new CustomEvent('laboratorio:ai-customer', { detail: { name: checkoutCustomer.name, phone } }));
            appendMessage('assistant', 'Perfecto. Abrí la confirmación del pedido: al confirmarlo vas a ver los datos para realizar la transferencia. La reserva queda asegurada cuando se acredita.');
            window.dispatchEvent(new Event('laboratorio:ai-checkout'));
            checkoutStep = null;
            return;
        }
        if (pendingProduct && /^\d+$/.test(text) && Number(text) > 0) { addPendingProduct(Number(text)); return; }
        typing.hidden = false;
        try {
            const response = await fetch(app.api_url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'ai_public_chat', conversation: id, message: text }) });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            appendMessage('assistant', data.reply?.message || 'No pude responder.');
            renderResults(data.reply?.display_results || []);
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
