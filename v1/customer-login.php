<?php
declare(strict_types=1);

use LaboratorioDigital\Http;

$app = require dirname(__DIR__) . '/app/container.php';
$customers = $app['checkout_google'];

try {
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
        header('Location: ' . $customers->storeUrl());
        exit;
    }
    Http::requireCsrf($_POST);
    $customers->createLocalCustomer(
        (string) ($_POST['first_name'] ?? ''),
        (string) ($_POST['last_name'] ?? ''),
        (string) ($_POST['phone'] ?? '')
    );
    header('Location: ' . $customers->storeUrl());
    exit;
} catch (Throwable $exception) {
    header('Location: ' . $customers->storeUrl() . '?customer_error=' . rawurlencode($exception->getMessage()));
    exit;
}
