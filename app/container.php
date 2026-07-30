<?php
declare(strict_types=1);

use LaboratorioDigital\Auth;
use LaboratorioDigital\BackupService;
use LaboratorioDigital\CashService;
use LaboratorioDigital\MailService;
use LaboratorioDigital\OrderService;
use LaboratorioDigital\PaymentProofService;
use LaboratorioDigital\ProductService;
use LaboratorioDigital\ReportService;
use LaboratorioDigital\SettingsService;
use LaboratorioDigital\StockService;

$app = require __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/BackupService.php';
require_once __DIR__ . '/ProductService.php';
require_once __DIR__ . '/StockService.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/PaymentProofService.php';
require_once __DIR__ . '/CashService.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/ReportService.php';
require_once __DIR__ . '/SettingsService.php';

$app['auth'] = new Auth($app['pdo']);
$app['products'] = new ProductService($app['pdo']);
$app['stock'] = new StockService($app['pdo']);
$app['orders'] = new OrderService(
    $app['pdo'],
    $app['stock'],
    $app['config']
);
$app['proofs'] = new PaymentProofService(
    $app['pdo'],
    $app['stock'],
    $app['config']
);
$app['cash'] = new CashService($app['pdo']);
$app['reports'] = new ReportService($app['pdo']);
$app['backups'] = new BackupService($app['pdo'], $app['config']);
$app['mail'] = new MailService($app['pdo'], $app['config']);
$app['settings'] = new SettingsService($app['pdo']);

return $app;
