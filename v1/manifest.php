<?php
declare(strict_types=1);

header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
?>
{
  "name": "Laboratorio Digital",
  "short_name": "Laboratorio Digital",
  "description": "Catálogo mayorista de Laboratorio Digital.",
  "lang": "es-AR",
  "start_url": "./",
  "scope": "./",
  "display": "standalone",
  "background_color": "#f7faf7",
  "theme_color": "#f7faf7",
  "icons": [
    {
      "src": "assets/favicon.png",
      "sizes": "1254x1254",
      "type": "image/png",
      "purpose": "any maskable"
    }
  ]
}
