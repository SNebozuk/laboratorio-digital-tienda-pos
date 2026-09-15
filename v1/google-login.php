<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$google = $app['checkout_google'];

if (!$google->enabled()) {
    header('Location: ' . $google->storeUrl() . '?google_error=' . rawurlencode('El acceso con Google todavía no está configurado.'));
    exit;
}

$canonicalUrl = $google->loginUrl();
$currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$canonicalHost = strtolower((string) (parse_url($canonicalUrl, PHP_URL_HOST) ?? ''));
if ($currentHost !== '' && $canonicalHost !== '' && $currentHost !== $canonicalHost) {
    header('Location: ' . $canonicalUrl, true, 302);
    exit;
}

$state = bin2hex(random_bytes(24));
$_SESSION['checkout_google_state'] = $state;
$redirect = $google->callbackUrl();
$url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $app['config']['google_client_id'],
    'redirect_uri' => $redirect,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
]);
header('Location: ' . $url);
exit;
