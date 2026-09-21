<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
header('Location: ' . $app['checkout_google']->storeUrl());
exit;
