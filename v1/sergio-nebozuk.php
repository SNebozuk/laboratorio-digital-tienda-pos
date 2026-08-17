<?php
declare(strict_types=1);

$storePath = '/v1';
$assetPath = $storePath . '/assets';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fbf8ff">
    <title>Creá tu web con Codex · Sergio Nebozuk</title>
    <link rel="stylesheet" href="<?= $assetPath ?>/app.css">
    <link rel="stylesheet" href="<?= $assetPath ?>/light.css">
</head>
<body class="creator-page">
    <main class="creator-shell">
        <a class="creator-back" href="<?= $storePath ?>/">← Volver a Laboratorio Digital</a>
        <p class="eyebrow">CREÁ TU PROPIA WEB CON IA</p>
        <h1>Tu idea puede tener<br>su propia web.</h1>
        <p class="creator-intro">Con Codex podés crear una tienda, una página para tu negocio o una herramienta a medida sin saber programar. Le explicás lo que necesitás y lo construyen juntos, paso a paso.</p>
        <div class="creator-services">
            <article><strong>1 · CONTÁ TU IDEA</strong><span>Escribí con palabras simples qué querés crear y para quién.</span></article>
            <article><strong>2 · MIRÁ EL AVANCE</strong><span>Codex arma la web y vos pedís los cambios que necesites.</span></article>
            <article><strong>3 · PUBLICALA</strong><span>Cuando te guste, la dejás lista para compartir con tus clientes.</span></article>
        </div>
        <section class="creator-gift" aria-label="Invitación para comenzar">
            <strong>¿Querés probarlo?</strong>
            <p>Mandame tu email por WhatsApp y pedí <b>500 créditos gratuitos</b> para empezar a crear con Codex.</p>
        </section>
        <a class="primary-button creator-cta" href="https://wa.me/5493415699338?text=Hola%20Sergio%2C%20quiero%20recibir%20500%20cr%C3%A9ditos%20para%20empezar%20a%20crear%20mi%20web%20con%20Codex.%20Mi%20email%20es%3A%20" target="_blank" rel="noopener">QUIERO CREAR MI WEB CON CODEX</a>
        <small class="creator-signature">Una invitación de Sergio Nebozuk · creador de experiencias digitales</small>
    </main>
</body>
</html>
