<?php
declare(strict_types=1);

$storePath = '/v1';
$assetPath = $storePath . '/assets';
$apiPath = $storePath . '/api.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf8ff">
    <title>Creá tu web con ChatGPT Codex · Sergio Nebozuk</title>
    <link rel="stylesheet" href="<?= $assetPath ?>/app.css">
    <link rel="stylesheet" href="<?= $assetPath ?>/light.css">
</head>
<body class="creator-page">
    <main class="creator-shell">
        <a class="creator-back" href="<?= $storePath ?>/">← Volver a Laboratorio Digital</a>
        <p class="eyebrow">CREÁ TU PROPIA WEB CON CHATGPT CODEX</p>
        <h1>Tu idea puede tener<br>su propia web.</h1>
        <p class="creator-intro">ChatGPT Codex es una herramienta de inteligencia artificial con la que podés crear una tienda, una página para tu negocio o una solución a medida sin saber programar. Le explicás lo que necesitás y lo construyen juntos, paso a paso.</p>
        <div class="creator-services">
            <article><strong>1 · CONTÁ TU IDEA</strong><span>Escribí con palabras simples qué querés crear y para quién.</span></article>
            <article><strong>2 · MIRÁ EL AVANCE</strong><span>Codex arma la web y vos pedís los cambios que necesites.</span></article>
            <article><strong>3 · PUBLICALA</strong><span>Cuando te guste, la dejás lista para compartir con tus clientes.</span></article>
        </div>
        <section class="creator-gift" aria-label="Invitación para comenzar">
            <strong>¿Querés probar ChatGPT Codex?</strong>
            <p>Escribí tu email y recibí créditos gratis para hacer tu página.</p>
            <form class="creator-invitation-form" id="creator-invitation-form">
                <label for="creator-email">Tu email</label>
                <div class="creator-invitation-fields">
                    <input id="creator-email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="nombre@ejemplo.com" required>
                    <button class="primary-button" type="submit">SOLICITAR INVITACIÓN</button>
                </div>
                <input class="creator-honeypot" name="website" type="text" tabindex="-1" autocomplete="off" aria-hidden="true">
                <small id="creator-invitation-status" role="status" aria-live="polite">Usaremos tu email sólo para gestionar esta solicitud.</small>
            </form>
        </section>
        <small class="creator-signature">Una invitación de Sergio Nebozuk · creador de experiencias digitales</small>
    </main>
    <script>
    (() => {
        const form = document.getElementById('creator-invitation-form');
        const status = document.getElementById('creator-invitation-status');
        if (!form || !status) return;
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const data = new FormData(form);
            button.disabled = true;
            status.textContent = 'Guardando tu solicitud…';
            try {
                const response = await fetch('<?= htmlspecialchars($apiPath, ENT_QUOTES, 'UTF-8') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ action: 'invitation_request', email: data.get('email'), website: data.get('website') }),
                });
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.error || 'No pudimos guardar tu solicitud.');
                status.textContent = '¡Listo! Tu solicitud quedó registrada. Sergio te indicará cómo seguir.';
                form.reset();
            } catch (error) {
                status.textContent = error.message || 'No pudimos guardar tu solicitud. Intentá nuevamente.';
            } finally {
                button.disabled = false;
            }
        });
    })();
    </script>
</body>
</html>
