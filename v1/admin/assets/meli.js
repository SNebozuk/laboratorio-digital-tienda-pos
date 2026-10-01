(() => {
    'use strict';
    const panel = document.getElementById('view-meli');
    if (!panel) return;
    const app = JSON.parse(document.getElementById('admin-app-data').textContent);
    const endpoint = new URL('meli.php', window.location.href);
    const message = document.getElementById('meli-status-message');
    const account = document.getElementById('meli-account');
    const connect = document.getElementById('meli-connect');
    const verify = document.getElementById('meli-verify');
    const footer = document.getElementById('meli-progress-footer');
    const progressText = document.getElementById('meli-progress-text');
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
        message.textContent = text;
    }
    async function request(options = {}) {
        const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store', ...options });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo verificar la conexión.');
        return data;
    }
    async function check() {
        if (busy) return;
        busy = true;
        verify.disabled = true;
        connect.disabled = true;
        indicator(false, 'Verificando conexión…');
        progress('consultando el acceso a la cuenta…', true);
        account.textContent = '';
        try {
            const data = await request();
            indicator(data.connected === true, data.message);
            progress(data.message);
            account.textContent = data.connected ? `Cuenta: ${data.nickname} · ID ${data.user_id}` : '';
            connect.disabled = !data.configured;
            if (data.redirect_uri) document.getElementById('meli-redirect-uri').textContent = data.redirect_uri;
            document.getElementById('meli-authorization-result').textContent = data.authorization_result || '';
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
    verify.addEventListener('click', check);
    window.MeliWorkspace = { activate: check };
    check();
    window.setInterval(() => {
        if (!document.hidden && panel.classList.contains('active')) check();
    }, 30000);
})();
