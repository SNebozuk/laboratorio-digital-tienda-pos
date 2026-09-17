<?php
declare(strict_types=1);

header('Content-Type: application/manifest+json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
?>
{
  "name": "Laboratorio Digital · Administración",
  "short_name": "LD Admin",
  "description": "Administración de Laboratorio Digital.",
  "lang": "es-AR",
  "id": "./",
  "start_url": "./",
  "scope": "./",
  "display": "standalone",
  "display_override": ["window-controls-overlay", "standalone"],
  "prefer_related_applications": false,
  "background_color": "#111827",
  "theme_color": "#111827",
  "icons": [
    { "src": "../assets/pwa-icon-192.png", "sizes": "192x192", "type": "image/png", "purpose": "any" },
    { "src": "../assets/pwa-icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "any" }
  ]
}
