<?php
declare(strict_types=1);

// Run once per minute: retry deliveries and reconcile notifications delayed by MeLi.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$app = require dirname(__DIR__) . '/app/container.php';
require_once dirname(__DIR__) . '/app/MercadoLibreService.php';
$result = (new LaboratorioDigital\MercadoLibreService($app['pdo'], $app['config']))->processNotifications(true);
if ($result['failed']) error_log('MeLi: ' . $result['failed'] . ' notificaciones de stock pendientes de reintento.');
