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
    $input = Http::input();
    Http::requireCsrf($input);
    $message = trim((string) ($input['message'] ?? ''));
    if ($message === '' || (preg_match_all('/./us', $message) ?: 0) > 500) throw new \RuntimeException('Escribí un mensaje breve para continuar.');
    $state = is_array($_SESSION['reception'] ?? null) ? $_SESSION['reception'] : ['history' => [], 'first_name' => '', 'last_name' => '', 'phone' => ''];
    $history = is_array($state['history'] ?? null) ? array_slice($state['history'], -10) : [];
    $history[] = ['role' => 'user', 'content' => $message];
    $reply = $app['reception_ai']->reply($history, (string) ($state['first_name'] ?? ''), (string) ($state['last_name'] ?? ''), (string) ($state['phone'] ?? ''));
    $firstName = (string) $reply['first_name'];
    $lastName = (string) $reply['last_name'];
    $rawPhone = (string) $reply['phone'];
    $phone = \LaboratorioDigital\CustomerService::normalizeWhatsapp($rawPhone);
    $validName = static fn (string $value): bool => \LaboratorioDigital\CheckoutGoogleService::validNamePart($value);
    $badFirstName = $firstName !== '' && (!$validName($firstName) || !$reply['first_name_plausible']);
    $badLastName = $lastName !== '' && (!$validName($lastName) || !$reply['last_name_plausible']);
    if ($badFirstName) $firstName = '';
    if ($badLastName) $lastName = '';
    $validPhone = $phone !== '';
    $known = $validPhone && !$badFirstName && !$badLastName ? $customers->knownWhatsapp($phone) : null;
    if ($known !== null) {
        $parts = preg_split('/\s+/u', trim($known['name'])) ?: [];
        $firstName = (string) array_shift($parts);
        $lastName = implode(' ', $parts);
    }
    if ($validName($firstName) && $validName($lastName) && $validPhone) {
        $customers->createLocalCustomer($firstName, $lastName, $phone);
        unset($_SESSION['reception']);
        Http::json(['ok' => true, 'entered' => true, 'reply' => '¡Hola, ' . $firstName . '! Ya podés entrar a la tienda.']);
    }
    $answer = $reply['message'];
    if ($badFirstName) $answer .= ' Escribí tu nombre real y bien escrito para poder ingresar.';
    if ($badLastName) $answer .= ' Escribí tu apellido real y bien escrito para poder ingresar.';
    if ($rawPhone !== '' && !$validPhone) $answer .= ' Ese WhatsApp no tiene un formato válido. Revisá el código de área y el número; por ejemplo, 341 15 1234567 o +54 9 341 1234567.';
    $history[] = ['role' => 'assistant', 'content' => $answer];
    $_SESSION['reception'] = ['history' => array_slice($history, -10), 'first_name' => $firstName, 'last_name' => $lastName, 'phone' => $phone];
    Http::json(['ok' => true, 'entered' => false, 'reply' => $answer]);
} catch (Throwable $exception) {
    Http::json(['ok' => false, 'error' => $exception->getMessage()], 400);
}
