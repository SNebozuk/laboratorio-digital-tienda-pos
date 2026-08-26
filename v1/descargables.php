<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
\LaboratorioDigital\Http::noCache();
$design = $app['settings']->design();
$storePath = '/' . trim((string) ($app['config']['public_store_path'] ?? '/v1'), '/');
$storePath = $storePath === '/' ? '' : $storePath;
$assetPath = $storePath . '/assets';
$assetVersion = substr(hash('sha256', (string) @file_get_contents(__DIR__ . '/assets/app.css') . (string) @file_get_contents(__DIR__ . '/assets/light.css')), 0, 12);
$storeUrl = $storePath === '' ? '/' : $storePath . '/';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descargables · Laboratorio Digital</title>
    <link rel="icon" href="<?= $escape($assetPath) ?>/favicon.png" type="image/png">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/app.css?v=<?= $escape($assetVersion) ?>">
    <link rel="stylesheet" href="<?= $escape($assetPath) ?>/light.css?v=<?= $escape($assetVersion) ?>">
</head>
<body class="downloads-page">
    <header class="store-header">
        <div class="header-leading"><a class="brand" href="<?= $escape($storeUrl) ?>"><img class="brand-logo" src="<?= $escape($design['logo_path']) ?>" alt="Laboratorio Digital"></a></div>
        <a class="downloads-back-header" href="<?= $escape($storeUrl) ?>">← Volver a la tienda</a>
    </header>
    <main class="downloads-shell">
        <a class="downloads-back" href="<?= $escape($storeUrl) ?>">← Volver a la tienda <small>ESC</small></a>
        <header class="downloads-intro"><p>RECURSOS GRATUITOS</p><h1>Descargables</h1><span>Elegí un diseño y descargalo directamente desde Laboratorio Digital.</span></header>
        <section class="downloads-grid" aria-label="Archivos descargables">
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/mochila-verde.jpg" alt="Mochila para colorear verde"><div><h2>Mochila para colorear · verde</h2><p>Una mochilita para colorear, ideal para llenar de golosinas y regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/mochila-verde.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/mochila-rosa.jpg" alt="Mochila para colorear rosa"><div><h2>Mochila para colorear · rosa</h2><p>Una versión alternativa de la mochilita para imprimir, colorear y regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/mochila-rosa.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/llaveros-san-valentin.jpg" alt="Llaveros para San Valentín"><div><h2>Llaveros para San Valentín</h2><p>Diseños de llaveros para crear un regalo especial.</p><a href="<?= $escape($assetPath) ?>/downloads/llaveros-san-valentin.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/cartas-a-santa.jpg" alt="Cartas a Santa"><div><h2>Cartas a Santa</h2><p>Cartas y sobres listos para imprimir y preparar los deseos de Navidad.</p><a href="<?= $escape($assetPath) ?>/downloads/cartas-a-santa.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/buzon-santa.jpg" alt="Buzón de Santa"><div><h2>Buzón de Santa</h2><p>Plantilla para armar un buzón navideño.</p><a href="<?= $escape($assetPath) ?>/downloads/buzon-santa.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/calendario-2026.jpg" alt="Calendario 2026"><div><h2>Calendario 2026</h2><p>Calendario listo para imprimir.</p><a href="<?= $escape($assetPath) ?>/downloads/calendario-2026.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/dia-amigo.jpg" alt="Día del amigo"><div><h2>Día del amigo</h2><p>Diseño para celebrar y regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/dia-amigo.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/semana-dulzura.jpg" alt="Semana de la dulzura"><div><h2>Semana de la dulzura</h2><p>Diseño especial para dulces y regalos.</p><a href="<?= $escape($assetPath) ?>/downloads/semana-dulzura.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/animalitos-canvas.jpg" alt="Animalitos con canvas"><div><h2>Animalitos con canvas</h2><p>Figuras para imprimir, recortar y armar.</p><a href="<?= $escape($assetPath) ?>/downloads/animalitos-canvas.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/anotador-estudiante.jpg" alt="Anotador día del estudiante"><div><h2>Anotador día del estudiante</h2><p>Diseño de anotador para regalar.</p><a href="<?= $escape($assetPath) ?>/downloads/anotador-estudiante.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/bienvenida-reyes.jpg" alt="Bienvenida para los Reyes Magos"><div><h2>Bienvenida para los Reyes Magos</h2><p>Cartel de bienvenida para imprimir.</p><a href="<?= $escape($assetPath) ?>/downloads/bienvenida-reyes.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/bolsita-visor.jpg" alt="Bolsita con visor"><div><h2>Bolsita con visor</h2><p>Plantilla de bolsita para armar.</p><a href="<?= $escape($assetPath) ?>/downloads/bolsita-visor.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/bolsita-estampitas.jpg" alt="Bolsita para estampitas"><div><h2>Bolsita para estampitas</h2><p>Packaging para recuerdos y estampitas.</p><a href="<?= $escape($assetPath) ?>/downloads/bolsita-estampitas.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/cajita-bruma.jpg" alt="Cajita con Bruma Clara"><div><h2>Cajita con Bruma Clara</h2><p>Plantilla de caja para imprimir y armar.</p><a href="<?= $escape($assetPath) ?>/downloads/cajita-bruma.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/pulseras-egresados.jpg" alt="Pulseras de egresados"><div><h2>Pulseras de egresados</h2><p>Diseños para egresados, eventos y recuerdos personalizados.</p><a href="<?= $escape($assetPath) ?>/downloads/pulseras-egresados.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/guirnalda-halloween.jpg" alt="Guirnalda de Halloween"><div><h2>Guirnalda de Halloween</h2><p>Plantilla para decorar Halloween en tres simples pasos.</p><a href="<?= $escape($assetPath) ?>/downloads/guirnalda-halloween.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/regalo-mama.jpg" alt="Regalo para mamá"><div><h2>Regalo para mamá</h2><p>Un detalle creativo para preparar y regalar con mucho cariño.</p><a href="<?= $escape($assetPath) ?>/downloads/regalo-mama.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/laberinto-ninez.jpg" alt="Laberinto Día de la Niñez"><div><h2>Laberinto Día de la Niñez</h2><p>Actividad con versiones espacial y de unicornios para imprimir.</p><a href="<?= $escape($assetPath) ?>/downloads/laberinto-ninez.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/memotest-italiano.jpg" alt="Memotest italiano"><div><h2>Memotest italiano</h2><p>Juego de memoria para imprimir, recortar y disfrutar.</p><a href="<?= $escape($assetPath) ?>/downloads/memotest-italiano.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/dia-amigo-frogcreativo.jpg" alt="Día del amigo por Frogcreativo"><div><h2>Día del amigo · Frogcreativo</h2><p>Un imprimible original con mensajes lindos para sorprender.</p><a href="<?= $escape($assetPath) ?>/downloads/dia-amigo-frogcreativo.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/tarjeta-mariposa-primavera.jpg" alt="Tarjeta mariposa de primavera"><div><h2>Tarjeta mariposa de primavera</h2><p>Tarjeta mariposa para celebrar la llegada de la primavera.</p><a href="<?= $escape($assetPath) ?>/downloads/tarjeta-mariposa-primavera.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/mini-candy.jpg" alt="Mini-Candy"><div><h2>Mini-Candy</h2><p>Plantilla para armar un Mini-Candy y llenarlo de dulces.</p><a href="<?= $escape($assetPath) ?>/downloads/mini-candy.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/tatuajes-navidenos.jpg" alt="Tatuajes navideños"><div><h2>Tatuajes navideños</h2><p>Tatuajes navideños listos para imprimir y recortar.</p><a href="<?= $escape($assetPath) ?>/downloads/tatuajes-navidenos.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/plantilla-primavera.jpg" alt="Plantilla primaveral"><div><h2>Plantilla primaveral</h2><p>Plantilla primaveral para imprimir y armar.</p><a href="<?= $escape($assetPath) ?>/downloads/plantilla-primavera.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/adorno-cajita-navidad.jpg" alt="Adorno cajita de Navidad"><div><h2>Adorno cajita de Navidad</h2><p>Adorno navideño en forma de cajita para imprimir y armar.</p><a href="<?= $escape($assetPath) ?>/downloads/adorno-cajita-navidad.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
            <article class="download-card"><img src="<?= $escape($assetPath) ?>/downloads/sobre-fotos-dia-padre.jpg" alt="Sobre para fotos Día del Padre"><div><h2>Sobre para fotos · Día del Padre</h2><p>Sobre para fotos, especial para el Día del Padre.</p><a href="<?= $escape($assetPath) ?>/downloads/sobre-fotos-dia-padre.pdf" download><span>↓</span> DESCARGAR PDF</a></div></article>
        </section>
    </main>
    <script>document.addEventListener('keydown', event => { if (event.key === 'Escape') window.location.href = <?= json_encode($storeUrl) ?>; });</script>
</body>
</html>
