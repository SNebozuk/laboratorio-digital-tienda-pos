<?php
declare(strict_types=1);

$text = 'LD';
$font = 'Arial, sans-serif';
$background = '#050505';
$foreground = '#ff5ca8';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

header('Content-Type: image/svg+xml; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="<?= $escape($text) ?>">
    <rect width="64" height="64" rx="14" fill="<?= $escape($background) ?>"/>
    <text x="32" y="34" fill="<?= $escape($foreground) ?>" font-family="<?= $escape($font) ?>" font-size="32" font-weight="700" text-anchor="middle" dominant-baseline="central"><?= $escape($text) ?></text>
</svg>
