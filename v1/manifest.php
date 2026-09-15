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
  "id": "./",
  "start_url": "./",
  "scope": "./",
  "display": "standalone",
  "prefer_related_applications": false,
  "background_color": "#f7faf7",
  "theme_color": "#f7faf7",
  "icons": [
    {
      "src": "assets/pwa-icon-192.png",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    },
    {
      "src": "assets/pwa-icon-512.png",
      "sizes": "512x512",
      "type": "image/png",
      "purpose": "any"
    }
  ]
}
