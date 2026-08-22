<?php
declare(strict_types=1);

use LaboratorioDigital\Auth;
use LaboratorioDigital\BackupService;
use LaboratorioDigital\CategoryService;
use LaboratorioDigital\DeliveryService;
use LaboratorioDigital\InvitationService;
use LaboratorioDigital\MailService;
use LaboratorioDigital\OrderService;
use LaboratorioDigital\PaymentProofService;
use LaboratorioDigital\ProductService;
use LaboratorioDigital\ProductImageService;
use LaboratorioDigital\ReceiptAiService;
use LaboratorioDigital\SettingsService;
use LaboratorioDigital\StockService;
use LaboratorioDigital\StoreVisitService;
use LaboratorioDigital\TutorialService;

$app = require __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/BackupService.php';
require_once __DIR__ . '/CategoryService.php';
require_once __DIR__ . '/DeliveryService.php';
require_once __DIR__ . '/InvitationService.php';
require_once __DIR__ . '/MailService.php';
require_once __DIR__ . '/ProductService.php';
require_once __DIR__ . '/ProductImageService.php';
require_once __DIR__ . '/StockService.php';
require_once __DIR__ . '/StoreVisitService.php';
require_once __DIR__ . '/TutorialService.php';
require_once __DIR__ . '/OrderService.php';
require_once __DIR__ . '/PaymentProofService.php';
require_once __DIR__ . '/ReceiptAiService.php';
require_once __DIR__ . '/SettingsService.php';

$app['auth'] = new Auth($app['pdo']);
$app['products'] = new ProductService($app['pdo']);
$app['categories'] = new CategoryService($app['pdo']);
$app['tutorials'] = new TutorialService($app['pdo']);
$app['deliveries'] = new DeliveryService($app['pdo']);
$app['invitations'] = new InvitationService($app['pdo']);
$app['product_images'] = new ProductImageService(
    $app['root'],
    $app['config']
);
$app['stock'] = new StockService($app['pdo']);
$app['store_visits'] = new StoreVisitService($app['pdo']);
$app['mail'] = new MailService($app['pdo'], $app['config']);
$app['orders'] = new OrderService(
    $app['pdo'],
    $app['stock'],
    $app['config']
);
$app['receipt_ai'] = new ReceiptAiService($app['config']);
$app['proofs'] = new PaymentProofService(
    $app['pdo'],
    $app['stock'],
    $app['config'],
    $app['receipt_ai']
);
$app['backups'] = new BackupService($app['pdo'], $app['config'], $app['root']);
$app['settings'] = new SettingsService($app['pdo']);

return $app;
