<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$google = $app['checkout_google'];
$cart = is_array($_SESSION['checkout_google_cart'] ?? null) ? $_SESSION['checkout_google_cart'] : [];
unset($_SESSION['checkout_google_cart']);

$storeUrl = static function (array $parameters) use ($google, $cart): string {
    if ($cart) $parameters['google_cart'] = json_encode($cart, JSON_UNESCAPED_SLASHES);
    return $google->storeUrl() . '?' . http_build_query($parameters);
};

$fail = static function (string $message) use ($storeUrl): never {
    header('Location: ' . $storeUrl(['google_error' => $message]));
    exit;
};

try {
    if (!$google->enabled()) $fail('El acceso con Google todavía no está configurado.');
    if (!hash_equals((string) ($_SESSION['checkout_google_state'] ?? ''), (string) ($_GET['state'] ?? ''))) {
        $fail('No pudimos validar el acceso con Google. Intentá nuevamente.');
    }
    unset($_SESSION['checkout_google_state']);
    $code = trim((string) ($_GET['code'] ?? ''));
    if ($code === '') $fail('Google no devolvió una autorización válida.');

    $payload = http_build_query([
        'code' => $code,
        'client_id' => $app['config']['google_client_id'],
        'client_secret' => $app['config']['google_client_secret'],
        'redirect_uri' => $google->callbackUrl(),
        'grant_type' => 'authorization_code',
    ]);
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($payload),
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true,
    ]]);
    $raw = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
    $token = json_decode((string) $raw, true);
    if (!is_array($token) || empty($token['access_token'])) throw new RuntimeException('No pudimos validar Google.');

    $profileRaw = file_get_contents('https://openidconnect.googleapis.com/v1/userinfo', false, stream_context_create(['http' => [
        'header' => 'Authorization: Bearer ' . $token['access_token'],
        'timeout' => 15,
        'ignore_errors' => true,
    ]]));
    $profile = json_decode((string) $profileRaw, true);
    if (!is_array($profile) || empty($profile['sub']) || empty($profile['email']) || empty($profile['email_verified'])) {
        throw new RuntimeException('Google no confirmó un email válido.');
    }
    $google->linkProfile($profile);
    header('Location: ' . $storeUrl(['google_checkout' => '1']));
    exit;
} catch (Throwable) {
    $fail('No pudimos ingresar con Google. Intentá nuevamente.');
}
