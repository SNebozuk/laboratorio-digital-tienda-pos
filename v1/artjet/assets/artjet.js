(() => {
    'use strict';

    document.querySelectorAll('.artjet-card-image img').forEach(image => {
        const showFallback = () => {
            image.hidden = true;
            const fallback = image.parentElement?.querySelector('.artjet-image-fallback');
            if (fallback) fallback.hidden = false;
        };

        image.addEventListener('error', showFallback, { once: true });
        if (image.complete && image.naturalWidth === 0) showFallback();
    });

    const modal = document.getElementById('artjet-modal');
    const modalImage = modal?.querySelector('.artjet-modal-image');
    const modalDetails = modal?.querySelector('.artjet-modal-details');
    let previousFocus = null;
    const closeModal = () => {
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        document.body.style.overflow = '';
        modalImage.replaceChildren();
        modalDetails.replaceChildren();
        previousFocus?.focus({ preventScroll: true });
    };
    document.querySelectorAll('[data-artjet-open]').forEach(button => {
        button.addEventListener('click', () => {
            previousFocus = button;
            const card = button.closest('.artjet-card');
            const image = button.querySelector('img');
            modalImage.className = 'artjet-modal-image ' + [...button.querySelector('.artjet-card-image').classList].filter(name => name.startsWith('is-')).join(' ');
            if (image && !image.hidden) {
                const enlarged = image.cloneNode();
                enlarged.alt = button.getAttribute('aria-label').replace(/^Ver información de /, '');
                modalImage.append(enlarged);
            } else {
                modalImage.textContent = 'SIN IMAGEN';
            }
            modalDetails.innerHTML = card.querySelector('.artjet-card-details').innerHTML;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            modal.querySelector('.artjet-modal-close').focus();
        });
    });
    modal?.querySelector('.artjet-modal-close').addEventListener('click', closeModal);
    modal?.addEventListener('click', event => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeModal();
        if (event.key === 'Tab' && modal && !modal.hidden) {
            const focusable = [...modal.querySelectorAll('button, a[href]')];
            const target = event.shiftKey ? focusable[focusable.length - 1] : focusable[0];
            if (document.activeElement === (event.shiftKey ? focusable[0] : focusable[focusable.length - 1])) {
                event.preventDefault(); target.focus();
            }
        }
    });
    document.querySelectorAll('.artjet-collection').forEach(collection => {
        const track = collection.querySelector('.artjet-product-grid');
        const prev = collection.querySelector('[data-artjet-prev]');
        const next = collection.querySelector('[data-artjet-next]');
        const update = () => {
            prev.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
        };
        const move = direction => track.scrollBy({ left: direction * Math.max(track.clientWidth * .8, 250), behavior: 'smooth' });
        prev.addEventListener('click', () => move(-1));
        next.addEventListener('click', () => move(1));
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    const chat = document.getElementById('artjet-chat');
    const launcher = document.getElementById('artjet-chat-launcher');
    const close = document.getElementById('artjet-chat-close');
    const form = document.getElementById('artjet-chat-form');
    const input = document.getElementById('artjet-chat-input');
    const messages = document.getElementById('artjet-chat-messages');
    const typing = document.getElementById('artjet-chat-typing');
    const history = [];
    const addMessage = (role, content) => {
        const message = document.createElement('p');
        message.className = role === 'user' ? 'is-user' : '';
        message.textContent = content;
        messages.appendChild(message);
        messages.scrollTop = messages.scrollHeight;
    };
    launcher?.addEventListener('click', () => { chat.hidden = false; launcher.hidden = true; input.focus(); });
    close?.addEventListener('click', () => { chat.hidden = true; launcher.hidden = false; });
    input?.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); form.requestSubmit(); }
    });
    form?.addEventListener('submit', async event => {
        event.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        input.value = '';
        addMessage('user', text);
        typing.hidden = false;
        input.disabled = true;
        try {
            const response = await fetch('../api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'artjet_ai_chat', history, message: text }) });
            const data = await response.json();
            if (!data.ok) throw new Error(data.error || 'No pude responder en este momento.');
            history.push({ role: 'user', content: text }, { role: 'assistant', content: data.reply });
            if (history.length > 12) history.splice(0, history.length - 12);
            addMessage('assistant', data.reply);
        } catch (error) { addMessage('assistant', error.message || 'No pude responder en este momento.'); }
        finally { typing.hidden = true; input.disabled = false; input.focus(); }
    });
})();
