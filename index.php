<?php
declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'self'; form-action 'self' https://www.laboratoriodigital.com.ar https://wa.me");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Robots-Tag: noindex, nofollow');

$maintenancePage = __DIR__ . '/maintenance/artjet/index.html';
if (!is_file($maintenancePage)) {
    http_response_code(503);
    echo 'Sitio en preparación.';
    exit;
}

readfile($maintenancePage);
