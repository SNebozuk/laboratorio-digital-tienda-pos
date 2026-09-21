(() => {
    const form = document.getElementById('reception-form');
    const messages = document.getElementById('reception-messages');
    const input = document.getElementById('reception-input');
    const entryForm = document.getElementById('customer-entry-form');
    if (!form || !messages || !input) return;

    const fullNameInput = document.getElementById('customer-full-name');
    const phoneInput = document.getElementById('customer-phone');
    const nameError = document.getElementById('customer-name-error');
    const phoneError = document.getElementById('customer-phone-error');
    const blockedWords = new Set(['test', 'testing', 'asdf', 'qwerty', 'xxx', 'xxxx', 'nombre', 'apellido', 'usuario', 'empresa', 'compañía', 'compania', 'sociedad', 'comercio', 'negocio', 'tienda', 'srl', 'sas', 'sa', 'ltd', 'llc', 'inc']);

    const validateName = value => {
        const name = value.trim().replace(/\s+/gu, ' ');
        const words = name.split(' ');
        if (!/^\p{L}+(?:['’\-]\p{L}+)*(?: +\p{L}+(?:['’\-]\p{L}+)*)+$/u.test(name) || words.length > 8 || name.length > 120) {
            return 'Escribí tu nombre y apellido reales, con al menos dos palabras y sin números ni símbolos.';
        }
        if (['nombre apellido', 'laboratorio digital'].includes(name.toLocaleLowerCase('es-AR'))
            || words.some(word => blockedWords.has(word.toLocaleLowerCase('es-AR')))) {
            return 'Escribí tu nombre y apellido reales, sin datos de prueba ni nombres de empresas.';
        }
        return '';
    };

    const normalizePhone = value => {
        const raw = value.trim();
        if (!/^\+?[\d\s().-]+$/u.test(raw)) return '';
        const international = raw.startsWith('+') || raw.startsWith('00');
        let digits = raw.replace(/\D/g, '');
        if (digits.startsWith('00')) digits = digits.slice(2);
        if (international && !digits.startsWith('54')) return '';
        const hasCountry = digits.startsWith('54') && digits.length > 11;
        if (hasCountry) digits = digits.slice(2);
        if (hasCountry && digits.startsWith('9') && [11, 12].includes(digits.length)) digits = digits.slice(1);
        digits = digits.replace(/^0+/, '');
        if (digits.startsWith('15')) return '';
        const local = digits.match(/^(\d{2,4})15(\d+)$/);
        if (local && [10, 11].includes((local[1] + local[2]).length)) digits = local[1] + local[2];
        if (![10, 11].includes(digits.length) || /^(\d)\1+$/u.test(digits)
            || ['1234567890', '9876543210', '12345678901', '10987654321'].includes(digits)) return '';
        return `+549${digits}`;
    };

    const showError = (field, element, message) => {
        element.textContent = message;
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
    };

    entryForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const fullName = fullNameInput.value.trim().replace(/\s+/gu, ' ');
        const nameMessage = validateName(fullName);
        const phone = normalizePhone(phoneInput.value);
        const phoneMessage = phone ? '' : (phoneInput.value.trim().startsWith('15')
            ? 'Agregá el código de área antes del 15.'
            : 'Revisá tu WhatsApp argentino e incluí el código de área. Ejemplo: 341 15 1234567.');
        showError(fullNameInput, nameError, nameMessage);
        showError(phoneInput, phoneError, phoneMessage);
        if (nameMessage || phoneMessage) return;
        fullNameInput.value = fullName;
        phoneInput.value = phone;
        const button = entryForm.querySelector('button');
        button.disabled = true;
        try {
            const response = await fetch(entryForm.action, { method: 'POST', body: new FormData(entryForm), credentials: 'same-origin' });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                showError(fullNameInput, nameError, data.field_errors?.full_name || '');
                showError(phoneInput, phoneError, data.field_errors?.phone || data.error || 'No se pudo validar el ingreso.');
                return;
            }
            window.location.reload();
        } catch (error) {
            showError(phoneInput, phoneError, 'No se pudo validar el ingreso. Intentá de nuevo.');
        } finally {
            button.disabled = false;
        }
    });
    fullNameInput?.addEventListener('input', () => showError(fullNameInput, nameError, ''));
    phoneInput?.addEventListener('input', () => showError(phoneInput, phoneError, ''));

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
            if (!fullNameInput.value && data.suggested_name) fullNameInput.value = data.suggested_name;
            if (!phoneInput.value && data.suggested_phone) phoneInput.value = data.suggested_phone;
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
