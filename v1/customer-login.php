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
    if (($input['action'] ?? '') === 'register') {
        $fullName = trim(preg_replace('/\s+/u', ' ', (string) ($input['full_name'] ?? '')) ?? '');
        $rawPhone = trim((string) ($input['phone'] ?? ''));
        $phone = \LaboratorioDigital\CustomerService::normalizeWhatsapp($rawPhone);
        $errors = [];
        if (!\LaboratorioDigital\CheckoutGoogleService::validFullName($fullName)) {
            $errors['full_name'] = 'Escribí tu nombre y apellido reales, con al menos dos palabras y sin números ni símbolos.';
        }
        if ($phone === '') {
            $errors['phone'] = 'Revisá tu WhatsApp argentino e incluí el código de área. Ejemplo: 341 15 1234567.';
        }
        if ($errors !== []) Http::json(['ok' => false, 'field_errors' => $errors], 422);
        $parts = explode(' ', $fullName, 2);
        try {
            $customers->createLocalCustomer($parts[0], $parts[1], $phone);
        } catch (RuntimeException $exception) {
            Http::json(['ok' => false, 'field_errors' => ['phone' => $exception->getMessage()]], 422);
        }
        unset($_SESSION['reception']);
        Http::json(['ok' => true, 'entered' => true]);
    }
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
    $answer = $reply['message'];
    if ($validName($firstName) && $validName($lastName) && $validPhone) $answer .= ' Para entrar, confirmá tus datos en el formulario.';
    if ($badFirstName) $answer .= ' Escribí tu nombre real y bien escrito para poder ingresar.';
    if ($badLastName) $answer .= ' Escribí tu apellido real y bien escrito para poder ingresar.';
    if ($rawPhone !== '' && !$validPhone) $answer .= ' Ese WhatsApp no tiene un formato válido. Revisá el código de área y el número; por ejemplo, 341 15 1234567 o +54 9 341 1234567.';
    $history[] = ['role' => 'assistant', 'content' => $answer];
    $_SESSION['reception'] = ['history' => array_slice($history, -10), 'first_name' => $firstName, 'last_name' => $lastName, 'phone' => $phone];
    Http::json(['ok' => true, 'entered' => false, 'reply' => $answer, 'suggested_name' => trim($firstName . ' ' . $lastName), 'suggested_phone' => $rawPhone]);
} catch (Throwable $exception) {
    Http::json(['ok' => false, 'error' => $exception->getMessage()], 400);
}
