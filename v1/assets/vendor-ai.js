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

    const id = (() => {
        const key = 'ld_vendor_ai_conversation';
        let value = localStorage.getItem(key);
        if (!value || new URLSearchParams(window.location.search).get('nueva') === '1') { value = crypto.randomUUID(); localStorage.setItem(key, value); }
        return value;
    })();
    let pendingProduct = null;
    let singleResultProduct = null;
    let resultRows = [];
    const resultQuantities = new Map();
    let checkoutStep = null;
    const checkoutCustomer = {};
    const escape = value => String(value || '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
    const fold = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    const appendMessage = (role, content) => {
        messages.insertAdjacentHTML('beforeend', `<div class="vendor-ai-message ${role === 'user' ? 'is-user' : ''}">${escape(content)}</div>`);
        messages.scrollTop = messages.scrollHeight;
    };
    const render = history => {
        messages.innerHTML = history.length ? history.map(item => `<div class="vendor-ai-message ${item.role === 'user' ? 'is-user' : ''}">${escape(item.content)}</div>`).join('') : '<div class="vendor-ai-message">¡Hola! Soy el Asesor IA. ¿En qué te ayudo?</div>';
        messages.scrollTop = messages.scrollHeight;
    };
    const renderResults = rows => {
        resultRows = rows;
        singleResultProduct = rows.length === 1
            ? { id: rows[0].variante_id, name: `${rows[0].producto} ${rows[0].variante || ''}`.trim() }
            : null;
        if (!rows.length) { results.innerHTML = '<span>Sin resultados para esta búsqueda.</span>'; return; }
        results.innerHTML = `<table><thead><tr><th></th><th>Producto</th><th>Variante</th><th></th></tr></thead><tbody>${rows.map(row => {
            const image = row.imagen ? `<img src="${escape(row.imagen)}" alt="">` : '';
            const price = row.precio === null ? 'Precio a consultar' : `$ ${Number(row.precio).toLocaleString('es-AR')}`;
            const variantId = Number(row.variante_id);
            const quantity = Number(resultQuantities.get(variantId) || 0);
            const stock = Number(row.stock || 0);
            const measures = row.medidas ? `<button type="button" data-vendor-ai-measures="${escape(row.medidas)}" data-vendor-ai-measures-name="${escape(`${row.producto} ${row.variante || ''}`.trim())}">MEDIDAS</button>` : '';
            return `<tr><td>${image}</td><td><strong>${escape(row.producto)}</strong></td><td>${escape(row.variante || 'Única')}<br><small>${escape(price)}</small></td><td><div class="vendor-ai-quantity"><button type="button" data-vendor-ai-quantity="-1" data-variant-id="${variantId}" aria-label="Quitar una unidad" ${quantity < 1 ? 'disabled' : ''}>−</button><span data-vendor-ai-count="${variantId}">${quantity}</span><button type="button" data-vendor-ai-quantity="1" data-variant-id="${variantId}" aria-label="Agregar una unidad" ${stock < 1 || quantity >= stock ? 'disabled' : ''}>+</button></div>${measures}</td></tr>`;
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
        const product = pendingProduct;
        window.dispatchEvent(new CustomEvent('laboratorio:ai-add-to-cart', {
            detail: {
                variantId: product.id,
                quantity,
                onResult: result => {
                    if (!result?.added) {
                        const available = Number(result?.available || 0);
                        appendMessage('assistant', available > 0
                            ? `No alcanza el stock para ${quantity} unidades de ${product.name}. Hay ${available} disponible${available === 1 ? '' : 's'}. ¿Querés agregar esa cantidad?`
                            : `No hay stock disponible de ${product.name} en este momento.`);
                        return;
                    }
                    const customer = app.checkout_customer?.customer || {};
                    const details = customer.name && customer.phone ? ` Al finalizar usaremos los datos de ${customer.name} y su WhatsApp ${customer.phone}. Podés cambiarlos en el checkout.` : '';
                    appendMessage('assistant', `Listo, agregué ${quantity} unidad${quantity === 1 ? '' : 'es'} de ${product.name} al carrito. ¿Querés seguir buscando o finalizar la compra?${details}`);
                    messages.insertAdjacentHTML('beforeend', '<button class="vendor-ai-cart-action" type="button" data-vendor-ai-continue>SEGUIR BUSCANDO</button><button class="vendor-ai-cart-action" type="button" data-vendor-ai-finish>FINALIZAR COMPRA</button>');
                    messages.scrollTop = messages.scrollHeight;
                    pendingProduct = null;
                },
            },
        }));
    };
    const cartCommand = (action, detail = {}) => new Promise(resolve => {
        window.dispatchEvent(new CustomEvent('laboratorio:ai-cart-command', { detail: { action, ...detail, onResult: resolve } }));
    });
    const addProductUnit = variantId => new Promise(resolve => {
        window.dispatchEvent(new CustomEvent('laboratorio:ai-add-to-cart', { detail: { variantId, quantity: 1, onResult: resolve } }));
    });
    const renderSyncedResults = async rows => {
        const summary = await cartCommand('summary');
        resultQuantities.clear();
        if (summary?.ok) summary.items.forEach(item => resultQuantities.set(Number(item.variantId), Number(item.quantity)));
        renderResults(rows);
    };
    const cartDescription = items => items.length
        ? items.map(item => `${item.quantity} × ${item.name}`).join(', ')
        : '';
    const cartTarget = text => text
        .replace(/\b(?:sac[áa]|quit[áa]|elimin[áa]|borr[áa]|remov[ée]|sum[áa]|agreg[áa]|aument[áa]|rest[áa]|descont[áa]|baj[áa]|cambi[áa]|pon[ée]|dej[áa])\w*\b/giu, ' ')
        .replace(/\b\d+\b/g, ' ')
        .replace(/\b(?:del?|al?|en|el|la|los|las|mi|carrito|pedido|cantidad|unidades?|productos?|m[áa]s|menos)\b/giu, ' ')
        .replace(/\s+/g, ' ')
        .trim();
    const reportCartResult = (result, success) => {
        if (result?.ok) {
            appendMessage('assistant', success(result));
            return;
        }
        if (result?.reason === 'empty') appendMessage('assistant', 'El carrito está vacío. Primero elegí un producto del catálogo.');
        else if (result?.reason === 'stock') appendMessage('assistant', `No puedo dejar esa cantidad de ${result.item.name}: hay ${result.available} disponible${result.available === 1 ? '' : 's'}.`);
        else if (result?.reason === 'ambiguous') appendMessage('assistant', `Encontré más de una coincidencia en el carrito: ${cartDescription(result.matches)}. Decime cuál querés modificar.`);
        else if (result?.reason === 'maintenance') appendMessage('assistant', 'El carrito está pausado por mantenimiento y no puedo modificarlo ahora.');
        else if (result?.reason === 'invalid_quantity') appendMessage('assistant', 'Indicame una cantidad válida, igual o mayor que cero.');
        else appendMessage('assistant', 'No encontré ese producto en el carrito. Podés preguntarme qué contiene para identificarlo.');
    };
    const handleCartMessage = async text => {
        const normalized = fold(text);
        const quantityMatch = normalized.match(/\b(\d+)\b/);
        const quantity = quantityMatch ? Number(quantityMatch[1]) : null;
        if (/\b(vaci\w*|limpi\w*)\b[\s\S]*\b(carrito|pedido)\b|\b(elimin\w*|borr\w*|quit\w*)\b[\s\S]*\b(todo|todos)\b/iu.test(normalized)) {
            const result = await cartCommand('clear');
            reportCartResult(result, () => 'Listo, vacié el carrito.');
            return true;
        }
        if (/\b(finaliz\w*|termin\w*|complet\w*|confirm\w*)\b[\s\S]*\b(compra|carrito|pedido)\b|\bcheckout\b|^\s*(finaliz\w*|termin\w*)\s*[.!]?\s*$/iu.test(normalized)) {
            const result = await cartCommand('checkout');
            reportCartResult(result, () => 'Abrí la confirmación del pedido para que completes o revises tus datos.');
            return true;
        }
        if (/\b(que|qué)\b[\s\S]*\b(hay|tengo|tenemos|contiene)\b[\s\S]*\b(carrito|pedido)\b|\b(resumen|contenido)\b[\s\S]*\b(carrito|pedido)\b/iu.test(normalized)) {
            const result = await cartCommand('summary');
            reportCartResult(result, value => value.items.length ? `En el carrito tenés ${cartDescription(value.items)}.` : 'El carrito está vacío.');
            return true;
        }
        if (/\b(abr\w*|mostr\w*|ver)\b[\s\S]*\b(carrito|pedido)\b/iu.test(normalized)) {
            const result = await cartCommand('open');
            reportCartResult(result, value => value.items.length ? `Te muestro el carrito: ${cartDescription(value.items)}.` : 'Te muestro el carrito; por ahora está vacío.');
            return true;
        }
        const target = cartTarget(text);
        if (/\b(sac\w*|quit\w*|elimin\w*|borr\w*|remov\w*)\b/iu.test(normalized) && !/\b(todo|todos)\b/iu.test(normalized)) {
            const result = await cartCommand('remove', { query: target });
            reportCartResult(result, value => `Listo, quité ${value.item.name} del carrito.`);
            return true;
        }
        if (quantity !== null && /\b(cambi\w*|pon\w*|dej\w*|fij\w*)\b/iu.test(normalized)) {
            const result = await cartCommand('set', { query: target, quantity });
            reportCartResult(result, value => quantity === 0 ? `Listo, quité ${value.item.name} del carrito.` : `Listo, dejé ${quantity} unidad${quantity === 1 ? '' : 'es'} de ${value.item.name}.`);
            return true;
        }
        if (/\b(rest\w*|descont\w*|baj\w*)\b/iu.test(normalized)) {
            const change = -(quantity || 1);
            const result = await cartCommand('change', { query: target, quantity: change });
            reportCartResult(result, value => value.item.quantity > 0 ? `Listo, quedaron ${value.item.quantity} unidad${value.item.quantity === 1 ? '' : 'es'} de ${value.item.name}.` : `Listo, quité ${value.item.name} del carrito.`);
            return true;
        }
        if (/\b(sum\w*|aument\w*)\b|\bagreg\w*\b[\s\S]*\bm[áa]s\b/iu.test(normalized)) {
            const change = quantity || 1;
            const result = await cartCommand('change', { query: target, quantity: change });
            reportCartResult(result, value => `Listo, ahora hay ${value.item.quantity} unidad${value.item.quantity === 1 ? '' : 'es'} de ${value.item.name}.`);
            return true;
        }
        return false;
    };

    launcher.hidden = testing;
    chat.dataset.state = testing ? 'open' : 'minimized';
    if (testing) window.requestAnimationFrame(() => input.focus());
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
        const quantityButton = event.target.closest('[data-vendor-ai-quantity]');
        if (quantityButton) {
            const variantId = Number(quantityButton.dataset.variantId);
            const change = Number(quantityButton.dataset.vendorAiQuantity);
            const row = resultRows.find(item => Number(item.variante_id) === variantId);
            if (!row) return;
            quantityButton.disabled = true;
            if (change > 0) {
                addProductUnit(variantId).then(result => {
                    if (result?.added) resultQuantities.set(variantId, Number(resultQuantities.get(variantId) || 0) + 1);
                    renderResults(resultRows);
                });
            } else {
                cartCommand('change', { variantId, quantity: -1 }).then(result => {
                    if (result?.ok) resultQuantities.set(variantId, Number(result.item.quantity));
                    renderResults(resultRows);
                });
            }
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
        if (!pendingProduct && singleResultProduct && /\b(agreg[\p{L}]*|sumar|poner)\b[\s\S]*\bcarrito\b/iu.test(text)) {
            pendingProduct = singleResultProduct;
            const quantity = text.match(/\b(\d+)\b/);
            if (quantity && Number(quantity[1]) > 0) addPendingProduct(Number(quantity[1]));
            else {
                appendMessage('assistant', `¿Cuántas unidades de ${pendingProduct.name} querés agregar al carrito?`);
                input.focus();
            }
            return;
        }
        if (await handleCartMessage(text)) return;
        renderResults([]);
        typing.hidden = false;
        try {
            const response = await fetch(app.api_url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'ai_public_chat', conversation: id, message: text }) });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error);
            appendMessage('assistant', data.reply?.message || 'No pude responder.');
            await renderSyncedResults(data.reply?.display_results || []);
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
