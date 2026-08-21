<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use PDOException;
use Throwable;

final class Database
{
    /**
     * Marca que todas las migraciones históricas de esta versión ya fueron
     * aplicadas. Evita recorrer el esquema completo en cada visita pública.
     */
    private const CURRENT_MIGRATION_VERSION = 30;

    public static function connect(string $databasePath, string $schemaPath): PDO
    {
        if (!extension_loaded('pdo_sqlite')) {
            throw new \RuntimeException(
                'El servidor necesita tener habilitada la extensión pdo_sqlite.'
            );
        }

        $directory = dirname($databasePath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear el almacenamiento privado.');
        }

        try {
            $pdo = new PDO('sqlite:' . $databasePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new \RuntimeException(
                'No se pudo abrir la base de datos.',
                0,
                $exception
            );
        }

        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');

        self::migrate($pdo, $schemaPath);

        return $pdo;
    }

    public static function migrate(PDO $pdo, string $schemaPath): void
    {
        // La aplicación abre SQLite en cada petición PHP. Antes se ejecutaba
        // todo el archivo de esquema y cada reparación histórica en cada
        // carga de la tienda, aun cuando la base ya estaba actualizada. En un
        // hosting compartido eso agrega una demora muy perceptible.
        try {
            $current = $pdo->prepare(
                'SELECT 1 FROM schema_migrations WHERE version = :version LIMIT 1'
            );
            $current->execute(['version' => self::CURRENT_MIGRATION_VERSION]);
            if ($current->fetchColumn() !== false) {
                return;
            }

            // Las bases ya actualizadas sólo necesitan la última migración.
            // todas las reparaciones históricas en cada visita pública.
            $previous = $pdo->prepare(
                'SELECT 1 FROM schema_migrations WHERE version = 28 LIMIT 1'
            );
            $previous->execute();
            if ($previous->fetchColumn() !== false) {
                self::migrateTutorials($pdo);
                self::migrateOrderDiscounts($pdo);
                $pdo->prepare('INSERT OR IGNORE INTO schema_migrations(version) VALUES(:version)')
                    ->execute(['version' => self::CURRENT_MIGRATION_VERSION]);
                return;
            }
        } catch (PDOException) {
            // Es una base nueva: se crea el esquema completo a continuación.
        }

        $schema = file_get_contents($schemaPath);
        if ($schema === false) {
            throw new \RuntimeException('No se pudo leer el esquema de base de datos.');
        }

        $pdo->exec($schema);
        self::migrateReceiptPrevalidation($pdo);
        self::migrateCategoryTree($pdo);
        self::migrateOrderArchive($pdo);
        self::migrateVariantImages($pdo);
        self::migrateProductDeletion($pdo);
        self::migrateLegacyMailAddress($pdo);
        self::clearLegacyOrders($pdo);
        self::disableMailQueues($pdo);
        self::migrateToSingleAvailableStock($pdo);
        self::migrateOptionalProductFields($pdo);
        self::seedCatalog(
            $pdo,
            dirname($schemaPath) . '/catalog_seed.sql'
        );
        self::migrateCatalogImagesToLocal($pdo);
        self::seedCategoryTree($pdo);
        self::importPilotAdhesivePaper($pdo);
        self::purgeDeletedCatalogAndRefreshPilot($pdo);
        self::migrateProductVisibilityState($pdo);
        self::prepareRealSalesNumbering($pdo);
        self::normalizeHistoricalCustomerNames($pdo);
        self::migrateCatalogDescriptions(
            $pdo,
            dirname($schemaPath) . '/catalog_descriptions.json'
        );
        self::migrateDeliverySlots($pdo);
        self::migrateDeliverySlotAmounts($pdo);
        self::migrateDeliveryReopen($pdo);
        self::uppercaseCustomerNames($pdo);
        self::migrateInvitations($pdo);
        self::migrateOrderNotificationSeen($pdo);
        self::migrateTutorials($pdo);
        $pdo->prepare('INSERT OR IGNORE INTO schema_migrations(version) VALUES(:version)')
            ->execute(['version' => self::CURRENT_MIGRATION_VERSION]);
    }

    private static function migrateOrderDiscounts(PDO $pdo): void
    {
        $version = 30;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;
        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $names = array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
            if (!in_array('discount_type', $names, true)) $pdo->exec("ALTER TABLE orders ADD COLUMN discount_type TEXT NOT NULL DEFAULT ''");
            if (!in_array('discount_percent', $names, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN discount_percent INTEGER NOT NULL DEFAULT 0');
            if (!in_array('discount_cents', $names, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN discount_cents INTEGER NOT NULL DEFAULT 0');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Separa el contenido editorial que antes estaba cargado como productos. */
    private static function migrateTutorials(PDO $pdo): void
    {
        $version = 29;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $pdo->exec("CREATE TABLE IF NOT EXISTS tutorials (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL COLLATE NOCASE,
                content TEXT NOT NULL DEFAULT '',
                image_path TEXT,
                active INTEGER NOT NULL DEFAULT 1 CHECK (active IN (0, 1)),
                sort_order INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_tutorials_public ON tutorials(active, sort_order, title)');
            $pdo->exec("INSERT INTO tutorials(title, content, image_path, active, sort_order)
                SELECT p.name, p.description, p.image_path, p.active, p.sort_order
                FROM products p JOIN categories c ON c.id = p.category_id
                WHERE c.slug = 'aprende' COLLATE NOCASE AND p.deleted_at IS NULL
                  AND NOT EXISTS (SELECT 1 FROM tutorials t WHERE t.title = p.name COLLATE NOCASE)");
            $pdo->exec("UPDATE products SET deleted_at = CURRENT_TIMESTAMP, active = 0, updated_at = CURRENT_TIMESTAMP
                WHERE deleted_at IS NULL AND category_id IN (SELECT id FROM categories WHERE slug = 'aprende' COLLATE NOCASE)");
            $pdo->exec("UPDATE categories SET active = 0, updated_at = CURRENT_TIMESTAMP WHERE slug = 'aprende' COLLATE NOCASE");
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Solicitudes de invitación a Codex capturadas desde la página pública. */
    private static function migrateInvitations(PDO $pdo): void
    {
        $version = 27;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $pdo->exec("CREATE TABLE IF NOT EXISTS invitation_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL COLLATE NOCASE UNIQUE,
                status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'sent')),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sent_at TEXT,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_invitation_requests_status_created ON invitation_requests(status, created_at DESC)');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Guarda por usuario hasta qué momento revisó el listado de ventas. */
    private static function migrateOrderNotificationSeen(PDO $pdo): void
    {
        $version = 28;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $columns = array_column($pdo->query('PRAGMA table_info(users)')->fetchAll(), 'name');
            if (!in_array('orders_seen_at', $columns, true)) {
                $pdo->exec('ALTER TABLE users ADD COLUMN orders_seen_at TEXT');
            }
            // Las ventas anteriores no deben aparecer como una alerta nueva.
            $pdo->exec('UPDATE users SET orders_seen_at = CURRENT_TIMESTAMP WHERE orders_seen_at IS NULL');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    private static function migrateDeliverySlots(PDO $pdo): void
    {
        $version = 22;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;
        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $columns = $pdo->query('PRAGMA table_info(orders)')->fetchAll();
            $names = array_column($columns, 'name');
            if (!in_array('delivery_slot_number', $names, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN delivery_slot_number INTEGER');
            if (!in_array('delivery_copied_at', $names, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN delivery_copied_at TEXT');
            $pdo->exec("CREATE TABLE IF NOT EXISTS delivery_slots (slot_number INTEGER PRIMARY KEY CHECK (slot_number BETWEEN 1 AND 100), location TEXT NOT NULL DEFAULT '', order_numbers TEXT NOT NULL DEFAULT '', customer_name TEXT NOT NULL DEFAULT '', transfers TEXT NOT NULL DEFAULT '', revision INTEGER NOT NULL DEFAULT 1, updated_by INTEGER REFERENCES users(id) ON DELETE SET NULL, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)");
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    private static function migrateDeliverySlotAmounts(PDO $pdo): void
    {
        $version = 23;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;
        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $names = array_column($pdo->query('PRAGMA table_info(delivery_slots)')->fetchAll(), 'name');
            if (!in_array('cash_due', $names, true)) $pdo->exec("ALTER TABLE delivery_slots ADD COLUMN cash_due TEXT NOT NULL DEFAULT ''");
            if (!in_array('order_total_cents', $names, true)) $pdo->exec('ALTER TABLE delivery_slots ADD COLUMN order_total_cents INTEGER NOT NULL DEFAULT 0');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    private static function migrateDeliveryReopen(PDO $pdo): void
    {
        $version = 24;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;
        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $names = array_column($pdo->query('PRAGMA table_info(orders)')->fetchAll(), 'name');
            if (!in_array('delivery_reopened_at', $names, true)) $pdo->exec('ALTER TABLE orders ADD COLUMN delivery_reopened_at TEXT');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /**
     * Mantiene una lectura uniforme en las listas operativas: los nombres de
     * compradores siempre se conservan en MAYÚSCULAS, también los históricos.
     */
    private static function uppercaseCustomerNames(PDO $pdo): void
    {
        $version = 25;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $upper = static function (string $value): string {
                $spaced = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
                return function_exists('mb_strtoupper')
                    ? mb_strtoupper($spaced, 'UTF-8')
                    : strtoupper($spaced);
            };
            $orders = $pdo->query("SELECT id, customer_name FROM orders WHERE trim(customer_name) <> ''")->fetchAll();
            $updateOrder = $pdo->prepare('UPDATE orders SET customer_name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            foreach ($orders as $order) {
                $name = $upper((string) $order['customer_name']);
                if ($name !== '' && $name !== (string) $order['customer_name']) {
                    $updateOrder->execute(['name' => $name, 'id' => (int) $order['id']]);
                }
            }
            $slots = $pdo->query("SELECT slot_number, customer_name FROM delivery_slots WHERE trim(customer_name) <> ''")->fetchAll();
            $updateSlot = $pdo->prepare('UPDATE delivery_slots SET customer_name = :name, revision = revision + 1, updated_at = CURRENT_TIMESTAMP WHERE slot_number = :slot');
            foreach ($slots as $slot) {
                $name = $upper((string) $slot['customer_name']);
                if ($name !== '' && $name !== (string) $slot['customer_name']) {
                    $updateSlot->execute(['name' => $name, 'slot' => (int) $slot['slot_number']]);
                }
            }
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /**
     * Retira una sola vez las ventas de prueba y deja preparada la numeracion
     * elegida para el inicio de la operatoria real. No modifica el stock fisico.
     */
    private static function prepareRealSalesNumbering(PDO $pdo): void
    {
        $version = 18;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $pdo->exec('UPDATE stock_movements SET order_id = NULL WHERE order_id IS NOT NULL');
            $pdo->exec('UPDATE cash_movements SET order_id = NULL WHERE order_id IS NOT NULL');
            $pdo->exec('DELETE FROM orders');
            $pdo->exec('UPDATE product_variants SET stock_reserved = 0, updated_at = CURRENT_TIMESTAMP WHERE stock_reserved <> 0');
            $pdo->prepare(
                'INSERT INTO settings(key, value, updated_at) VALUES(:key, :value, CURRENT_TIMESTAMP)
                 ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP'
            )->execute([
                'key' => 'order_number_floor',
                'value' => '97759',
            ]);
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')
                ->execute(['version' => $version]);
        });
    }

    /** Normaliza una sola vez los nombres ya registrados, sin tocar ventas ni stock. */
    private static function normalizeHistoricalCustomerNames(PDO $pdo): void
    {
        $version = 19;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, static function (PDO $pdo) use ($version): void {
            $rows = $pdo->query("SELECT id, customer_name FROM orders WHERE trim(customer_name) <> ''")->fetchAll();
            $update = $pdo->prepare('UPDATE orders SET customer_name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            foreach ($rows as $row) {
                $original = (string) $row['customer_name'];
                $spaced = preg_replace('/\\s+/u', ' ', trim($original)) ?? '';
                $normalized = function_exists('mb_convert_case') && function_exists('mb_strtolower')
                    ? mb_convert_case(mb_strtolower($spaced, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
                    : ucwords(strtolower($spaced));
                if ($normalized !== '' && $normalized !== $original) {
                    $update->execute(['name' => $normalized, 'id' => (int) $row['id']]);
                }
            }
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')
                ->execute(['version' => $version]);
        });
    }

    /**
     * Restaura las descripciones completas del catálogo anterior.
     *
     * La exportación original contiene HTML propio de Tienda Nube. El archivo
     * de apoyo conserva solamente su contenido editorial como texto limpio con
     * párrafos y viñetas, para que se vea bien tanto en la tienda como en el
     * editor del administrador.
     */
    private static function migrateCatalogDescriptions(PDO $pdo, string $path): void
    {
        $version = 21;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }
        if (!is_file($path)) {
            throw new \RuntimeException('No se encontró el archivo de descripciones del catálogo.');
        }

        $contents = file_get_contents($path);
        $descriptions = is_string($contents)
            ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR)
            : null;
        if (!is_array($descriptions)) {
            throw new \RuntimeException('El archivo de descripciones del catálogo no es válido.');
        }

        $byName = $descriptions['by_name'] ?? $descriptions;
        $bySku = $descriptions['by_sku'] ?? [];
        if (!is_array($byName) || !is_array($bySku)) {
            throw new \RuntimeException('La estructura de descripciones del catálogo no es válida.');
        }

        self::immediate($pdo, static function (PDO $pdo) use ($byName, $bySku, $version): void {
            $update = $pdo->prepare(
                'UPDATE products
                 SET description = :description, updated_at = CURRENT_TIMESTAMP
                 WHERE name = :name COLLATE NOCASE
                   AND deleted_at IS NULL'
            );
            foreach ($byName as $name => $description) {
                if (!is_string($name) || !is_string($description) || trim($name) === '') {
                    continue;
                }
                $update->execute([
                    'name' => trim($name),
                    'description' => trim($description),
                ]);
            }
            // Algunas denominaciones cambiaron entre exportaciones. El SKU es
            // estable y permite restaurar igualmente la descripción correcta.
            $updateBySku = $pdo->prepare(
                'UPDATE products
                 SET description = :description, updated_at = CURRENT_TIMESTAMP
                 WHERE deleted_at IS NULL
                   AND EXISTS (
                       SELECT 1 FROM product_variants pv
                       WHERE pv.product_id = products.id
                         AND upper(pv.sku) = :sku
                   )'
            );
            foreach ($bySku as $sku => $description) {
                if (!is_string($sku) || !is_string($description) || trim($sku) === '') {
                    continue;
                }
                $updateBySku->execute([
                    'sku' => strtoupper(trim($sku)),
                    'description' => trim($description),
                ]);
            }
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')
                ->execute(['version' => $version]);
        });
    }

    /** Importación piloto controlada desde la tienda anterior. */
    private static function importPilotAdhesivePaper(PDO $pdo): void
    {
        $version = 15;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $title = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS';
            $description = 'Papel fotográfico autoadhesivo brillante (Glossy) tamaño A4 de 115g ArtJet. Secado instantáneo y adhesivo de alta calidad. Ideal para etiquetas, stickers y personalización.';
            $image = '/v1/assets/catalog/papel-fotografico-adhesivo-a4-115g-artjet-100-hojas.webp';
            $sku = '115A4100';

            $category = $pdo->prepare("SELECT id FROM categories WHERE name = 'Fotográfico Brillante' COLLATE NOCASE LIMIT 1");
            $category->execute();
            $categoryId = (int) $category->fetchColumn();
            if ($categoryId < 1) {
                throw new \RuntimeException('No se encontró la categoría Fotográfico Brillante para la importación piloto.');
            }

            $find = $pdo->prepare('SELECT id FROM products WHERE name = :name COLLATE NOCASE LIMIT 1');
            $find->execute(['name' => $title]);
            $productId = (int) $find->fetchColumn();
            if ($productId < 1) {
                $insert = $pdo->prepare('INSERT INTO products(category_id, name, description, image_path, active) VALUES(:category_id, :name, :description, :image_path, 1)');
                $insert->execute(['category_id' => $categoryId, 'name' => $title, 'description' => $description, 'image_path' => $image]);
                $productId = (int) $pdo->lastInsertId();
            } else {
                $update = $pdo->prepare('UPDATE products SET category_id = :category_id, description = :description, image_path = :image_path, active = 1, deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute(['category_id' => $categoryId, 'description' => $description, 'image_path' => $image, 'id' => $productId]);
            }

            $variant = $pdo->prepare('SELECT id FROM product_variants WHERE product_id = :product_id ORDER BY id LIMIT 1');
            $variant->execute(['product_id' => $productId]);
            $variantId = (int) $variant->fetchColumn();
            if ($variantId < 1) {
                $insertVariant = $pdo->prepare('INSERT INTO product_variants(product_id, name, sku, image_path, price_cents, price_specified, stock_on_hand, stock_specified, stock_reserved, min_stock, active) VALUES(:product_id, :name, :sku, :image_path, 2120000, 1, 23, 1, 0, 0, 1)');
                $insertVariant->execute(['product_id' => $productId, 'name' => 'Única', 'sku' => $sku, 'image_path' => $image]);
            } else {
                $updateVariant = $pdo->prepare('UPDATE product_variants SET name = :name, sku = :sku, barcode = NULL, image_path = :image_path, price_cents = 2120000, price_specified = 1, stock_on_hand = 23, stock_specified = 1, stock_reserved = 0, active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $updateVariant->execute(['name' => 'Única', 'sku' => $sku, 'image_path' => $image, 'id' => $variantId]);
            }

            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Borra definitivamente restos de catálogo sin historial de ventas y actualiza la importación piloto. */
    private static function purgeDeletedCatalogAndRefreshPilot(PDO $pdo): void
    {
        $version = 16;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $eligible = 'SELECT p.id FROM products p WHERE p.deleted_at IS NOT NULL AND NOT EXISTS (SELECT 1 FROM product_variants pv JOIN order_items oi ON oi.variant_id = pv.id WHERE pv.product_id = p.id)';
            $pdo->exec("DELETE FROM stock_movements WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id IN ($eligible))");
            $pdo->exec("DELETE FROM products WHERE id IN ($eligible)");

            $title = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS';
            $description = 'Papel fotográfico autoadhesivo brillante (Glossy) tamaño A4 de 115g ArtJet. Secado instantáneo y adhesivo de alta calidad. Ideal para etiquetas, stickers y personalización.';
            $image = '/v1/assets/catalog/papel-fotografico-adhesivo-a4-115g-artjet-100-hojas.webp';
            $category = $pdo->prepare("SELECT id FROM categories WHERE name = 'Fotográfico Brillante' COLLATE NOCASE LIMIT 1");
            $category->execute();
            $categoryId = (int) $category->fetchColumn();
            if ($categoryId < 1) throw new \RuntimeException('No se encontró la categoría Fotográfico Brillante.');

            $find = $pdo->prepare('SELECT id FROM products WHERE name = :name COLLATE NOCASE LIMIT 1');
            $find->execute(['name' => $title]);
            $productId = (int) $find->fetchColumn();
            if ($productId < 1) {
                $insert = $pdo->prepare('INSERT INTO products(category_id, name, description, image_path, active) VALUES(:category_id, :name, :description, :image_path, 1)');
                $insert->execute(['category_id' => $categoryId, 'name' => $title, 'description' => $description, 'image_path' => $image]);
                $productId = (int) $pdo->lastInsertId();
            } else {
                $update = $pdo->prepare('UPDATE products SET category_id = :category_id, description = :description, image_path = :image_path, active = 1, deleted_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $update->execute(['category_id' => $categoryId, 'description' => $description, 'image_path' => $image, 'id' => $productId]);
            }

            $variant = $pdo->prepare('SELECT id FROM product_variants WHERE product_id = :product_id ORDER BY id LIMIT 1');
            $variant->execute(['product_id' => $productId]);
            $variantId = (int) $variant->fetchColumn();
            if ($variantId < 1) {
                $insertVariant = $pdo->prepare('INSERT INTO product_variants(product_id, name, sku, barcode, image_path, price_cents, price_specified, stock_on_hand, stock_specified, stock_reserved, min_stock, active) VALUES(:product_id, :name, :sku, :barcode, :image_path, 2120000, 1, 29, 1, 0, 0, 1)');
                $insertVariant->execute(['product_id' => $productId, 'name' => 'Única', 'sku' => '115A4100', 'barcode' => '721450695454', 'image_path' => $image]);
            } else {
                $updateVariant = $pdo->prepare('UPDATE product_variants SET name = :name, sku = :sku, barcode = :barcode, image_path = :image_path, price_cents = 2120000, price_specified = 1, stock_on_hand = 29, stock_specified = 1, stock_reserved = 0, active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                $updateVariant->execute(['name' => 'Única', 'sku' => '115A4100', 'barcode' => '721450695454', 'image_path' => $image, 'id' => $variantId]);
            }
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Repara productos visibles cuyas variantes quedaron ocultas por una versión anterior. */
    private static function migrateProductVisibilityState(PDO $pdo): void
    {
        $version = 17;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $pdo->exec(
                'UPDATE product_variants
                 SET active = 1, updated_at = CURRENT_TIMESTAMP
                 WHERE product_id IN (
                    SELECT p.id
                    FROM products p
                    WHERE p.active = 1
                      AND p.deleted_at IS NULL
                      AND NOT EXISTS (
                        SELECT 1 FROM product_variants visible_variant
                        WHERE visible_variant.product_id = p.id
                          AND visible_variant.active = 1
                      )
                 )'
            );
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Permite dejar SKU, precio y stock visualmente vacíos y libera identificadores de productos borrados. */
    private static function migrateOptionalProductFields(PDO $pdo): void
    {
        $version = 14;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) return;

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $columns = [];
            foreach ($pdo->query('PRAGMA table_info(product_variants)')->fetchAll() as $row) {
                $columns[(string) $row['name']] = true;
            }
            if (!isset($columns['price_specified'])) {
                $pdo->exec('ALTER TABLE product_variants ADD COLUMN price_specified INTEGER NOT NULL DEFAULT 1 CHECK (price_specified IN (0, 1))');
            }
            if (!isset($columns['stock_specified'])) {
                $pdo->exec('ALTER TABLE product_variants ADD COLUMN stock_specified INTEGER NOT NULL DEFAULT 1 CHECK (stock_specified IN (0, 1))');
            }
            // Los productos borrados se conservan sólo para la integridad histórica,
            // pero no deben seguir bloqueando identificadores reutilizables.
            $pdo->exec("UPDATE product_variants SET sku = '__BORRADO__' || id, barcode = NULL, updated_at = CURRENT_TIMESTAMP WHERE product_id IN (SELECT id FROM products WHERE deleted_at IS NOT NULL)");
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /**
     * Unifica el inventario: stock_on_hand pasa a ser el único stock disponible.
     * Las reservas anteriores dejan de modificar el número cargado manualmente.
     */
    private static function migrateToSingleAvailableStock(PDO $pdo): void
    {
        $version = 13;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }
        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $pdo->exec('UPDATE product_variants SET stock_reserved = 0, updated_at = CURRENT_TIMESTAMP');
            $pdo->exec('UPDATE orders SET stock_reserved_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE stock_reserved_at IS NOT NULL');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Elimina mensajes pendientes: la tienda se comunica con clientes por WhatsApp. */
    private static function disableMailQueues(PDO $pdo): void
    {
        $version = 10;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }
        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $pdo->exec('DELETE FROM mail_queue');
            $pdo->exec('DELETE FROM customer_notification_queue');
            $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')->execute(['version' => $version]);
        });
    }

    /** Vacía una sola vez las ventas de prueba antes de iniciar la operatoria real. */
    private static function clearLegacyOrders(PDO $pdo): void
    {
        $version = 9;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
            $pdo->exec('DELETE FROM orders');
            $pdo->exec('DELETE FROM payment_proofs');
            $pdo->exec('DELETE FROM order_events');
            $pdo->exec('UPDATE stock_movements SET order_id = NULL');
            $pdo->exec('UPDATE product_variants SET stock_reserved = 0, updated_at = CURRENT_TIMESTAMP');
            $insert = $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)');
            $insert->execute(['version' => $version]);
        });
    }

    /** Reemplaza las fotos heredadas por copias servidas desde este hosting. */
    private static function migrateCatalogImagesToLocal(PDO $pdo): void
    {
        $rows = $pdo->query("SELECT id, image_path FROM products WHERE image_path LIKE 'http%'")->fetchAll();
        $update = $pdo->prepare('UPDATE products SET image_path = :image_path, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        foreach ($rows as $row) {
            $path = (string) parse_url((string) $row['image_path'], PHP_URL_PATH);
            $filename = basename($path);
            if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
                continue;
            }
            $update->execute([
                'id' => (int) $row['id'],
                'image_path' => '/v1/assets/catalog/' . $filename,
            ]);
        }

        $variantRows = $pdo->query("SELECT id, image_path FROM product_variants WHERE image_path LIKE 'http%'")->fetchAll();
        $variantUpdate = $pdo->prepare('UPDATE product_variants SET image_path = :image_path WHERE id = :id');
        foreach ($variantRows as $row) {
            $path = (string) parse_url((string) $row['image_path'], PHP_URL_PATH);
            $filename = basename($path);
            if ($filename === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $filename)) {
                continue;
            }
            $variantUpdate->execute([
                'id' => (int) $row['id'],
                'image_path' => '/v1/assets/catalog/' . $filename,
            ]);
        }
    }

    /** Corrige la casilla provisoria usada antes de crear ventas@artjet.com.ar. */
    private static function migrateLegacyMailAddress(PDO $pdo): void
    {
        $update = $pdo->prepare(
            "UPDATE settings
             SET value = 'ventas@artjet.com.ar', updated_at = CURRENT_TIMESTAMP
             WHERE key IN ('sales_email', 'mail_from', 'mail_reply_to', 'mail_smtp_username')
               AND value = :legacy"
        );
        $update->execute(['legacy' => 'ventas@laboratorio-digital.com.ar']);
    }

    /** Replica la jerarquÃ­a de categorÃ­as exportada desde catálogo anterior. */
    private static function seedCategoryTree(PDO $pdo): void
    {
        $version = 11;
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        self::immediate($pdo, function (PDO $pdo) use ($version): void {
        $tree = [
            'SUBLIMABLES' => ['Madera Cristal', 'PolÃ­mero', 'CerÃ¡mica', 'CartÃ³n', 'Botellas y Termos', 'Bolsas Friselina', 'Accesorios para sublimar'],
            'REMERAS' => ['de AlgodÃ³n', 'Remeras sublimables'],
            'PAPELES' => ['Papeles AdorÃ­', 'FotogrÃ¡fico Brillante', 'Holofan', 'FotogrÃ¡fico Mate - Matelina', 'Filmilo', 'SublimaciÃ³n', 'Papel Obra', 'Winky, TermocontraÃ­ble', 'Tatufan, Tatuajes Temporales', 'Duralite, Transfer', 'MagnÃ©tico'],
            'TINTAS' => ['FotogrÃ¡fica', 'SublimaciÃ³n'],
            'GORRAS' => ['Trucker Adulto', 'Trucker Infantil', 'Gabardina'],
        ];
        // Los primeros registros se importaron con la codificación dañada. Se
        // reemplaza la estructura por los nombres UTF-8 correctos y se corrigen
        // las categorías ya guardadas antes de volver a sembrar el árbol.
        $tree = [
            'SUBLIMABLES' => ['Madera Cristal', 'Polímero', 'Cerámica', 'Cartón', 'Botellas y Termos', 'Bolsas Friselina', 'Accesorios para sublimar'],
            'REMERAS' => ['de Algodón', 'Remeras sublimables'],
            'PAPELES' => ['Papeles Adorí', 'Fotográfico Brillante', 'Holofan', 'Fotográfico Mate - Matelina', 'Filmilo', 'Sublimación', 'Papel Obra', 'Winky, Termocontraíble', 'Tatufan, Tatuajes Temporales', 'Duralite, Transfer', 'Magnético'],
            'TINTAS' => ['Fotográfica', 'Sublimación'],
            'GORRAS' => ['Trucker Adulto', 'Trucker Infantil', 'Gabardina'],
        ];
        $repairs = [
            'PolÃƒÂ­mero' => 'Polímero', 'CerÃƒÂ¡mica' => 'Cerámica', 'CartÃƒÂ³n' => 'Cartón',
            'de AlgodÃƒÂ³n' => 'de Algodón', 'Papeles AdorÃƒÂ­' => 'Papeles Adorí',
            'FotogrÃƒÂ¡fico Brillante' => 'Fotográfico Brillante',
            'FotogrÃƒÂ¡fico Mate - Matelina' => 'Fotográfico Mate - Matelina',
            'SublimaciÃƒÂ³n' => 'Sublimación', 'Winky, TermocontraÃƒÂ­ble' => 'Winky, Termocontraíble',
            'MagnÃƒÂ©tico' => 'Magnético', 'FotogrÃƒÂ¡fica' => 'Fotográfica',
        ];
        $rename = $pdo->prepare('UPDATE categories SET name = :new_name, slug = :slug, updated_at = CURRENT_TIMESTAMP WHERE name = :old_name');
        foreach ($repairs as $oldName => $newName) {
            $rename->execute(['old_name' => $oldName, 'new_name' => $newName, 'slug' => self::categorySlug($newName)]);
        }
        $parentQuery = $pdo->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $insert = $pdo->prepare(
            'INSERT INTO categories(name, slug, parent_id, sort_order, active)
             VALUES(:name, :slug, :parent_id, :sort_order, 1)
             ON CONFLICT(name) DO UPDATE SET parent_id = excluded.parent_id, sort_order = excluded.sort_order, active = 1, updated_at = CURRENT_TIMESTAMP'
        );
        foreach ($tree as $parentName => $children) {
            $parentQuery->execute(['name' => $parentName]);
            $parentId = (int) $parentQuery->fetchColumn();
            if ($parentId < 1) {
                continue;
            }
            foreach ($children as $position => $childName) {
                $insert->execute([
                    'name' => $childName,
                    'slug' => self::categorySlug($parentName . '-' . $childName),
                    'parent_id' => $parentId,
                    'sort_order' => $position,
                ]);
            }
        }
        $pdo->prepare('INSERT INTO schema_migrations(version) VALUES(:version)')
            ->execute(['version' => $version]);
        });
    }

    private static function categorySlug(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
        return trim($value, '-');
    }

    private static function migrateCategoryTree(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(categories)')->fetchAll() as $row) $columns[(string) $row['name']] = true;
        if (!isset($columns['parent_id'])) $pdo->exec('ALTER TABLE categories ADD COLUMN parent_id INTEGER REFERENCES categories(id) ON DELETE SET NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_categories_tree ON categories(parent_id, sort_order, name)');
        $pdo->exec('INSERT OR IGNORE INTO schema_migrations(version) VALUES(4)');
    }

    /** Agrega el archivado de ventas a bases creadas antes de esta funcionalidad. */
    private static function migrateOrderArchive(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(orders)')->fetchAll() as $row) {
            $columns[(string) $row['name']] = true;
        }
        if (!isset($columns['archived_at'])) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN archived_at TEXT');
        }
        if (!isset($columns['archived_by'])) {
            $pdo->exec(
                'ALTER TABLE orders ADD COLUMN archived_by INTEGER REFERENCES users(id) ON DELETE SET NULL'
            );
        }
        $pdo->exec(
            'CREATE INDEX IF NOT EXISTS idx_orders_archived_created ON orders(archived_at, created_at)'
        );
        $pdo->exec('INSERT OR IGNORE INTO schema_migrations(version) VALUES(6)');
    }

    /** Permite que cada variante tenga su propia foto. */
    private static function migrateVariantImages(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(product_variants)')->fetchAll() as $row) {
            $columns[(string) $row['name']] = true;
        }
        if (!isset($columns['image_path'])) {
            $pdo->exec('ALTER TABLE product_variants ADD COLUMN image_path TEXT');
        }
        $pdo->exec('INSERT OR IGNORE INTO schema_migrations(version) VALUES(8)');
    }

    /** Permite retirar productos del catálogo sin perder el historial archivado. */
    private static function migrateProductDeletion(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(products)')->fetchAll() as $row) {
            $columns[(string) $row['name']] = true;
        }
        if (!isset($columns['deleted_at'])) {
            $pdo->exec('ALTER TABLE products ADD COLUMN deleted_at TEXT');
        }
        $pdo->exec('INSERT OR IGNORE INTO schema_migrations(version) VALUES(12)');
    }

    /** Agrega campos a bases ya creadas antes de la prevalidación de pagos. */
    private static function migrateReceiptPrevalidation(PDO $pdo): void
    {
        $columns = [];
        foreach ($pdo->query('PRAGMA table_info(payment_proofs)')->fetchAll() as $row) {
            $columns[(string) $row['name']] = true;
        }
        $definitions = [
            'ai_status' => "TEXT NOT NULL DEFAULT 'not_run'",
            'ai_risk_level' => 'TEXT',
            'ai_summary' => 'TEXT',
            'ai_result_json' => 'TEXT',
            'ai_model' => 'TEXT',
            'ai_checked_at' => 'TEXT',
        ];
        foreach ($definitions as $name => $definition) {
            if (!isset($columns[$name])) {
                $pdo->exec(
                    'ALTER TABLE payment_proofs ADD COLUMN '
                    . $name . ' ' . $definition
                );
            }
        }

        $insertSetting = $pdo->prepare(
            'INSERT OR IGNORE INTO settings(key, value) VALUES(:key, :value)'
        );
        $insertSetting->execute([
            'key' => 'business_hours',
            'value' => 'Lunes a viernes de 9 a 17 h',
        ]);
        $pdo->exec(
            'INSERT OR IGNORE INTO schema_migrations(version) VALUES(3)'
        );
    }

    private static function seedCatalog(PDO $pdo, string $seedPath): void
    {
        $version = 2;
        $query = $pdo->prepare(
            'SELECT 1 FROM schema_migrations WHERE version = :version'
        );
        $query->execute(['version' => $version]);
        if ($query->fetchColumn() !== false) {
            return;
        }

        $seed = file_get_contents($seedPath);
        if ($seed === false) {
            throw new \RuntimeException('No se pudo leer el catálogo inicial.');
        }

        $pdo->exec('BEGIN IMMEDIATE');
        try {
            $pdo->exec($seed);
            $insert = $pdo->prepare(
                'INSERT INTO schema_migrations(version) VALUES(:version)'
            );
            $insert->execute(['version' => $version]);
            $pdo->exec('COMMIT');
        } catch (Throwable $exception) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable) {
                // La transacción pudo fallar antes de iniciarse.
            }
            throw $exception;
        }
    }

    /**
     * Ejecuta una operación con bloqueo de escritura inmediato.
     *
     * SQLite no permite iniciar BEGIN IMMEDIATE mediante PDO::beginTransaction().
     * Esta variante evita que dos cajas o pedidos reserven la misma unidad.
     *
     * @template T
     * @param callable(PDO): T $operation
     * @return T
     */
    public static function immediate(PDO $pdo, callable $operation): mixed
    {
        $pdo->exec('BEGIN IMMEDIATE');

        try {
            $result = $operation($pdo);
            $pdo->exec('COMMIT');

            return $result;
        } catch (Throwable $exception) {
            try {
                $pdo->exec('ROLLBACK');
            } catch (Throwable) {
                // La operación pudo fallar antes de abrir la transacción.
            }

            throw $exception;
        }
    }
}
