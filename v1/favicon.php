<?php
declare(strict_types=1);

$app = require dirname(__DIR__) . '/app/container.php';
$design = $app['settings']->design();
$fontStacks = [
    'Arial' => 'Arial, sans-serif',
    'Helvetica' => 'Helvetica, Arial, sans-serif',
    'Verdana' => 'Verdana, sans-serif',
    'Georgia' => 'Georgia, serif',
    'Times New Roman' => 'Times New Roman, serif',
    'Trebuchet MS' => 'Trebuchet MS, sans-serif',
    'Montserrat' => 'Montserrat, Arial, sans-serif',
    'Roboto' => 'Roboto, Arial, sans-serif',
    'Poppins' => 'Poppins, Arial, sans-serif',
    'Oswald' => 'Oswald, Arial, sans-serif',
    'Inter' => 'Inter, Arial, sans-serif',
    'Bebas Neue' => 'Bebas Neue, Arial, sans-serif',
];
$text = trim((string) ($design['favicon_text'] ?? 'LD')) ?: 'LD';
$font = $fontStacks[(string) ($design['favicon_font'] ?? '')] ?? $fontStacks['Arial'];
$background = strtolower((string) ($design['favicon_background_color'] ?? '#7652b8'));
$foreground = strtolower((string) ($design['favicon_text_color'] ?? '#ffffff'));
if (!preg_match('/^#[0-9a-f]{6}$/', $background)) $background = '#7652b8';
if (!preg_match('/^#[0-9a-f]{6}$/', $foreground)) $foreground = '#ffffff';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="<?= $escape($text) ?>">
    <rect width="64" height="64" rx="14" fill="<?= $escape($background) ?>"/>
    <text x="32" y="34" fill="<?= $escape($foreground) ?>" font-family="<?= $escape($font) ?>" font-size="32" font-weight="700" text-anchor="middle" dominant-baseline="central"><?= $escape($text) ?></text>
</svg>
