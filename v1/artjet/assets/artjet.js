(() => {
    'use strict';

    document.addEventListener('click', event => {
        const back = event.target.closest('.artjet-card-back');
        const trigger = event.target.closest('[data-artjet-flip]')
            || (back && !event.target.closest('a, button') ? back : null);
        if (!trigger) return;

        const card = trigger.closest('[data-artjet-card]');
        if (!card) return;

        const flipped = card.classList.toggle('is-flipped');
        const front = card.querySelector('.artjet-card-front');
        const reverse = card.querySelector('.artjet-card-back');
        if (front) front.setAttribute('aria-expanded', String(flipped));
        if (front) front.inert = flipped;
        if (reverse) reverse.inert = !flipped;

        if (flipped) {
            card.querySelector('.artjet-card-close')?.focus({ preventScroll: true });
        } else {
            front?.focus({ preventScroll: true });
        }
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
