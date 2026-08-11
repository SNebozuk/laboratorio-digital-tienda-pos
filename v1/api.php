<?php
declare(strict_types=1);

// Punto de entrada de la API dentro de la tienda pÃºblica. Evita que una
// protecciÃ³n HTTP heredada del directorio principal interfiera con los pedidos.
require dirname(__DIR__) . '/api.php';
