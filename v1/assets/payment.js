(() => {
    'use strict';

    const dataElement = document.getElementById('payment-data');
    if (!dataElement) {
        return;
    }

    const app = JSON.parse(dataElement.textContent);
    const form = document.getElementById('public-proof-form');
    const feedback = document.getElementById('payment-feedback');

    function showFeedback(message, type = '') {
        if (!feedback) {
            return;
        }
        feedback.textContent = message;
        feedback.className = `form-feedback ${type}`.trim();
    }

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-copy]');
        if (!button) {
            return;
        }
        try {
            await navigator.clipboard.writeText(button.dataset.copy);
            const original = button.textContent;
            button.textContent = 'COPIADO';
            window.setTimeout(() => {
                button.textContent = original;
            }, 1400);
        } catch {
            showFeedback('No pudimos copiar el dato. Mantenelo presionado para copiarlo.', 'error');
        }
    });

    form?.addEventListener('submit', async event => {
        event.preventDefault();
        const fileInput = form.querySelector('input[type="file"]');
        const button = form.querySelector('button[type="submit"]');
        const file = fileInput?.files?.[0];

        if (!file) {
            showFeedback('Elegí una imagen o PDF.', 'error');
            return;
        }
        if (file.size > Number(app.proof_max_bytes)) {
            showFeedback('El archivo supera el tamaño máximo permitido.', 'error');
            return;
        }

        const payload = new FormData();
        payload.append('action', 'upload_proof');
        payload.append('csrf_token', app.csrf_token);
        payload.append('order_id', String(app.order_id));
        payload.append('upload_token', app.upload_token);
        payload.append('proof', file);

        button.disabled = true;
        button.textContent = 'SUBIENDO…';
        showFeedback('Estamos cargando y validando el stock…');

        try {
            const response = await fetch(app.api_url, {
                method: 'POST',
                headers: { 'X-CSRF-Token': app.csrf_token },
                body: payload,
            });
            const result = await response.json();
            if (!response.ok || !result.ok) {
                throw new Error(
                    result.error || 'No pudimos subir el comprobante.'
                );
            }

            showFeedback(
                'Comprobante recibido. El stock quedó reservado y el pago está pendiente de verificación.',
                'success'
            );
            form.remove();
            document.getElementById('payment-status-copy').textContent =
                'Recibimos el comprobante. El stock está reservado mientras verificamos el pago.';
            const status = document.getElementById('payment-status');
            status.textContent = 'Pago informado';
            status.className = 'status-pill status-payment_reported';
        } catch (error) {
            showFeedback(error.message, 'error');
            button.disabled = false;
            button.textContent = 'SUBIR COMPROBANTE';
        }
    });
})();
