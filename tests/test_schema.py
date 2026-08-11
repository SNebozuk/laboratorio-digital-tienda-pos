import sqlite3
import unittest
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SCHEMA = (ROOT / "database" / "schema.sql").read_text(encoding="utf-8")


class SchemaContractTest(unittest.TestCase):
    def setUp(self) -> None:
        self.db = sqlite3.connect(":memory:")
        self.db.row_factory = sqlite3.Row
        self.db.executescript(SCHEMA)
        self.db.execute(
            """
            INSERT INTO users(name, email, password_hash, role)
            VALUES('Administración', 'admin@example.test', 'hash', 'admin')
            """
        )
        self.user_id = self.db.execute(
            "SELECT id FROM users WHERE email = 'admin@example.test'"
        ).fetchone()["id"]
        self.db.execute(
            "INSERT INTO categories(name, slug) VALUES('Remeras', 'remeras')"
        )
        category_id = self.db.execute(
            "SELECT id FROM categories WHERE slug = 'remeras'"
        ).fetchone()["id"]
        self.db.execute(
            """
            INSERT INTO products(category_id, name, description)
            VALUES(?, 'REMERA VERDE', 'Producto de prueba')
            """,
            (category_id,),
        )
        self.product_id = self.db.execute(
            "SELECT id FROM products WHERE name = 'REMERA VERDE'"
        ).fetchone()["id"]
        self.db.execute(
            """
            INSERT INTO product_variants(
                product_id, name, sku, price_cents, stock_on_hand, stock_reserved
            ) VALUES(?, 'Talle 1', 'REM-VER-1', 1490000, 10, 2)
            """,
            (self.product_id,),
        )
        self.variant_id = self.db.execute(
            "SELECT id FROM product_variants WHERE sku = 'REM-VER-1'"
        ).fetchone()["id"]

    def tearDown(self) -> None:
        self.db.close()

    def test_available_stock_is_physical_minus_reserved(self) -> None:
        row = self.db.execute(
            """
            SELECT stock_on_hand - stock_reserved AS available
            FROM product_variants WHERE id = ?
            """,
            (self.variant_id,),
        ).fetchone()
        self.assertEqual(row["available"], 8)

    def test_size_guide_settings_are_available(self) -> None:
        settings = dict(
            self.db.execute(
                """
                SELECT key, value FROM settings
                WHERE key IN ('size_guide_intro', 'size_guide_json')
                """
            ).fetchall()
        )
        migration = self.db.execute(
            "SELECT 1 FROM schema_migrations WHERE version = 5"
        ).fetchone()

        self.assertIn("size_guide_intro", settings)
        self.assertEqual(settings["size_guide_json"], "[]")
        self.assertIsNotNone(migration)

    def test_delivered_sales_support_archiving(self) -> None:
        columns = {
            row["name"] for row in self.db.execute("PRAGMA table_info(orders)")
        }
        migration = self.db.execute(
            "SELECT 1 FROM schema_migrations WHERE version = 6"
        ).fetchone()

        self.assertIn("archived_at", columns)
        self.assertIn("archived_by", columns)
        self.assertIsNotNone(migration)

    def test_customer_cancellation_notifications_are_queued_for_future_channel(self) -> None:
        columns = {
            row["name"]
            for row in self.db.execute(
                "PRAGMA table_info(customer_notification_queue)"
            )
        }
        migration = self.db.execute(
            "SELECT 1 FROM schema_migrations WHERE version = 7"
        ).fetchone()

        self.assertIn("customer_phone", columns)
        self.assertIn("customer_email", columns)
        self.assertIn("payload_json", columns)
        self.assertIsNotNone(migration)

    def test_variants_can_store_an_independent_image(self) -> None:
        columns = {
            row["name"]
            for row in self.db.execute("PRAGMA table_info(product_variants)")
        }
        migration = self.db.execute(
            "SELECT 1 FROM schema_migrations WHERE version = 8"
        ).fetchone()

        self.assertIn("image_path", columns)
        self.db.execute(
            "UPDATE product_variants SET image_path = ? WHERE id = ?",
            ("/uploads/products/talle-1.webp", self.variant_id),
        )
        image_path = self.db.execute(
            "SELECT image_path FROM product_variants WHERE id = ?",
            (self.variant_id,),
        ).fetchone()["image_path"]
        self.assertEqual(image_path, "/uploads/products/talle-1.webp")
        self.assertIsNotNone(migration)

    def test_sku_is_unique_without_case_sensitivity(self) -> None:
        with self.assertRaises(sqlite3.IntegrityError):
            self.db.execute(
                """
                INSERT INTO product_variants(
                    product_id, name, sku, price_cents, stock_on_hand
                ) VALUES(?, 'Talle 2', 'rem-ver-1', 1490000, 4)
                """,
                (self.product_id,),
            )

    def test_reserved_stock_cannot_exceed_physical_stock(self) -> None:
        with self.assertRaises(sqlite3.IntegrityError):
            self.db.execute(
                """
                UPDATE product_variants
                SET stock_reserved = 11
                WHERE id = ?
                """,
                (self.variant_id,),
            )

    def test_web_order_does_not_reserve_until_payment_is_reported(self) -> None:
        self.db.execute(
            """
            INSERT INTO orders(
                public_number, channel, status, customer_name, customer_email,
                subtotal_cents, total_cents, payment_method, payment_deadline_at
            ) VALUES(
                'LD-WEB-TEST', 'web', 'pending_payment', 'Cliente',
                'cliente@example.test', 1490000, 1490000,
                'bank_transfer', datetime('now', '+2 hours')
            )
            """
        )
        order_id = self.db.execute(
            "SELECT id FROM orders WHERE public_number = 'LD-WEB-TEST'"
        ).fetchone()["id"]
        self.db.execute(
            """
            INSERT INTO order_items(
                order_id, variant_id, product_name, variant_name, sku,
                quantity, unit_price_cents, line_total_cents
            ) VALUES(?, ?, 'REMERA VERDE', 'Talle 1', 'REM-VER-1', 3, 1490000, 4470000)
            """,
            (order_id, self.variant_id),
        )

        before = self.db.execute(
            "SELECT stock_reserved FROM product_variants WHERE id = ?",
            (self.variant_id,),
        ).fetchone()["stock_reserved"]
        self.assertEqual(before, 2)

        cursor = self.db.execute(
            """
            UPDATE product_variants
            SET stock_reserved = stock_reserved + 3
            WHERE id = ? AND stock_on_hand - stock_reserved >= 3
            """,
            (self.variant_id,),
        )
        self.assertEqual(cursor.rowcount, 1)
        self.db.execute(
            """
            UPDATE orders
            SET status = 'payment_reported', stock_reserved_at = CURRENT_TIMESTAMP
            WHERE id = ?
            """,
            (order_id,),
        )

        after = self.db.execute(
            """
            SELECT
                stock_reserved,
                stock_on_hand - stock_reserved AS available
            FROM product_variants WHERE id = ?
            """,
            (self.variant_id,),
        ).fetchone()
        self.assertEqual(after["stock_reserved"], 5)
        self.assertEqual(after["available"], 5)

    def test_only_one_cash_session_can_be_open(self) -> None:
        self.db.execute(
            """
            INSERT INTO cash_sessions(opened_by, opening_cents)
            VALUES(?, 100000)
            """,
            (self.user_id,),
        )
        with self.assertRaises(sqlite3.IntegrityError):
            self.db.execute(
                """
                INSERT INTO cash_sessions(opened_by, opening_cents)
                VALUES(?, 200000)
                """,
                (self.user_id,),
            )

    def test_rejected_proof_can_rotate_link_and_keep_reservation(self) -> None:
        self.db.execute(
            """
            INSERT INTO orders(
                public_number, channel, status, customer_name, customer_email,
                subtotal_cents, total_cents, payment_method,
                upload_token_hash, stock_reserved_at
            ) VALUES(
                'LD-WEB-RETRY', 'web', 'payment_reported', 'Cliente',
                'cliente@example.test', 1490000, 1490000,
                'bank_transfer', 'hash-anterior', CURRENT_TIMESTAMP
            )
            """
        )
        order_id = self.db.execute(
            "SELECT id FROM orders WHERE public_number = 'LD-WEB-RETRY'"
        ).fetchone()["id"]
        self.db.execute(
            """
            INSERT INTO payment_proofs(
                order_id, storage_key, original_name,
                mime_type, size_bytes, sha256
            ) VALUES(
                ?, 'proofs/old.pdf', 'old.pdf',
                'application/pdf', 100, 'proof-hash'
            )
            """,
            (order_id,),
        )

        self.db.execute(
            """
            UPDATE payment_proofs
            SET status = 'rejected',
                reviewed_by = ?,
                reviewed_at = CURRENT_TIMESTAMP
            WHERE order_id = ?
            """,
            (self.user_id, order_id),
        )
        self.db.execute(
            """
            UPDATE orders
            SET status = 'rejected',
                rejection_deadline_at = datetime('now', '+2 hours'),
                upload_token_hash = 'hash-nuevo'
            WHERE id = ?
            """,
            (order_id,),
        )

        order = self.db.execute(
            """
            SELECT status, upload_token_hash, stock_reserved_at,
                   rejection_deadline_at
            FROM orders WHERE id = ?
            """,
            (order_id,),
        ).fetchone()
        proof = self.db.execute(
            "SELECT status FROM payment_proofs WHERE order_id = ?",
            (order_id,),
        ).fetchone()

        self.assertEqual(order["status"], "rejected")
        self.assertEqual(order["upload_token_hash"], "hash-nuevo")
        self.assertIsNotNone(order["stock_reserved_at"])
        self.assertIsNotNone(order["rejection_deadline_at"])
        self.assertEqual(proof["status"], "rejected")

    def test_editing_reserved_order_adjusts_only_the_quantity_delta(self) -> None:
        self.db.execute(
            """
            INSERT INTO orders(
                public_number, channel, status, customer_name, customer_email,
                subtotal_cents, total_cents, payment_method,
                stock_reserved_at
            ) VALUES(
                'LD-WEB-EDIT', 'web', 'payment_reported', 'Cliente',
                'cliente@example.test', 2980000, 2980000,
                'bank_transfer', CURRENT_TIMESTAMP
            )
            """
        )
        order_id = self.db.execute(
            "SELECT id FROM orders WHERE public_number = 'LD-WEB-EDIT'"
        ).fetchone()["id"]
        self.db.execute(
            """
            INSERT INTO order_items(
                order_id, variant_id, product_name, variant_name, sku,
                quantity, unit_price_cents, line_total_cents
            ) VALUES(
                ?, ?, 'REMERA VERDE', 'Talle 1', 'REM-VER-1',
                2, 1490000, 2980000
            )
            """,
            (order_id, self.variant_id),
        )

        previous_quantity = 2
        next_quantity = 4
        delta = next_quantity - previous_quantity
        cursor = self.db.execute(
            """
            UPDATE product_variants
            SET stock_reserved = stock_reserved + ?
            WHERE id = ?
              AND stock_on_hand - stock_reserved >= ?
            """,
            (delta, self.variant_id, delta),
        )
        self.assertEqual(cursor.rowcount, 1)
        self.db.execute(
            """
            UPDATE order_items
            SET quantity = ?, line_total_cents = unit_price_cents * ?
            WHERE order_id = ? AND variant_id = ?
            """,
            (next_quantity, next_quantity, order_id, self.variant_id),
        )

        variant = self.db.execute(
            """
            SELECT stock_reserved, stock_on_hand - stock_reserved AS available
            FROM product_variants WHERE id = ?
            """,
            (self.variant_id,),
        ).fetchone()
        item = self.db.execute(
            """
            SELECT quantity FROM order_items
            WHERE order_id = ? AND variant_id = ?
            """,
            (order_id, self.variant_id),
        ).fetchone()
        self.assertEqual(variant["stock_reserved"], 4)
        self.assertEqual(variant["available"], 6)
        self.assertEqual(item["quantity"], 4)

    def test_report_queries_support_sales_and_low_stock(self) -> None:
        self.db.execute(
            """
            INSERT INTO orders(
                public_number, channel, status, customer_name,
                subtotal_cents, total_cents, payment_method, delivered_at
            ) VALUES(
                'LD-POS-REPORT', 'pos', 'delivered', 'Consumidor final',
                1490000, 1490000, 'cash', CURRENT_TIMESTAMP
            )
            """
        )
        sales = self.db.execute(
            """
            SELECT COUNT(*) AS sale_count, COALESCE(SUM(total_cents), 0) AS total
            FROM orders
            WHERE status = 'delivered'
              AND date(COALESCE(delivered_at, created_at), 'localtime')
                  = date('now', 'localtime')
            """
        ).fetchone()
        low_stock = self.db.execute(
            """
            SELECT COUNT(*) AS variants
            FROM product_variants v
            JOIN products p ON p.id = v.product_id
            WHERE p.active = 1
              AND v.active = 1
              AND v.stock_on_hand - v.stock_reserved <= v.min_stock
            """
        ).fetchone()
        self.assertEqual(sales["sale_count"], 1)
        self.assertEqual(sales["total"], 1490000)
        self.assertEqual(low_stock["variants"], 0)


if __name__ == "__main__":
    unittest.main()
