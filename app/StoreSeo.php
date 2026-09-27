<?php
declare(strict_types=1);

namespace LaboratorioDigital;

/** Metadatos basados exclusivamente en el catálogo y contacto públicos. */
final class StoreSeo
{
    public const ORIGIN = 'https://www.laboratoriodigital.com.ar';

    public static function storeUrl(string $storePath): string
    {
        $path = trim($storePath, '/');
        return self::ORIGIN . '/' . ($path === '' ? '' : $path . '/');
    }

    public static function imageUrl(?string $path, string $storeUrl): ?string
    {
        $path = trim((string) $path);
        if ($path === '') return null;
        if (str_starts_with($path, '//')) return 'https:' . $path;
        if (preg_match('~^https?://~i', $path)) return $path;
        if (str_starts_with($path, '/')) return self::ORIGIN . $path;
        return $storeUrl . $path;
    }

    /** @param array<string,mixed> $settings @param array<string,mixed>|null $product */
    public static function structuredData(string $storeUrl, array $settings, ?array $product): array
    {
        $organization = [
            '@type' => 'Organization',
            '@id' => $storeUrl . '#organization',
            'name' => trim((string) ($settings['store_name'] ?? '')) ?: 'Laboratorio Digital',
            'url' => $storeUrl,
        ];
        $address = trim((string) ($settings['pickup_address'] ?? ''));
        if ($address !== '') $organization['address'] = $address;
        $whatsapp = preg_replace('/\D+/', '', (string) ($settings['whatsapp_number'] ?? ''));
        if ($whatsapp !== '') {
            $organization['contactPoint'] = [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'url' => 'https://wa.me/' . $whatsapp,
            ];
        }
        $graph = [$organization, [
            '@type' => 'WebSite',
            '@id' => $storeUrl . '#website',
            'url' => $storeUrl,
            'name' => $organization['name'],
            'inLanguage' => 'es',
            'publisher' => ['@id' => $organization['@id']],
        ]];
        if ($product !== null) {
            $productUrl = $storeUrl . '?producto=' . (int) $product['id'];
            $entry = [
                '@type' => 'Product',
                '@id' => $productUrl . '#product',
                'url' => $productUrl,
                'name' => (string) $product['name'],
            ];
            $description = trim(strip_tags((string) ($product['description'] ?? '')));
            if ($description !== '') $entry['description'] = $description;
            $category = trim((string) ($product['category']['name'] ?? ''));
            if ($category !== '') $entry['category'] = $category;
            $image = self::imageUrl($product['image_path'] ?? null, $storeUrl);
            if ($image !== null) $entry['image'] = $image;
            $offers = [];
            foreach ($product['variants'] as $variant) {
                if (($variant['price_cents'] ?? null) === null) continue;
                $offer = [
                    '@type' => 'Offer',
                    'name' => (string) $variant['name'],
                    'url' => $productUrl,
                    'priceCurrency' => 'ARS',
                    'price' => number_format((int) $variant['price_cents'] / 100, 2, '.', ''),
                    'seller' => ['@id' => $organization['@id']],
                ];
                if (($variant['available_stock'] ?? null) !== null) {
                    $offer['availability'] = $variant['available_stock'] > 0
                        ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
                }
                $offers[] = $offer;
            }
            if ($offers !== []) $entry['offers'] = $offers;
            $graph[] = $entry;
        }
        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }
}
