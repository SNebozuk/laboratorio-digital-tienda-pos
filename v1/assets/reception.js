(() => {
    const form = document.getElementById('reception-form');
    const messages = document.getElementById('reception-messages');
    const input = document.getElementById('reception-input');
    if (!form || !messages || !input) return;

    const addMessage = (text, fromCustomer = false) => {
        const bubble = document.createElement('div');
        bubble.className = `vendor-ai-message${fromCustomer ? ' is-user' : ''}`;
        bubble.textContent = text;
        messages.append(bubble);
        messages.scrollTop = messages.scrollHeight;
    };

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const message = input.value.trim();
        if (!message) return;
        const payload = new FormData(form);
        addMessage(message, true);
        input.value = '';
        input.disabled = true;
        form.querySelector('button').disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: payload,
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.error || 'No pude responder. Intentá de nuevo.');
            addMessage(data.reply);
            if (data.entered) {
                window.setTimeout(() => window.location.reload(), 900);
                return;
            }
        } catch (error) {
            addMessage(error.message || 'No pude responder. Intentá de nuevo.');
        }
        input.disabled = false;
        form.querySelector('button').disabled = false;
        input.focus();
    });
})();
