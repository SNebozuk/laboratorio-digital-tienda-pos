-- Generado desde catalogo-catalogo-anterior-corregido.csv.
-- Se aplica una sola vez mediante schema_migrations(version = 2).

INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('SUBLIMABLES', 'sublimables', 0, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('ACCESORIOS', 'accesorios', 1, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('REMERAS', 'remeras', 2, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('BUZOS', 'buzos', 3, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('APRENDE', 'aprende', 4, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('PAPELES', 'papeles', 5, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('GORRAS', 'gorras', 6, 1);
INSERT OR IGNORE INTO categories(name, slug, sort_order, active) VALUES('TINTAS', 'tintas', 7, 1);


-- 10 UNIDADES PIN SUBLIMABLE POLYMER MUG
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, '10 UNIDADES PIN SUBLIMABLE POLYMER MUG', 'Dimensiones: 5 cm de diametro Espesor : 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 40 seg Área de estampado: 4.5 cm de diametro Superf...', '/v1/assets/catalog/pin-e3bbf9dbea020d7061169938726148331-f617f7687ccb5ed56a16993872719856-1024-1024.jpg', 1, 0
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = '10 UNIDADES PIN SUBLIMABLE POLYMER MUG' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-189991916-761868247', NULL, 300000, 195, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = '10 UNIDADES PIN SUBLIMABLE POLYMER MUG' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- AGENDA MADERA CRISTAL - CR225
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'AGENDA MADERA CRISTAL - CR225', 'Agenda A5 perpetua para sublimar Medidas:• 15,5 cm x 22 cm Cantidad:• Bolsa x1 unidad Incluye: • Tapa y contratapa sublimables• 50 hojas personalizadas • Ani...', '/v1/assets/catalog/cuaderno-2494924ae1211a7eb017708218564617-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'AGENDA MADERA CRISTAL - CR225' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR225', NULL, 820000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'AGENDA MADERA CRISTAL - CR225' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ALHAJERO MADERA CRISTAL - CR21
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ALHAJERO MADERA CRISTAL - CR21', 'Dimensiones: 8cm x 8cm x 4.5cm Espesor : 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de estampado: completo Superficie bri...', '/v1/assets/catalog/alhajero-5eeb7f3ec80383e04e17211443416302-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ALHAJERO MADERA CRISTAL - CR21' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR21', NULL, 230000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'ALHAJERO MADERA CRISTAL - CR21' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- APOYA CELULAR MADERA CRISTAL - CR37
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'APOYA CELULAR MADERA CRISTAL - CR37', 'Dimensiones: 16 cm alto x 7.5 cm de ancho x 14 profundidad Espesor: 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de estam...', '/v1/assets/catalog/apoya-celular-1-4f8de18c370a8c87bb17171697573036-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'APOYA CELULAR MADERA CRISTAL - CR37' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR37', NULL, 130000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'APOYA CELULAR MADERA CRISTAL - CR37' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ARGOLLA METÁLICA PARA LLAVES - 10 UNIDADES
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ARGOLLA METÁLICA PARA LLAVES - 10 UNIDADES', 'Argolla metálica redonda ideal para sujetar llaves con cierre sencillo. Compacta y durable, perfecta para uso diario. ¡Consigue la tuya ahora!', '/v1/assets/catalog/shopping-8654526a790aa2124017690493835394-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ARGOLLA METÁLICA PARA LLAVES - 10 UNIDADES' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-320000081-1418471342', NULL, 50000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'ARGOLLA METÁLICA PARA LLAVES - 10 UNIDADES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ATRIL POSAVASOS MADERA - CR43
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ATRIL POSAVASOS MADERA - CR43', '10 cm - no sublimable', '/v1/assets/catalog/atril-posavaso1-939812099a7bce2c3d15544705552622-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ATRIL POSAVASOS MADERA - CR43' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34564009-87344533', NULL, 40000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'ATRIL POSAVASOS MADERA - CR43' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- AZUCARERA - YERBERA POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'AZUCARERA - YERBERA POLYMER MUG SUBLIMABLE', 'Dimensiones: 13,5 cm de alto x 8 cm de diámetro Espesor: 3 mm Color: Blanco Temperatura y tiempo de sublimación: 180º 180 segundos Área de estampado: 21 cm x...', '/v1/assets/catalog/yerbera1-f3868e09005533b68115664207504732-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'AZUCARERA - YERBERA POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'PAY', NULL, 300000, 21, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'AZUCARERA - YERBERA POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BANDEJA A4 MADERA CRISTAL - CR32D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BANDEJA A4 MADERA CRISTAL - CR32D', 'Dimensiones: 21 cm alto x 29 cm de ancho x 4.5 profundidad ( A3 ) Espesor: 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de ...', '/v1/assets/catalog/bandeja-a411-ce2b4879d40936cc2215544706300024-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BANDEJA A4 MADERA CRISTAL - CR32D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR32D', NULL, 530000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BANDEJA A4 MADERA CRISTAL - CR32D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BILLETERA MUJER NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BILLETERA MUJER NEGRO', 'Dimensiones abierta 31 cm x 20 cm Area sublimable 18 cm x 10 cm simil neoprene. Color negro "falso charol"', '/v1/assets/catalog/billetera-dama-negro1-07d4113f9247ee6a6415988930137453-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BILLETERA MUJER NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-61801488-187871947', NULL, 500000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BILLETERA MUJER NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BODY BEBE MANGAS LARGAS SPUM
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BODY BEBE MANGAS LARGAS SPUM', 'Body para bebé manga larga de Spum premium. Tela suave, abrigada y resistente, diseñada especialmente para obtener sublimaciones con colores vivos y duraderos. Ideal para regalería personalizada de invierno.', '/v1/assets/catalog/body-bebe-ml-color-blanco1-ed596d91bbdac3034016212605576980-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-88197526-874091076', NULL, 400000, 15, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-88197526-874091078', NULL, 400000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-88197526-874091079', NULL, 400000, 20, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-88197526-874091080', NULL, 400000, 21, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-88197526-874091083', NULL, 400000, 16, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-88197526-874091085', NULL, 400000, 18, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'BODY BEBE MANGAS LARGAS SPUM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOLSA FRISELINA 15X20
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOLSA FRISELINA 15X20', 'termosellada ultrasonido color blanco dimensiones 15 cm x 20 cm', '/v1/assets/catalog/bolsa-friselina-15x201-815445fdf5bea48bfa15569150484028-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOLSA FRISELINA 15X20' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-35401744-90494842', NULL, 40000, 365, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOLSA FRISELINA 15X20' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOLSA FRISELINA 20X30
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOLSA FRISELINA 20X30', '80 g termosellada ultrasonido color blanco dimensiones 30 cm x 20 cm', '/v1/assets/catalog/bolsa-chica-friselina1-11668feb7a8f1df86515571748519508-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOLSA FRISELINA 20X30' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-35466287-90719817', NULL, 50000, 289, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOLSA FRISELINA 20X30' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOLSA FRISELINA 30X30
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOLSA FRISELINA 30X30', 'termosellada ultrasonido color blanco dimensiones 35 cm x 30 cm', '/v1/assets/catalog/bolsa-friselina-30x301-bcdd8eb46731b4065715571746972070-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOLSA FRISELINA 30X30' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-35465474-90719445', NULL, 60000, 279, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOLSA FRISELINA 30X30' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOLSA FRISELINA 30X40
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOLSA FRISELINA 30X40', 'termosellada ultrasonido color blanco dimensiones 30 cm x 40 cm + 10 cm', '/v1/assets/catalog/bolsa-friselina11-14dac7f45d2b3f61f915544886109773-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOLSA FRISELINA 30X40' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34564711-87346531', NULL, 60000, 111, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOLSA FRISELINA 30X40' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOLSA FRISELINA 45X40
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOLSA FRISELINA 45X40', '80 g termosellada ultrasonido color blanco dimensiones 45 cm x 40 cm + 10 cm', '/v1/assets/catalog/bolsa-friselina1-14dac7f45d2b3f61f915544885599894-1024-1024.webp', 1, 12
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOLSA FRISELINA 45X40' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34564709-87346529', NULL, 80000, 252, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOLSA FRISELINA 45X40' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOTELLA CORDON SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOTELLA CORDON SUBLIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_44_47-0b6834860ff4943c0d17843104398361-1024-1024.webp', 1, 13
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOTELLA CORDON SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356131279-1560789512', NULL, 1120000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOTELLA CORDON SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BOTELLA TERMICA SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BOTELLA TERMICA SUBLIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_44_51-eccad82cec59a6679717843104250400-1024-1024.webp', 1, 14
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BOTELLA TERMICA SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356131415-1560789980', NULL, 1050000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'BOTELLA TERMICA SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BUZO CANGURO FRIZA CLASICA - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BUZO CANGURO FRIZA CLASICA - NEGRO', 'Comprá online BUZO CANGURO FRIZA CLASICA - NEGRO por $16.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/canguro-negro-clasico-hombre-frente-e0dcbf07a8665545b817483745360165-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-117315602-451941145', NULL, 1600000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-117315602-451941147', NULL, 1600000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-117315602-451941148', NULL, 1600000, 6, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-117315602-451941149', NULL, 1600000, 2, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-117315602-451941150', NULL, 1600000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-117315602-1478735484', NULL, 1890000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-117315602-1478735486', NULL, 1890000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CANGURO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO', 'aptos para estampar y DTF', '/v1/assets/catalog/chatgpt-image-9-mar-2026-10_09_36-be2bb326d08bdd813017730617859173-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-329926127-1468142487', NULL, 1490000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-329926127-1468142491', NULL, 1490000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-329926127-1468142492', NULL, 1490000, 1, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-329926127-1468142494', NULL, 1490000, 1, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-329926127-1468142498', NULL, 1490000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE', 'aptos para estampar y DTF', '/v1/assets/catalog/gemini_generated_image_w0kbgow0kbgow0kb-75aebc4fe1846b366a17730683677036-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-81664733-317063660', NULL, 1490000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-81664733-317063661', NULL, 1490000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-81664733-317063662', NULL, 1490000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-81664733-317063663', NULL, 1490000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-81664733-317063664', NULL, 1490000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO', 'Comprá online BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO por $14.900. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/cuello-redondo-negro-hombre-frente-07be87a6c720112c9017483765973055-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-117316084-876258379', NULL, 1490000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-117316084-876258383', NULL, 1490000, 3, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle3', 'TN-117316084-876258387', NULL, 1490000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-117316084-876258390', NULL, 1490000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-117316084-876258393', NULL, 1490000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'BUZO CUELLO REDONDO FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CAJA DE TE MADERA CRISTAL - CR29D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CAJA DE TE MADERA CRISTAL - CR29D', 'madera cristal sublimable 15 cm x 15 cm x 8.5 cm con tapa', '/v1/assets/catalog/cajon-multiuso-x41-4bd8ec47d0e29c192715659727681546-1024-1024.webp', 1, 15
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CAJA DE TE MADERA CRISTAL - CR29D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR29D', NULL, 700000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CAJA DE TE MADERA CRISTAL - CR29D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CAJA TAZA CARTON SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CAJA TAZA CARTON SUBLIMABLE', 'carton sublimable 10 cm x 10 cm x 10 cm', '/v1/assets/catalog/caja-de-carton-sublimable1-f0ba147878d658e3ad15544681551252-1024-1024.webp', 1, 16
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CAJA TAZA CARTON SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563972-87344495', NULL, 60000, 74, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CAJA TAZA CARTON SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CAJA TAZA VENTANA CARTON SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CAJA TAZA VENTANA CARTON SUBLIMABLE', 'Comprá online CAJA TAZA VENTANA CARTON SUBLIMABLE por $600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/caja-de-carton-2-ventana-sublimable-fe7239f67fd74e8f1817154383085793-1024-1024.webp', 1, 17
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CAJA TAZA VENTANA CARTON SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563971-87344494', NULL, 60000, 60, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CAJA TAZA VENTANA CARTON SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CAJITA FELIZ CARTON SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CAJITA FELIZ CARTON SUBLIMABLE', 'Comprá online CAJITA FELIZ CARTON SUBLIMABLE por $400. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/cajita-feliz-392de5cc16cbb6061417707532865615-1024-1024.webp', 1, 18
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CAJITA FELIZ CARTON SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-324372203-1437947639', NULL, 40000, 9, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CAJITA FELIZ CARTON SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CAMPERA FRIZA CLASICA - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CAMPERA FRIZA CLASICA - NEGRO', 'Dimensiones de Sisa a Sisa Los talles podrian variar entre 1 y 2 centimetros, se recomienda chequear en el local antes de retirar Talle 1: 53cm x 62cm Talle ...', '/v1/assets/catalog/campera-negra-hombre-c60b69a62d510580e217501840960912-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-172783878-876253858', NULL, 1800000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-172783878-876253860', NULL, 1800000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-172783878-876253864', NULL, 1800000, 4, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-172783878-876253866', NULL, 1800000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-172783878-876253869', NULL, 1800000, 5, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'BUZOS' COLLATE NOCASE
  AND p.name = 'CAMPERA FRIZA CLASICA - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CARTEL FORMA MADERA CRISTAL - CR23
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CARTEL FORMA MADERA CRISTAL - CR23', '11 cm x 25 cm', '/v1/assets/catalog/cartel-con-forma1-28c1b79d95331700f615544707273588-1024-1024.webp', 1, 19
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CARTEL FORMA MADERA CRISTAL - CR23' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR23', NULL, 230000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CARTEL FORMA MADERA CRISTAL - CR23' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CARTON BRILLANTE A3 SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CARTON BRILLANTE A3 SUBLIMABLE', 'carton 350g para tapas de cuadernos agendas carteles etc dimensiones 42 cm x 29,7 cm', '/v1/assets/catalog/carton-sublimable11-f8dc3b6a4d039369eb15544684598433-1024-1024.webp', 1, 20
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CARTON BRILLANTE A3 SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563976-87344499', NULL, 80000, 100, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CARTON BRILLANTE A3 SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CARTON BRILLANTE A4 SUBLIMABLE - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CARTON BRILLANTE A4 SUBLIMABLE - BLANCO', 'carton 350g para tapas de cuadernos agendas carteles etc dimensiones 29,7 cm x 21 cm', '/v1/assets/catalog/carton-sublimable1-f8dc3b6a4d039369eb15544683411116-1024-1024.webp', 1, 21
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CARTON BRILLANTE A4 SUBLIMABLE - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563975-87344498', NULL, 40000, 193, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CARTON BRILLANTE A4 SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CHOP CERVECERO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CHOP CERVECERO POLYMER MUG SUBLIMABLE', 'Comprá online CHOP CERVECERO POLYMER MUG SUBLIMABLE por $5.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/chop-plastico-ca7b81231243a5a65e17715966180691-1024-1024.webp', 1, 22
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CHOP CERVECERO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-326434157-1451388135', NULL, 500000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'CHOP CERVECERO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CINTA TERMICA WORKAT SUPERMIAU - 10MM
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CINTA TERMICA WORKAT SUPERMIAU - 10MM', 'La podés aplicar en remeras, tazas, gorras o cualquier otro objeto sublimable! 30 metros Resisten altas temperaturas: Supermiau 200°', '/v1/assets/catalog/10-mm-99023afdbe8d418f3517648585920200-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CINTA TERMICA WORKAT SUPERMIAU - 10MM' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-174519058-665958959', NULL, 280000, 62, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'CINTA TERMICA WORKAT SUPERMIAU - 10MM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CINTA TERMICA WORKAT SUPERMIAU - 5MM
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CINTA TERMICA WORKAT SUPERMIAU - 5MM', 'La podés aplicar en remeras, tazas, gorras o cualquier otro objeto sublimable! 30 metros Resisten altas temperaturas: Supermiau 200°', '/v1/assets/catalog/5mm-4b8a59067262d40e7317648585025767-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CINTA TERMICA WORKAT SUPERMIAU - 5MM' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-174520104-665962231', NULL, 180000, 95, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'CINTA TERMICA WORKAT SUPERMIAU - 5MM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- COMBO MATE IMAN + LLAVERO MADERA CRISTAL
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'COMBO MATE IMAN + LLAVERO MADERA CRISTAL', 'Combo Mate Sublimable – Iman + Llavero Combo de madera cristal sublimable, ideal para personalizar mediante sublimación. Incluye: 1 llavero de 3,5 × 7 cm 1...', '/v1/assets/catalog/chatgpt-image-26-jun-2026-12_55_08-p-m-9461b0c8bb53d9152717824895279810-1024-1024.webp', 1, 23
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'COMBO MATE IMAN + LLAVERO MADERA CRISTAL' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR217', NULL, 50000, 47, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'COMBO MATE IMAN + LLAVERO MADERA CRISTAL' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- COMO SUBLIMAR FRISELINA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'COMO SUBLIMAR FRISELINA', '👜 Cómo sublimar friselina La friselina es uno de los materiales más delicados para sublimar. Por eso, la clave no está tanto en la temperatura, sino ...', '/v1/assets/catalog/imagen-de-whatsapp-2025-08-25-a-las-12-39-34_95e6f7e8-ec323134b2ef6ebbed17561365200536-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'COMO SUBLIMAR FRISELINA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289752558-1296349480', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = 'COMO SUBLIMAR FRISELINA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- CONFORMADOR TAZA POLYMER MUG
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'CONFORMADOR TAZA POLYMER MUG', 'Comprá online CONFORMADOR TAZA POLYMER MUG por $1.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/conformador-mdf-1-c85afa50f08a28913717715965957656-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'CONFORMADOR TAZA POLYMER MUG' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-326433653-1451386161', NULL, 100000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'CONFORMADOR TAZA POLYMER MUG' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- DESTAPADOR DE PARED MADERA CRISTAL - CR82
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'DESTAPADOR DE PARED MADERA CRISTAL - CR82', 'Destapador de pared Dimensiones: 25 cm alto x 10 cm de ancho Espesor: 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de estam...', '/v1/assets/catalog/destapador-pared1-278fadd0c440478c0115949866850756-1024-1024.webp', 1, 24
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'DESTAPADOR DE PARED MADERA CRISTAL - CR82' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR82', NULL, 430000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'DESTAPADOR DE PARED MADERA CRISTAL - CR82' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ACETATO BRUMA CLARA A4 ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ACETATO BRUMA CLARA A4 ARTJET', 'Acetato esmerilado translúcido (Bruma Clara) para impresoras inkjet. Formato A4, perfecto para lograr efectos de transparencia suave en invitaciones, sobres y proyectos creativos de alta gama.', '/v1/assets/catalog/acetato-bruma-clara-84ffd0f6a72dbf256f17582117631810-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ACETATO BRUMA CLARA A4 ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F01-20', NULL, 890000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ACETATO BRUMA CLARA A4 ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ACETATO COOL WHITE A4 ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ACETATO COOL WHITE A4 ARTJET', 'Acetato blanco translúcido (Cool White) para impresoras inkjet. Formato A4, ideal para proyectos de iluminación, pantallas, tarjetería y manualidades con un acabado premium y moderno.', '/v1/assets/catalog/acetato-cool-white-ddfa3d0c018e65844f17582128599131-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ACETATO COOL WHITE A4 ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F02-20', NULL, 890000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ACETATO COOL WHITE A4 ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ACETATO VIDRIO FINO A4 ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ACETATO VIDRIO FINO A4 ARTJET', 'Acetato transparente flexible de 120 micrones para impresoras inkjet fotográficas. Ideal para cajas, etiquetas y proyectos decorativos. Alta calidad de imagen y resistencia a la torsión.', '/v1/assets/catalog/acetato-vidrio-fino-4bd989c4292288b61517582129495126-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ACETATO VIDRIO FINO A4 ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F03-20', NULL, 660000, 11, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ACETATO VIDRIO FINO A4 ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 ALUMINIO PERLADO ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 ALUMINIO PERLADO ARTJET', 'Film autoadhesivo con acabado aluminio perlado para impresoras inkjet. Formato A4, perfecto para etiquetas de alta gama, identificación de productos y proyectos de diseño con efecto plateado satinado.', '/v1/assets/catalog/filmilo-aluminio-perlado-84c272128392629e7617582130364514-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 ALUMINIO PERLADO ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F04-20', NULL, 1100000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 ALUMINIO PERLADO ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 BLANCO FRIO ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 BLANCO FRIO ARTJET', 'Film autoadhesivo color blanco frío para impresoras inkjet. Formato A4, perfecto para etiquetas con colores vibrantes y negros profundos. Ideal para señalética, stickers y personalización de alta calidad.', '/v1/assets/catalog/filmilo-blanco-frio-068aa9d244e31656bf17582105619023-1024-1024.jpg', 1, 4
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 BLANCO FRIO ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F05', NULL, 1200000, 42, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 BLANCO FRIO ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 BLANCO MATE ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 BLANCO MATE ARTJET', 'Film autoadhesivo blanco con acabado mate para impresoras inkjet. Formato A4, ideal para etiquetas que requieren una terminación sobria sin brillo, stickers decorativos y organización de productos.', '/v1/assets/catalog/filmilo-blanco-mate-5258b02724d2d834cf17582138426276-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 BLANCO MATE ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F06-20', NULL, 1100000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 BLANCO MATE ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 BLANCO PERLADO ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 BLANCO PERLADO ARTJET', 'Film autoadhesivo con acabado blanco perlado para impresoras inkjet. Formato A4, ideal para invitaciones de boda, etiquetas de cosmética y papelería creativa con un brillo sofisticado.', '/v1/assets/catalog/filmilo-blanco-perlado-5bcd4ab45415b8f07317582138911656-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 BLANCO PERLADO ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F07-20', NULL, 1100000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 BLANCO PERLADO ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 GRIS ESPACIAL ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 GRIS ESPACIAL ARTJET', 'Film autoadhesivo con acabado Gris Espacial (Space Grey) para impresoras inkjet. Formato A4, ideal para etiquetas de tecnología, stickers personalizados y proyectos de diseño con estética minimalista.', '/v1/assets/catalog/filmilo-gris-espacial-cc1c96f37f6cdd025017582139754739-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 GRIS ESPACIAL ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F09-20', NULL, 1100000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 GRIS ESPACIAL ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 GRIS PLATINUM ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 GRIS PLATINUM ARTJET', 'Film autoadhesivo con acabado Gris Platinum para impresoras inkjet. Formato A4, ideal para etiquetas técnicas, stickers industriales o decorativos con un estilo metalizado moderno y elegante.', '/v1/assets/catalog/filmilo-gris-platinum-414698a4eea78bf4fb17582138611510-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 GRIS PLATINUM ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F10-20', NULL, 1100000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 GRIS PLATINUM ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 NIEBLA TRANSLUCIDA ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 NIEBLA TRANSLUCIDA ARTJET', 'Film autoadhesivo con acabado esmerilado (Niebla Traslúcida) para impresoras inkjet. Formato A4, ideal para calcomanías decorativas, señalética en vidrio y etiquetas con efecto mate difuso.', '/v1/assets/catalog/filmilo-niebla-traslucida-5913863fb20c0a43c317582139108026-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 NIEBLA TRANSLUCIDA ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F11-20', NULL, 1100000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 NIEBLA TRANSLUCIDA ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 ORO ANTIGUO ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 ORO ANTIGUO ARTJET', 'Papel autoadhesivo con acabado oro antiguo para impresoras inkjet. Formato A4, perfecto para etiquetas de vino, productos artesanales y papelería fina con un toque clásico y elegante.', '/v1/assets/catalog/filmilo-oro-antiguo-9b2c9bf33c00e52d5117582139317341-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 ORO ANTIGUO ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F12-20', NULL, 1100000, 7, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 ORO ANTIGUO ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 ORO LUJOSO ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 ORO LUJOSO ARTJET', 'Papel adhesivo con acabado oro metalizado premium para impresoras inkjet. Formato A4, ideal para etiquetas de lujo, stickers de marcas exclusivas y proyectos decorativos brillantes.', '/v1/assets/catalog/filmilo-oro-lujoso-a4b9a3af9edc95323617582140022848-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 ORO LUJOSO ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F13-20', NULL, 1100000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 ORO LUJOSO ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- FILMILO ADHESIVO A4 SUPER CRISTAL ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'FILMILO ADHESIVO A4 SUPER CRISTAL ARTJET', 'Film autoadhesivo 100% transparente para impresoras inkjet. Formato A4, ideal para etiquetas invisibles, calcomanías de vidrio y personalización de frascos. Alta adherencia y claridad óptica.', '/v1/assets/catalog/filmilo-super-cristal-fc5226179f632dc36917582139549390-1024-1024.webp', 1, 12
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'FILMILO ADHESIVO A4 SUPER CRISTAL ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'F14-20', NULL, 1100000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'FILMILO ADHESIVO A4 SUPER CRISTAL ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GANCHOS ABROCHADORA 26/06 PLATA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GANCHOS ABROCHADORA 26/06 PLATA', 'En el Atelier donde cada hoja encuentra su lugar perfecto… nacen las Grampas 26/06 📎✨ Pensadas para el ritmo cotidiano, combinan practicidad y...', '/v1/assets/catalog/caja-de-grampas-y-piramide-de-metal-8e23d081537d3a4f4217746267340175-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GANCHOS ABROCHADORA 26/06 PLATA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-334501668-1487353019', NULL, 60000, 22, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'GANCHOS ABROCHADORA 26/06 PLATA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GLITERINA ADORIE AZUL A4 - 20 HOJA G4
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GLITERINA ADORIE AZUL A4 - 20 HOJA G4', 'Gliterina: ¡brillo que inspira creatividad! ?HOJAS A4 Sumergite en un mundo de alegría y color con Gliterina, la línea de papeles con glitter que transforma ...', '/v1/assets/catalog/azul-b9a1bfcf26a4b75efb17782519644195-1024-1024.webp', 1, 13
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GLITERINA ADORIE AZUL A4 - 20 HOJA G4' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'G1', NULL, 490000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'GLITERINA ADORIE AZUL A4 - 20 HOJA G4' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GLITERINA ADORIE BRONCE A4 - 20 HOJAS G3
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GLITERINA ADORIE BRONCE A4 - 20 HOJAS G3', 'Gliterina: ¡brillo que inspira creatividad! ?HOJAS A4 Sumergite en un mundo de alegría y color con Gliterina, la línea de papeles con glitter que transforma ...', '/v1/assets/catalog/bronce-86f323e950c0313bae17398001843157-1024-1024.webp', 1, 14
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GLITERINA ADORIE BRONCE A4 - 20 HOJAS G3' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'G3', NULL, 490000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'GLITERINA ADORIE BRONCE A4 - 20 HOJAS G3' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GLITERINA ADORIE VERDE OLIVA A4 - 20 HOJAS G31
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GLITERINA ADORIE VERDE OLIVA A4 - 20 HOJAS G31', 'Gliterina: ¡brillo que inspira creatividad! ?HOJAS A4 Sumergite en un mundo de alegría y color con Gliterina, la línea de papeles con glitter que transforma ...', '/v1/assets/catalog/verde-oliva-889af6e0d8d0406b0717398027146304-1024-1024.webp', 1, 15
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GLITERINA ADORIE VERDE OLIVA A4 - 20 HOJAS G31' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'G31', NULL, 490000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'GLITERINA ADORIE VERDE OLIVA A4 - 20 HOJAS G31' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA GABARDINA FRANCIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA GABARDINA FRANCIA', 'Gorra tipo béisbol de 6 gajos en color blanco de gabardina, ideal para personalizar con estampados, DTF o bordados. Este modelo no se sublima, lo que la conv...', '/v1/assets/catalog/chatgpt-image-13-feb-2026-20_32_56-1c55fabeb767fdaf1317710255797227-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA GABARDINA FRANCIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-308198708-1369492470', NULL, 380000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA GABARDINA FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - AMARILLO BLANCO AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - AMARILLO BLANCO AMARILLO', 'vicera y red amarillo - frente blanco sublimable', '/v1/assets/catalog/trucker-amarillo-blanco1-5ddf0a899f964dde4d15544687562484-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - AMARILLO BLANCO AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'ABA', NULL, 280000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - AMARILLO BLANCO AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - AMARILLO BLANCO MARINO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - AMARILLO BLANCO MARINO', 'Comprá online GORRA TRUCKER - AMARILLO BLANCO MARINO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/amarillo-blanco-marino-b5f501aa2aa9ee31b517710247862709-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - AMARILLO BLANCO MARINO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-325173219-1442159129', NULL, 280000, 20, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - AMARILLO BLANCO MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - CELESTE BLANCO CELESTE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - CELESTE BLANCO CELESTE', 'Comprá online GORRA TRUCKER - CELESTE BLANCO CELESTE por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-celeste-blanco-8b63ba7c86a73375c717709919233068-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - CELESTE BLANCO CELESTE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CBC', NULL, 280000, 8, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - CELESTE BLANCO CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - FRANCIA BLANCO FRANCIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - FRANCIA BLANCO FRANCIA', 'Comprá online GORRA TRUCKER - FRANCIA BLANCO FRANCIA por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/trucker-azul-blanco-4319cab53b6f2324b717709910341919-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - FRANCIA BLANCO FRANCIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'FRBFR', NULL, 280000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - FRANCIA BLANCO FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - FRANCIA FRANCIA FRANCIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - FRANCIA FRANCIA FRANCIA', 'Comprá online GORRA TRUCKER - FRANCIA FRANCIA FRANCIA por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-azul-francia1-993161f819187f77d916109391881867-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - FRANCIA FRANCIA FRANCIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'FRFRFR', NULL, 280000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - FRANCIA FRANCIA FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - FUCSIA BLANCO FUCSIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - FUCSIA BLANCO FUCSIA', 'vicera y red fucsia - frente blanco sublimable', '/v1/assets/catalog/trucker-fucsia-blanco1-82719650a01f04351815544692492087-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - FUCSIA BLANCO FUCSIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'FBF', NULL, 280000, 20, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - FUCSIA BLANCO FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - GRIS GRIS GRIS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - GRIS GRIS GRIS', 'Comprá online GORRA TRUCKER - GRIS GRIS GRIS por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-gris-76bc0164995603c9bb17635703480499-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - GRIS GRIS GRIS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'GRGRGR', NULL, 280000, 21, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - GRIS GRIS GRIS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - NEGRO NEGRO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - NEGRO NEGRO NEGRO', 'Comprá online GORRA TRUCKER - NEGRO NEGRO NEGRO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-negra1-06c7fa33d724a2bf1215544700719120-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - NEGRO NEGRO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'NNN', NULL, 280000, 68, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - NEGRO NEGRO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - ROJO BLANCO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - ROJO BLANCO NEGRO', 'Comprá online GORRA TRUCKER - ROJO BLANCO NEGRO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-negro-blanco-rojo-06c05089c05b8c406f17709921192558-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - ROJO BLANCO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'RBN', NULL, 280000, 19, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - ROJO BLANCO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - ROJO BLANCO ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - ROJO BLANCO ROJO', 'vicera sublimable', '/v1/assets/catalog/trucker-rojo-blanco1-bd4da7de948830b22b15544702255367-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - ROJO BLANCO ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'ROBRO', NULL, 280000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - ROJO BLANCO ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - ROJO ROJO ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - ROJO ROJO ROJO', 'Comprá online GORRA TRUCKER - ROJO ROJO ROJO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/trucker-rojo1-e1fea69d693a9f787b16109392548520-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - ROJO ROJO ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'RORORO', NULL, 280000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - ROJO ROJO ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER INFANTIL - AMARILLO BLANCO AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER INFANTIL - AMARILLO BLANCO AMARILLO', 'Comprá online GORRA TRUCKER INFANTIL - AMARILLO BLANCO AMARILLO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/trucker-amarillo-blanco1-5ddf0a899f964dde4d15703933015251-1024-1024.webp', 1, 12
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER INFANTIL - AMARILLO BLANCO AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'IABA', NULL, 280000, 12, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER INFANTIL - AMARILLO BLANCO AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER INFANTIL - MARINO BLANCO MARINO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER INFANTIL - MARINO BLANCO MARINO', 'Comprá online GORRA TRUCKER INFANTIL - MARINO BLANCO MARINO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-infantl-marino1-eb194f200be8b49d9916073031989380-1024-1024.webp', 1, 13
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER INFANTIL - MARINO BLANCO MARINO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'IMBM', NULL, 280000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER INFANTIL - MARINO BLANCO MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER INFANTIL - NEGRO NEGRO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER INFANTIL - NEGRO NEGRO NEGRO', 'Comprá online GORRA TRUCKER INFANTIL - NEGRO NEGRO NEGRO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gorra-trucker-negra1-06c7fa33d724a2bf1215703933363770-1024-1024.webp', 1, 14
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER INFANTIL - NEGRO NEGRO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'INNN', NULL, 280000, 18, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER INFANTIL - NEGRO NEGRO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER INFANTIL - ROJO BLANCO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER INFANTIL - ROJO BLANCO NEGRO', 'Comprá online GORRA TRUCKER INFANTIL - ROJO BLANCO NEGRO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/chatgpt-image-13-feb-2026-20_20_47-4802bd7e74bf46ca5617710248649933-1024-1024.webp', 1, 15
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER INFANTIL - ROJO BLANCO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-325173822-1442161557', NULL, 280000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER INFANTIL - ROJO BLANCO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER INFANTIL - ROJO BLANCO ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER INFANTIL - ROJO BLANCO ROJO', 'vicera y red fucsia - frente blanco', '/v1/assets/catalog/gorra-trucker-rojo-blanco-infantil1-6901dee5007a4e2a7316057106796924-1024-1024.webp', 1, 16
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER INFANTIL - ROJO BLANCO ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'IROBRO', NULL, 280000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER INFANTIL - ROJO BLANCO ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 BURBUJA TRANSPARENTE ARTJET - H19
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 BURBUJA TRANSPARENTE ARTJET - H19', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/burbuja-f720da2903c5d903f1169893437588371-9072499cc990108f7816989343984448-1024-1024.jpg', 1, 16
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 BURBUJA TRANSPARENTE ARTJET - H19' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H19-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 BURBUJA TRANSPARENTE ARTJET - H19' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 CORAZONADA TRANSPARENTE ARTJET - H01
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 CORAZONADA TRANSPARENTE ARTJET - H01', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-corazonada-transparente-7148b1065770a8aa2317541486563543-1024-1024.webp', 1, 17
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 CORAZONADA TRANSPARENTE ARTJET - H01' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H01-20', NULL, 1560000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 CORAZONADA TRANSPARENTE ARTJET - H01' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 DESTELLO BRILLANTE ARTJET - H12
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 DESTELLO BRILLANTE ARTJET - H12', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-destello-23481a8173bac6068016989348430194-1024-1024.webp', 1, 18
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 DESTELLO BRILLANTE ARTJET - H12' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H12-20', NULL, 1200000, 21, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 DESTELLO BRILLANTE ARTJET - H12' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 DIAMANTE TRANSPARENTE ARTJET - H03
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 DIAMANTE TRANSPARENTE ARTJET - H03', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-diamante-transparente-bb7b37c0e5f00a66ab17127622550124-1024-1024.webp', 1, 19
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 DIAMANTE TRANSPARENTE ARTJET - H03' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H03-20', NULL, 1560000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 DIAMANTE TRANSPARENTE ARTJET - H03' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 DISCO DANCE ARTJET - H17
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 DISCO DANCE ARTJET - H17', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/disco-dance-a5c09a26f7d837f22a16989344644140-1024-1024.webp', 1, 20
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 DISCO DANCE ARTJET - H17' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H17-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 DISCO DANCE ARTJET - H17' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 DULCE NAVIDAD ARTJET - H20
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 DULCE NAVIDAD ARTJET - H20', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-dulce-navidad-5a3cc96ef23e304cd417309180483428-1024-1024.webp', 1, 21
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 DULCE NAVIDAD ARTJET - H20' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H20-20', NULL, 1200000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 DULCE NAVIDAD ARTJET - H20' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 ENERGIA ESCOCESA ARTJET - H04
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 ENERGIA ESCOCESA ARTJET - H04', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-energio-escosesa-9a30cc0e6d16e2582017127606978780-1024-1024.webp', 1, 22
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 ENERGIA ESCOCESA ARTJET - H04' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H04-20', NULL, 1200000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 ENERGIA ESCOCESA ARTJET - H04' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 ESTRELLITA TRANSPARENTE ARTJET - H02
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 ESTRELLITA TRANSPARENTE ARTJET - H02', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-estrellita-transparente-3ce8f0bef759a223ad17127625715299-1024-1024.webp', 1, 23
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 ESTRELLITA TRANSPARENTE ARTJET - H02' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H02-20', NULL, 1560000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 ESTRELLITA TRANSPARENTE ARTJET - H02' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 FIESTA DE CONFETI ARTJET - H11
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 FIESTA DE CONFETI ARTJET - H11', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/fiesta-de-confeti-7b397e47511bfdbbdc16989343315689-1024-1024.webp', 1, 24
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 FIESTA DE CONFETI ARTJET - H11' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H11-20', NULL, 1200000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 FIESTA DE CONFETI ARTJET - H11' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 GOTITAS LLUVIA ARTJET - H06
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 GOTITAS LLUVIA ARTJET - H06', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-gotitas-de-lluvia-76292d139a22f34c3717097540182451-1024-1024.webp', 1, 25
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 GOTITAS LLUVIA ARTJET - H06' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H06-20', NULL, 1200000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 GOTITAS LLUVIA ARTJET - H06' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 HECHIZO BOHEMIO ARTJET - H05
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 HECHIZO BOHEMIO ARTJET - H05', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/efecto-hechizo-bohemio-50b14a1d537dc7d07517127605422152-1024-1024.webp', 1, 26
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 HECHIZO BOHEMIO ARTJET - H05' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H05-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 HECHIZO BOHEMIO ARTJET - H05' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 LLUVIA DE DIAMANTES ARTJET - H18
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 LLUVIA DE DIAMANTES ARTJET - H18', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/lluvia-de-diamantes-502e3ae789aa4edf5016989344213481-1024-1024.webp', 1, 27
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 LLUVIA DE DIAMANTES ARTJET - H18' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H18-20', NULL, 1200000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 LLUVIA DE DIAMANTES ARTJET - H18' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 NOCHE ESTRELLADA ARTJET - H10
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 NOCHE ESTRELLADA ARTJET - H10', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/holofan-efecto-noche-estrellada1-04273d6c930c6bd24216614383018189-1024-1024.webp', 1, 28
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 NOCHE ESTRELLADA ARTJET - H10' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H10-20', NULL, 1200000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 NOCHE ESTRELLADA ARTJET - H10' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 RAFAGA DE AMOR ARTJET - H15
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 RAFAGA DE AMOR ARTJET - H15', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/rafaga-de-amor-71389b54a8f3d2251a16989346853186-1024-1024.webp', 1, 29
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 RAFAGA DE AMOR ARTJET - H15' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H15-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 RAFAGA DE AMOR ARTJET - H15' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 ROCIO DE OTOO ARTJET - H08
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 ROCIO DE OTOO ARTJET - H08', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/rocio-de-otono-0a88ee3d9f47880e0d16989349620042-1024-1024.webp', 1, 30
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 ROCIO DE OTOO ARTJET - H08' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H08-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 ROCIO DE OTOO ARTJET - H08' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 TELARAA DE CAMPO ARTJET - H07
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 TELARAA DE CAMPO ARTJET - H07', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/telarana-cc48b3dc8a1761e3f116989350093615-1024-1024.webp', 1, 31
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 TELARAA DE CAMPO ARTJET - H07' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H07-20', NULL, 1200000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 TELARAA DE CAMPO ARTJET - H07' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 TORNADO PSICODELICO ARTJET - H14
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 TORNADO PSICODELICO ARTJET - H14', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/tornado-104ac1178a9a425d2c16989347194748-1024-1024.webp', 1, 32
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 TORNADO PSICODELICO ARTJET - H14' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H14-20', NULL, 1200000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 TORNADO PSICODELICO ARTJET - H14' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOPPY 500CC POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOPPY 500CC POLYMER MUG SUBLIMABLE', 'Para sublimar en 360 tener en cuenta que el primer estampado se hace en 180/180 y el resto de hacen en 60 segundos', '/v1/assets/catalog/hoppy-polymer1-3211289f4771e8388816036697458659-1024-1024.webp', 1, 25
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOPPY 500CC POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-66798065-230692915', NULL, 700000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'HOPPY 500CC POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOPPY DOBLE TAPA SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOPPY DOBLE TAPA SUBLIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_45_06-59ef81c1f45264100e17843103533031-1024-1024.webp', 1, 26
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOPPY DOBLE TAPA SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356132146-1560791975', NULL, 650000, 18, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'HOPPY DOBLE TAPA SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- JARRO TERMICO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'JARRO TERMICO POLYMER MUG SUBLIMABLE', 'Dimensiones: 24 cm de alto x 8 cm de diámetro Capacidad: 450cc Espesor: 4 mm Color: Blanco Temperatura y tiempo de sublimación: 180º 180 segundos Área de est...', '/v1/assets/catalog/jarro-termico1-becd9f5a25f71d43cb16853631347199-1024-1024.webp', 1, 27
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'JARRO TERMICO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'JTP', NULL, 400000, 23, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'JARRO TERMICO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO 40X60 POLIMERO SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO 40X60 POLIMERO SUBLIMABLE', '40 mm x 60 mm 4 mm de espesor Incluye Argolla', '/v1/assets/catalog/40x60-rect1-09b26a9ea6aa75ac4d15544809711381-1024-1024.webp', 1, 28
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO 40X60 POLIMERO SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'LL02', NULL, 40000, 75, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO 40X60 POLIMERO SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO ABU MADERA CRISTAL - CR66D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO ABU MADERA CRISTAL - CR66D', 'Medida: 6 cm x 4 cm Incluye argolla', '/v1/assets/catalog/llavero-abu-604cdc0d9882798fdb17181169621921-1024-1024.webp', 1, 29
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO ABU MADERA CRISTAL - CR66D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR66D', NULL, 30000, 64, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO ABU MADERA CRISTAL - CR66D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CAMISETA MADERA CRISTAL - CR03D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CAMISETA MADERA CRISTAL - CR03D', 'Medida: 5 cm x 5 cm Incluye argolla', '/v1/assets/catalog/llavero-camiseta-5e444bbf21906405ae17181170039875-1024-1024.webp', 1, 30
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CAMISETA MADERA CRISTAL - CR03D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR03D', NULL, 30000, 41, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CAMISETA MADERA CRISTAL - CR03D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CIRCULO 20 UNIDADES MADERA CRISTAL - CR02
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CIRCULO 20 UNIDADES MADERA CRISTAL - CR02', '5 cm incluye arandela', '/v1/assets/catalog/llavero-circulo1-7d0f620dc7740289ec15675352213428-1024-1024.webp', 1, 31
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CIRCULO 20 UNIDADES MADERA CRISTAL - CR02' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR02', NULL, 480000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CIRCULO 20 UNIDADES MADERA CRISTAL - CR02' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CIRCULO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CIRCULO POLYMER MUG SUBLIMABLE', '4 cm diametro 4 mm de espesor Incluye Argolla', '/v1/assets/catalog/circulo1-e129d56c8285b0b03a15544813555927-1024-1024.webp', 1, 32
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CIRCULO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'LL04', NULL, 40000, 100, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CIRCULO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CORAZON 20 UNIDADES MADERA CRISTAL - CR277D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CORAZON 20 UNIDADES MADERA CRISTAL - CR277D', '6 x 5.5cmincluye arandela y refuerzo plastico', '/v1/assets/catalog/chatgpt-image-27-feb-2026-10_55_48-d8a7ba60a05f80e0c317722005547669-1024-1024.webp', 1, 33
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CORAZON 20 UNIDADES MADERA CRISTAL - CR277D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR277D', NULL, 480000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CORAZON 20 UNIDADES MADERA CRISTAL - CR277D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CORAZON MADERA CRISTAL - CR277D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CORAZON MADERA CRISTAL - CR277D', 'Medida: 7 cm x 7 cm Material: Madera Cristal MDF de espesor 3mm Venta por 20 unidades Incluye Arandela...', '/v1/assets/catalog/llavero-corazon-20cbb1df88cde1bb9f17138807345444-1024-1024.webp', 1, 34
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CORAZON MADERA CRISTAL - CR277D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR277D-TN-209902805-876767525', NULL, 30000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CORAZON MADERA CRISTAL - CR277D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO CUADRADO 20 UNIDADES MADERA CRISTAL - CR01
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO CUADRADO 20 UNIDADES MADERA CRISTAL - CR01', '5 cm x 5 cm incluye arandela y refuerzo plastico', '/v1/assets/catalog/whatsapp-image-2026-02-13-at-8-10-34-pm-124c7e83abcc2bfaf017710243145824-1024-1024.webp', 1, 35
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO CUADRADO 20 UNIDADES MADERA CRISTAL - CR01' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR01', NULL, 480000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO CUADRADO 20 UNIDADES MADERA CRISTAL - CR01' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO DESTAPADOR POLIMERO SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO DESTAPADOR POLIMERO SUBLIMABLE', '40 mm x 70mm 4 mm de espesor Incluye Argolla', '/v1/assets/catalog/destapador1-4d7bcf382590b9bb6615662205547247-1024-1024.webp', 1, 36
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO DESTAPADOR POLIMERO SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'LL06', NULL, 70000, 75, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO DESTAPADOR POLIMERO SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO HUESO CHICO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO HUESO CHICO POLYMER MUG SUBLIMABLE', '40 x 20 mm', '/v1/assets/catalog/hueso-chico1-93c772f38ccd7860bb15544815004645-1024-1024.webp', 1, 37
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO HUESO CHICO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'LL08', NULL, 40000, 33, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO HUESO CHICO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO HUESO GRANDE POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO HUESO GRANDE POLYMER MUG SUBLIMABLE', '40 mm x 60 mm 4 mm de espesor Incluye Argolla', '/v1/assets/catalog/huesito1-6095521b9c7b4c147716100222651553-1024-1024.webp', 1, 38
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO HUESO GRANDE POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'LL09', NULL, 40000, 18, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO HUESO GRANDE POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO MARIPOSA 20 UNIDADES MADERA CRISTAL - CR278D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO MARIPOSA 20 UNIDADES MADERA CRISTAL - CR278D', '6 x 5.5cmincluye arandela y refuerzo plastico', '/v1/assets/catalog/chatgpt-image-27-feb-2026-10_51_20-9ec3de9d2fac10b03f17722003198439-1024-1024.webp', 1, 39
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO MARIPOSA 20 UNIDADES MADERA CRISTAL - CR278D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR278D', NULL, 480000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO MARIPOSA 20 UNIDADES MADERA CRISTAL - CR278D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO MARIPOSA MADERA CRISTAL - CR278D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO MARIPOSA MADERA CRISTAL - CR278D', '6cm x 5cm + Argolla', '/v1/assets/catalog/llavero-mariposa-da141b8f48440bdd3017105310369160-1024-1024.webp', 1, 40
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO MARIPOSA MADERA CRISTAL - CR278D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR278D-TN-204889045-854123398', NULL, 30000, 12, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO MARIPOSA MADERA CRISTAL - CR278D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO MATE MADERA CRISTAL - CR216
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO MATE MADERA CRISTAL - CR216', 'Medidas 3,5 x 7cm incluye argolla', '/v1/assets/catalog/llavero-mate1-4c33d5782d63d8e2cd16838128798807-1024-1024.webp', 1, 41
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO MATE MADERA CRISTAL - CR216' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR216', NULL, 30000, 87, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO MATE MADERA CRISTAL - CR216' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- LLAVERO PAPA MADERA CRISTAL - CR65D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'LLAVERO PAPA MADERA CRISTAL - CR65D', 'Medida: 6 cm x 4 cm incluye arandela', '/v1/assets/catalog/llavero-papa-aaa-ae011eff61c55c2ef117181171314023-1024-1024.webp', 1, 42
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'LLAVERO PAPA MADERA CRISTAL - CR65D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR65D', NULL, 30000, 12, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'LLAVERO PAPA MADERA CRISTAL - CR65D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATE LISTO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATE LISTO POLYMER MUG SUBLIMABLE', 'Mate listo para sublimar - incluye bombilla - conserva la temperatura Dimensiones: 19 cm de alto x 8 cm de diámetro Capacidad: 450 cc Espesor: 4 mm Color: B...', '/v1/assets/catalog/mate-listo-nuevo1-399dcb73ebd992cb4d15901660557362-1024-1024.webp', 1, 43
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATE LISTO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34564079-87344604', NULL, 550000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MATE LISTO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATE POLIMERO SUBLIMABLE WORKAT - ROSA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATE POLIMERO SUBLIMABLE WORKAT - ROSA', 'Comprá online MATE POLIMERO SUBLIMABLE WORKAT - ROSA por $3.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/chatgpt-image-15-abr-2026-13_03_55-82f06b59af1d24c75617762690399733-1024-1024.webp', 1, 44
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATE POLIMERO SUBLIMABLE WORKAT - ROSA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-338200789-1503217087', NULL, 360000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MATE POLIMERO SUBLIMABLE WORKAT - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATE POLYMER MUG SUBLIMABLE - ACCESORIO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATE POLYMER MUG SUBLIMABLE - ACCESORIO NEGRO', 'Incluye Accesorio', '/v1/assets/catalog/whatsapp-image-2026-01-28-at-3-34-36-pm-343aae74291473ebde17696254937985-1024-1024.webp', 1, 45
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATE POLYMER MUG SUBLIMABLE - ACCESORIO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-321332665-1424414220', NULL, 380000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MATE POLYMER MUG SUBLIMABLE - ACCESORIO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A3 200G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A3 200G ARTJET - 20 HOJAS', 'Comprá online MATELINA A3 200G ARTJET - 20 HOJAS por $6.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-200g-a3-20-hojas1-6f32d667ea788366fd16311331678393-1024-1024.webp', 1, 33
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A3 200G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M200A320', NULL, 600000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A3 200G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A4 130G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A4 130G ARTJET - 100 HOJAS', 'Comprá online MATELINA A4 130G ARTJET - 100 HOJAS por $9.300. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-130g-simple-faz-100-hojas1-5beb4a1d89973c978d16311324533531-1024-1024.webp', 1, 34
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A4 130G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M130A4100', NULL, 930000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A4 130G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A4 150G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A4 150G ARTJET - 100 HOJAS', 'Comprá online MATELINA A4 150G ARTJET - 100 HOJAS por $10.500. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-150g-simple-faz-100-hojas-0d351a44032cb658aa17436843157300-1024-1024.webp', 1, 35
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A4 150G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M150A4100', NULL, 1050000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A4 150G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A4 180G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A4 180G ARTJET - 100 HOJAS', 'Comprá online MATELINA A4 180G ARTJET - 100 HOJAS por $13.200. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-180g-simple-faz-100-hojas1-3bbc07933ee9d6c18d16311328864490-1024-1024.webp', 1, 36
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A4 180G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M180A4100', NULL, 1320000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A4 180G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A4 200G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A4 200G ARTJET - 100 HOJAS', 'Con un acabado mate perfecto, los papeles matelina simple faz, te ofrecen presentaciones de altísima calidad, luciendo los colores plenos de manera intensa. ...', '/v1/assets/catalog/matelina-200g-simple-faz-100-hojas1-12d02c2662b1a7cbd916311330481974-1024-1024.webp', 1, 37
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A4 200G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M200A4100', NULL, 1440000, 20, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A4 200G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA A4 230G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA A4 230G ARTJET - 100 HOJAS', 'Comprá online MATELINA A4 230G ARTJET - 100 HOJAS por $14.500. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-230g-simple-faz-100-hojas1-cc3c25bf5eb28974a016311334107233-1024-1024.webp', 1, 38
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA A4 230G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M230A4100', NULL, 1450000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA A4 230G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA ADHESIVO A4 108G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA ADHESIVO A4 108G ARTJET - 100 HOJAS', 'Comprá online MATELINA ADHESIVO A4 108G ARTJET - 100 HOJAS por $21.200. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/artjet-108g-mate-autoadhesivo-751b000b57f34d2f3217163871833837-1024-1024.webp', 1, 39
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA ADHESIVO A4 108G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'M108A4100', NULL, 2120000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA ADHESIVO A4 108G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA DOBLE FAZ A4 180G ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA DOBLE FAZ A4 180G ARTJET - 50 HOJAS', 'Comprá online MATELINA DOBLE FAZ A4 180G ARTJET - 50 HOJAS por $7.700. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-180g-doble-faz-50-hojas1-8c388b594e186c63e316311329318509-1024-1024.webp', 1, 40
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA DOBLE FAZ A4 180G ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'MD180A450', NULL, 770000, 9, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA DOBLE FAZ A4 180G ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA DOBLE FAZ A4 200G ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA DOBLE FAZ A4 200G ARTJET - 50 HOJAS', 'Transformá tus diseños con los papeles doble faz de Matelina, sin brillo óptico y con los mejores acabados de color en las dos caras,es ideal para armar caja...', '/v1/assets/catalog/matelina-200g-doble-faz-50-hojas1-088eeb7eff2518d14f16311335738193-1024-1024.webp', 1, 41
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA DOBLE FAZ A4 200G ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'MD200A450', NULL, 880000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA DOBLE FAZ A4 200G ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G CORTEZA DE PINO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G CORTEZA DE PINO ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G CORTEZA DE PINO ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-185g-corteza-de-pino-50-hojas1-feb128d7b0e1acefaa17296963135723-1024-1024.jpg', 1, 42
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G CORTEZA DE PINO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-238462481-1046192453', NULL, 1160000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G CORTEZA DE PINO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G CUERINA CLASICA ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G CUERINA CLASICA ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G CUERINA CLASICA ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-230g-cuerina-clasica-50-hojas1-e202f32f6bd1a10ddd17296961181788-1024-1024.jpg', 1, 43
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G CUERINA CLASICA ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-238461703-1046189423', NULL, 1160000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G CUERINA CLASICA ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G LINO NATURAL ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G LINO NATURAL ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G LINO NATURAL ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-185g-lino-natural-50-hojas1-ad1a60ed92fb991efd17296960461804-1024-1024.jpg', 1, 44
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G LINO NATURAL ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-238461230-1046187241', NULL, 1160000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G LINO NATURAL ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G NOGAL EUROPEO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G NOGAL EUROPEO ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G NOGAL EUROPEO ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-230g-nogal-europeo-50-hojas1-ab394c6316f2763f8617296962004391-1024-1024.jpg', 1, 45
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G NOGAL EUROPEO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-238462133-1046191261', NULL, 1160000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G NOGAL EUROPEO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G TEJIDO DE LANA ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G TEJIDO DE LANA ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G TEJIDO DE LANA ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/chatgpt-image-14-jul-2026-01_41_02-p-m-3cd24b20fcb995668b17840472744361-1024-1024.webp', 1, 46
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G TEJIDO DE LANA ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355517189-1558687510', NULL, 1160000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G TEJIDO DE LANA ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G TELAR NORTEÑO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G TELAR NORTEÑO ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G TELAR NORTEÑO ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-185g-telar-norteno-50-hojas-ff7edc004ef8fe665d17576907856232-1024-1024.webp', 1, 47
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G TELAR NORTEÑO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-293615570-1312344073', NULL, 1160000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G TELAR NORTEÑO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MEDIAS DE FUTBOL SUBLIMABLES
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MEDIAS DE FUTBOL SUBLIMABLES', 'Medias deportivas de poliester. Planta color negro y caña blanca sublimable T2: Planta del pie 18cm caña 21cm . Del 20 al 29 T3: Planta del pie 20cm caña 28...', '/v1/assets/catalog/medias-futbol1-19800e75443581c8e216175875069938-1024-1024.webp', 1, 46
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MEDIAS DE FUTBOL SUBLIMABLES' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-82102894-344326479', NULL, 510000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MEDIAS DE FUTBOL SUBLIMABLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-82102894-344326481', NULL, 510000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MEDIAS DE FUTBOL SUBLIMABLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-82102894-319063607', NULL, 510000, 2, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MEDIAS DE FUTBOL SUBLIMABLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-82102894-319063608', NULL, 510000, 3, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MEDIAS DE FUTBOL SUBLIMABLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- METALINA ADORIE DESTELLO A4 - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'METALINA ADORIE DESTELLO A4 - 10 HOJAS', '- Paquetes de 10 hojas en formato A4✨ La línea de papeles metalizados que transforma y realza tus ideas ✨ 💡 Perfectos para todos tus pr...', '/v1/assets/catalog/destello-d122bbd301b51bfc6a17720272185054-1024-1024.webp', 1, 48
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'METALINA ADORIE DESTELLO A4 - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-327341851-1455674575', NULL, 260000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'METALINA ADORIE DESTELLO A4 - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- METALINA ADORIE ORO MONEDA A4 - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'METALINA ADORIE ORO MONEDA A4 - 10 HOJAS', '- Paquetes de 10 hojas en formato A4✨ La línea de papeles metalizados que transforma y realza tus ideas ✨ 💡 Perfectos para todos tus pr...', '/v1/assets/catalog/oro-moneda-568acf6629998b45bd17720271235570-1024-1024.webp', 1, 49
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'METALINA ADORIE ORO MONEDA A4 - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'ME12', NULL, 260000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'METALINA ADORIE ORO MONEDA A4 - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- METALINA ADORIE TORNASOL A4 - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'METALINA ADORIE TORNASOL A4 - 10 HOJAS', '- Paquetes de 10 hojas en formato A4✨ La línea de papeles metalizados que transforma y realza tus ideas ✨ 💡 Perfectos para todos tus pr...', '/v1/assets/catalog/tornasol-1ed649778a8193112317720272070831-1024-1024.webp', 1, 50
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'METALINA ADORIE TORNASOL A4 - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-327341722-1455673641', NULL, 260000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'METALINA ADORIE TORNASOL A4 - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MOUSE PAD SUBLIMABLE REDONDO 19CM
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MOUSE PAD SUBLIMABLE REDONDO 19CM', 'Dimensiones abierta 31 cm x 20 cm Area sublimable 18 cm x 10 cm simil neoprene. Base color gris', '/v1/assets/catalog/mouse-pad-redondo-d5f6c81db973407e1d17710256403019-1024-1024.webp', 1, 47
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MOUSE PAD SUBLIMABLE REDONDO 19CM' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-325320602-1442974236', NULL, 90000, 38, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'MOUSE PAD SUBLIMABLE REDONDO 19CM' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOOGRAFICO MAGNETICO - IMANTADO - 1 HOJA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOOGRAFICO MAGNETICO - IMANTADO - 1 HOJA', 'Papel fotográfico brillante magnético para Inkjet, material ferroso muy flexible y de poco espesor, esto facilita la carga en la toma de hoja. Compatible con...', '/v1/assets/catalog/magnetico-52887722ed9e0a967d17702166449095-1024-1024.webp', 1, 51
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOOGRAFICO MAGNETICO - IMANTADO - 1 HOJA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-323025533-1431649862', NULL, 300000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOOGRAFICO MAGNETICO - IMANTADO - 1 HOJA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO 4R 10X15 200G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO 4R 10X15 200G ARTJET - 100 HOJAS', 'Papel fotográfico ArtJet 10x15 cm (4R) de 200g. Acabado brillante de alta resolución para impresoras inkjet. Secado instantáneo y colores vibrantes. Ideal para fotos y souvenirs.', '/v1/assets/catalog/4r1-352496fd7af79b6df516311356404625-1024-1024.webp', 1, 52
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO 4R 10X15 200G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-97799887-880991127', NULL, 360000, 16, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO 4R 10X15 200G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A3 200G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A3 200G ARTJET - 20 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A3 de 200g ArtJet. Calidad premium para impresoras inkjet, secado instantáneo y gran formato. Ideal para láminas, posters y presentaciones.', '/v1/assets/catalog/artjet-200g-a32-90f79b16fe5104015a15935568041812-1024-1024.webp', 1, 53
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A3 200G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '200A320', NULL, 800000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A3 200G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A3+ 200G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A3+ 200G ARTJET - 20 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A3 de 200g ArtJet. Calidad premium para impresoras inkjet, secado instantáneo y gran formato. Ideal para láminas, posters y presentaciones.', '/v1/assets/catalog/artjet-200g-a311-af8acbe4f770f204ed15935568519346-1024-1024.webp', 1, 54
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A3+ 200G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '200A3+20', NULL, 1000000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A3+ 200G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 120G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 120G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 120g ArtJet. Secado instantáneo y excelente resolución para impresoras inkjet. Ideal para folletos, volantes y Candy Bar.', '/v1/assets/catalog/a4-artjet-120g1-47293befb543d1a4d215875703291176-1024-1024.webp', 1, 55
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 120G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '120A4100', NULL, 860000, 26, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 120G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 140G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 140G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 140g ArtJet. Secado instantáneo y alta resolución para impresoras inkjet. Ideal para folletos, Candy Bar y presentaciones de calidad.', '/v1/assets/catalog/140g-x1001-173fca5d0d91de829016117714696788-1024-1024.webp', 1, 56
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 140G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '140A4100', NULL, 940000, 24, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 140G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 160G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 160G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 160g ArtJet. Secado instantáneo y alta resolución para impresoras inkjet. Ideal para Candy Bar, folletos de alta calidad y presentaciones.', '/v1/assets/catalog/a4-160g1-8ec054dd7e985f6e4e16015547021097-1024-1024.webp', 1, 57
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 160G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '160A4100', NULL, 1060000, 24, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 160G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 180G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 180G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 180g ArtJet. Secado instantáneo y colores vibrantes para impresoras inkjet. Ideal para folletos premium, candy bar y souvenirs.', '/v1/assets/catalog/180g-x1001-0d010fd517a2a32c2815793518419652-1024-1024.webp', 1, 58
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 180G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '180A4100', NULL, 1200000, 37, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 180G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 200G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 200G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 200g ArtJet. Secado instantáneo y máxima resolución para impresoras inkjet. Ideal para fotos de alta calidad, catálogos y tarjetería.', '/v1/assets/catalog/200g-x1001-42caf787a98c45566915793520804091-1024-1024.webp', 1, 59
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 200G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '200A4100', NULL, 1300000, 63, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 200G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 200G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 200G ARTJET - 20 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 200g ArtJet. Secado instantáneo y máxima resolución para impresoras inkjet. Ideal para fotos, catálogos y presentaciones.', '/v1/assets/catalog/200g-x1001-f2e95371eb8321d97715888605329208-1024-1024.webp', 1, 60
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 200G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '200A420', NULL, 500000, 31, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 200G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO A4 230G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO A4 230G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) tamaño A4 de 230g ArtJet. Secado instantáneo y alta densidad para impresoras inkjet. Ideal para portadas, tarjetería y fotos de alta calidad.', '/v1/assets/catalog/artjet-230g-x1001-3cb5ee3bef01983bf516758720861654-1024-1024.webp', 1, 61
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO A4 230G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '230A4100', NULL, 1440000, 19, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO A4 230G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO ADHESIVO A3 115G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO ADHESIVO A3 115G ARTJET - 20 HOJAS', 'Papel fotográfico autoadhesivo brillante (Glossy) tamaño A3 de 115g ArtJet. Calidad premium para impresoras inkjet, secado instantáneo y gran formato. Ideal para calcomanías grandes y Candy Bar.', '/v1/assets/catalog/a3-artjet-115g1-87a7ce3f5b08aff3e615875690838003-1024-1024.webp', 1, 62
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A3 115G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '115A320', NULL, 860000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A3 115G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS', 'Papel fotográfico autoadhesivo brillante (Glossy) tamaño A4 de 115g ArtJet. Secado instantáneo y adhesivo de alta calidad. Ideal para etiquetas, stickers y personalización.', '/v1/assets/catalog/a4-artjet-115g-autoadhesivo-100h1-659181a6a5e6ca4c6315875708518397-1024-1024.webp', 1, 63
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '115A4100', NULL, 2120000, 48, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 20 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 20 HOJAS', 'Papel fotográfico autoadhesivo brillante (Glossy) tamaño A4 de 115g ArtJet. Secado instantáneo y alta resolución para impresoras inkjet. Ideal para etiquetas personalizadas, stickers y candy bar.', '/v1/assets/catalog/a4-artjet-115g-autoadhesivo1-c02f1a3b3d36d8e11f15831740597090-1024-1024.webp', 1, 64
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 20 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '115A420', NULL, 600000, 76, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO ADHESIVO A4 115G ARTJET - 20 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO DOBLE FAZ A4 120G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO DOBLE FAZ A4 120G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) Doble Faz tamaño A4 de 120g ArtJet. Secado instantáneo en ambas caras. Ideal para folletos, catálogos y presentaciones de alta resolución.', '/v1/assets/catalog/a4-artjet-120g-doble-faz1-73d8ac59abb8eaf92816525554375023-1024-1024.webp', 1, 65
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO DOBLE FAZ A4 120G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-118401615-880994304', NULL, 1320000, 17, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO DOBLE FAZ A4 120G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CORTEZA DE PINO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CORTEZA DE PINO ARTJET - 50 HOJAS', 'Papel fotográfico con textura Canvas Corteza de Pino de 230g para impresoras inkjet. Formato A4, ideal para reproducciones artísticas, diplomas y tarjetas con un acabado rústico y elegante.', '/v1/assets/catalog/corteza-de-pino-71e44fe8a267755f8c17586532551747-1024-1024.webp', 1, 66
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CORTEZA DE PINO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-295770289-1320873917', NULL, 1160000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CORTEZA DE PINO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS LINO NATURAL ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS LINO NATURAL ARTJET - 50 HOJAS', 'Papel fotográfico con textura Lino Natural de 230g para impresoras inkjet. Formato A4, ideal para reproducciones de arte, tarjetería fina y presentaciones con un acabado textil elegante.', '/v1/assets/catalog/lino-natural-26943d3aedbc4f148f17098079655028-1024-1024.webp', 1, 67
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS LINO NATURAL ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-203714195-880973576', NULL, 1160000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS LINO NATURAL ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS NOGAL EUROPEO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS NOGAL EUROPEO ARTJET - 50 HOJAS', 'Papel fotográfico con textura Nogal Europeo de 230g para impresoras inkjet. Formato A4, ideal para fotografía artística, tarjetas de invitación y diseños con acabado símil madera elegante.', '/v1/assets/catalog/nogal-europeo-fcf3b58bb80e7e0c8f17097544603522-1024-1024.webp', 1, 68
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS NOGAL EUROPEO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-203647319-880970460', NULL, 1160000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS NOGAL EUROPEO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TEJIDO DE LANA ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TEJIDO DE LANA ARTJET - 50 HOJAS', 'Papel fotográfico con textura Tejido de Lana de 230g para impresoras inkjet. Formato A4, perfecto para fotografía artística, tarjetas de invitación y proyectos creativos con acabado textil único.', '/v1/assets/catalog/tejido-de-lana-da4a8824ea71d49c3b17097545628843-1024-1024.webp', 1, 69
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TEJIDO DE LANA ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-203647723-880974521', NULL, 1160000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TEJIDO DE LANA ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TELAR NORTEÑO ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TELAR NORTEÑO ARTJET - 50 HOJAS', '¡Ahora tus proyectos pueden parecer pinturas al óleo, creando una sensación artística única! La textura de Canvas agrega profundidad y dimensión a tus imágen...', '/v1/assets/catalog/chatgpt-image-14-jul-2026-01_38_33-p-m-77940f47ccd14c020917840471391765-1024-1024.webp', 1, 70
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TELAR NORTEÑO ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355515446-1558682127', NULL, 1160000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TELAR NORTEÑO ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TRAMADO DE ALGODON ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TRAMADO DE ALGODON ARTJET - 50 HOJAS', 'Papel fotográfico con textura Tramado de Algodón de 230g para impresoras inkjet. Formato A4, ideal para fotografía artística, tarjetas personales y presentaciones con un acabado premium y natural.', '/v1/assets/catalog/tramado-de-algodon-b41ba1dd22bc500e9d17610700157380-1024-1024.webp', 1, 71
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TRAMADO DE ALGODON ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-295769687-1320872190', NULL, 1160000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS TRAMADO DE ALGODON ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL SUBLIMACION COLOR UP 95% A4 ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL SUBLIMACION COLOR UP 95% A4 ARTJET - 100 HOJAS', 'PAPELES DE ALTA TRANSFERENCIA Papel encapado específicamente para sublimar, transfiere mas del 95% de la tinta. Resultados inmejorables, con colores vivos y ...', '/v1/assets/catalog/color-up-a4-d5743d39a1c352f23017577752911445-1024-1024.webp', 1, 72
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL SUBLIMACION COLOR UP 95% A4 ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'S100', NULL, 880000, 22, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL SUBLIMACION COLOR UP 95% A4 ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL SUBLIMACION TRICAPA A4 ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL SUBLIMACION TRICAPA A4 ARTJET - 100 HOJAS', 'origen chino - 100 g Tecnología tricapa, mayor resistencia Previene el caminito de hormiga No se dobla No es sensible a la humedad Alta transferencia y nit...', '/v1/assets/catalog/a4-artjet-sublimacion-tricapa1-f25349f1d64e44bffe16182396860353-1024-1024.webp', 1, 73
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL SUBLIMACION TRICAPA A4 ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'S101', NULL, 950000, 40, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL SUBLIMACION TRICAPA A4 ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 AMARILLO FLUO 180G - 20 HOJAS P22
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 AMARILLO FLUO 180G - 20 HOJAS P22', 'Comprá online PAPELINA ADORIE A4 AMARILLO FLUO 180G - 20 HOJAS P22 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/amarillo-fluo-53cc853a624d593cb817296944828995-1024-1024.webp', 1, 74
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 AMARILLO FLUO 180G - 20 HOJAS P22' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P22', NULL, 300000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 AMARILLO FLUO 180G - 20 HOJAS P22' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 BLANCO TIZA 180G - 20 HOJAS P01
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 BLANCO TIZA 180G - 20 HOJAS P01', 'Comprá online PAPELINA ADORIE A4 BLANCO TIZA 180G - 20 HOJAS P01 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/imagen_800x800-b2f06e7dc2cbacfe5817769727676786-1024-1024.webp', 1, 75
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 BLANCO TIZA 180G - 20 HOJAS P01' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P01', NULL, 300000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 BLANCO TIZA 180G - 20 HOJAS P01' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 FUCSIA CHIC 180G - 20 HOJAS P17
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 FUCSIA CHIC 180G - 20 HOJAS P17', 'Comprá online PAPELINA ADORIE A4 FUCSIA CHIC 180G - 20 HOJAS P17 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/fucsia-chic-f306b2dcf8fa38f2d017296946523650-1024-1024.webp', 1, 76
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 FUCSIA CHIC 180G - 20 HOJAS P17' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P17', NULL, 300000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 FUCSIA CHIC 180G - 20 HOJAS P17' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 GRIS PIEDRA 180G - 20 HOJAS P05
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 GRIS PIEDRA 180G - 20 HOJAS P05', 'Comprá online PAPELINA ADORIE A4 GRIS PIEDRA 180G - 20 HOJAS P05 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/gris-piedra-3d7fe18ce32bb81e4f17296947262103-1024-1024.webp', 1, 77
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 GRIS PIEDRA 180G - 20 HOJAS P05' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P05', NULL, 300000, 8, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 GRIS PIEDRA 180G - 20 HOJAS P05' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 MARRON CAFE 180G - 20 HOJAS P12
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 MARRON CAFE 180G - 20 HOJAS P12', 'Comprá online PAPELINA ADORIE A4 MARRON CAFE 180G - 20 HOJAS P12 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/marron-cafe-be0b08fdcc88dc393d17296948447074-1024-1024.webp', 1, 78
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 MARRON CAFE 180G - 20 HOJAS P12' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P12', NULL, 300000, 9, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 MARRON CAFE 180G - 20 HOJAS P12' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 NARANJA CARAMELO 180G - 20 HOJAS P20
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 NARANJA CARAMELO 180G - 20 HOJAS P20', 'Comprá online PAPELINA ADORIE A4 NARANJA CARAMELO 180G - 20 HOJAS P20 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/naranja-caramelo-fee890ec5accf038cc17296948582201-1024-1024.webp', 1, 79
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 NARANJA CARAMELO 180G - 20 HOJAS P20' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P20', NULL, 300000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 NARANJA CARAMELO 180G - 20 HOJAS P20' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 ROJO TOMATE 180G - 20 HOJAS P19
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 ROJO TOMATE 180G - 20 HOJAS P19', 'Comprá online PAPELINA ADORIE A4 ROJO TOMATE 180G - 20 HOJAS P19 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/rojo-tomate-5c6984d9fa6df2232517296948870449-1024-1024.webp', 1, 80
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 ROJO TOMATE 180G - 20 HOJAS P19' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P19', NULL, 300000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 ROJO TOMATE 180G - 20 HOJAS P19' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 VERDE INGLES 180G - 20 HOJAS P07
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 VERDE INGLES 180G - 20 HOJAS P07', 'Comprá online PAPELINA ADORIE A4 VERDE INGLES 180G - 20 HOJAS P07 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/verde-ingles-dffbb067381307e9fe17296949516379-1024-1024.webp', 1, 81
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 VERDE INGLES 180G - 20 HOJAS P07' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P07', NULL, 300000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 VERDE INGLES 180G - 20 HOJAS P07' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 VERDE PRIMAVERA 180G - 20 HOJAS P09
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 VERDE PRIMAVERA 180G - 20 HOJAS P09', 'Comprá online PAPELINA ADORIE A4 VERDE PRIMAVERA 180G - 20 HOJAS P09 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/verde-primavera-1e8a4ea55e651c2ed617296949794965-1024-1024.webp', 1, 82
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 VERDE PRIMAVERA 180G - 20 HOJAS P09' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P09', NULL, 300000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 VERDE PRIMAVERA 180G - 20 HOJAS P09' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPELINA ADORIE A4 VERDE SELVA 180G - 20 HOJAS P08
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPELINA ADORIE A4 VERDE SELVA 180G - 20 HOJAS P08', 'Comprá online PAPELINA ADORIE A4 VERDE SELVA 180G - 20 HOJAS P08 por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/verde-selva-ad0bd4192fbb70712217296949933335-1024-1024.webp', 1, 83
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPELINA ADORIE A4 VERDE SELVA 180G - 20 HOJAS P08' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'P08', NULL, 300000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPELINA ADORIE A4 VERDE SELVA 180G - 20 HOJAS P08' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAR DE CANILLERAS - INFANTIL
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAR DE CANILLERAS - INFANTIL', 'Medidas 16 cm x 9 cm Excelentes canilleras sublimables, confeccionadas con plástico anti impacto, goma y tela sublimable. Se entregan con forma plana para su...', '/v1/assets/catalog/canillera-infantil1-da17ecfc6e80884eb015794433173833-1024-1024.webp', 1, 48
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAR DE CANILLERAS - INFANTIL' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-44760275-116759280', NULL, 500000, 20, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PAR DE CANILLERAS - INFANTIL' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAR DE CANILLERAS - JUVENIL
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAR DE CANILLERAS - JUVENIL', 'Medidas 19 cm x 10,5 cm Excelentes canilleras sublimables, confeccionadas con plástico anti impacto, goma y tela sublimable. Se entregan con forma plana pa...', '/v1/assets/catalog/canillera-junior1-e03d5e257c0d4137ef15794434292745-1024-1024.webp', 1, 49
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAR DE CANILLERAS - JUVENIL' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-44760314-116759489', NULL, 520000, 21, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PAR DE CANILLERAS - JUVENIL' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PLACA 20X29 MADERA CRISTAL - CR47
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PLACA 20X29 MADERA CRISTAL - CR47', '3 mm espesor 20 cm x 29 cm', '/v1/assets/catalog/placa-2029-b84ba88303e653aa4617708220914964-1024-1024.webp', 1, 50
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PLACA 20X29 MADERA CRISTAL - CR47' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR47', NULL, 450000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PLACA 20X29 MADERA CRISTAL - CR47' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PLATO 15 CM POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PLATO 15 CM POLYMER MUG SUBLIMABLE', 'Ala y Centro Sublimable', '/v1/assets/catalog/chatgpt-image-20-ene-2026-15_56_26-c2f735a05f3468020817689354143840-1024-1024.webp', 1, 51
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PLATO 15 CM POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '1515', NULL, 130000, 12, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PLATO 15 CM POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PLATO 20 CM POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PLATO 20 CM POLYMER MUG SUBLIMABLE', 'Centro Sublimable', '/v1/assets/catalog/chatgpt-image-20-ene-2026-16_03_02-ab990a6d2bc582562c17689358113048-1024-1024.webp', 1, 52
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PLATO 20 CM POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '2020', NULL, 320000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PLATO 20 CM POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA LLAVES MADERA CRISTAL - CR11D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA LLAVES MADERA CRISTAL - CR11D', 'Medidas: 20 x 11 cm', '/v1/assets/catalog/porta-llaves-e236be290d82b3849517181169298953-1024-1024.webp', 1, 53
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA LLAVES MADERA CRISTAL - CR11D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR11D', NULL, 180000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA LLAVES MADERA CRISTAL - CR11D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA RETRATO 13X18 CORAZONES MADERA CRISTAL - CR378D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA RETRATO 13X18 CORAZONES MADERA CRISTAL - CR378D', 'marco 13 cm x 18 cm3 corazones calados', '/v1/assets/catalog/13x18-corazones-312361de9d1820974617407513533521-1024-1024.webp', 1, 54
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA RETRATO 13X18 CORAZONES MADERA CRISTAL - CR378D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR378D', NULL, 190000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA RETRATO 13X18 CORAZONES MADERA CRISTAL - CR378D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA RETRATO AMOR MADERA CRISTAL - CR73
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA RETRATO AMOR MADERA CRISTAL - CR73', '27 cm x 15 cm', '/v1/assets/catalog/amor1-defd98cf2a1a2fd9c115544728279951-1024-1024.webp', 1, 55
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA RETRATO AMOR MADERA CRISTAL - CR73' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR73', NULL, 340000, 9, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA RETRATO AMOR MADERA CRISTAL - CR73' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA ROLLO MADERA CRISTAL - CR90D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA ROLLO MADERA CRISTAL - CR90D', 'Dimensiones: 11 cm alto x 22 cm de ancho Espesor: 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Superficie brillante, colores nít...', '/v1/assets/catalog/portarollo-d192cd976437a0f86117690214224594-1024-1024.webp', 1, 56
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA ROLLO MADERA CRISTAL - CR90D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR90D', NULL, 300000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA ROLLO MADERA CRISTAL - CR90D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA TAZA CARTON SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA TAZA CARTON SUBLIMABLE', 'carton sublimable 19 cm x 14,3 cm', '/v1/assets/catalog/caja-portataza1-eb420fba1bafdcedd415544685799562-1024-1024.webp', 1, 57
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA TAZA CARTON SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563979-87344502', NULL, 50000, 91, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA TAZA CARTON SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTARETRATO FELIZ DIA MADERA CRISTAL - CR51
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTARETRATO FELIZ DIA MADERA CRISTAL - CR51', 'con pie 12.5 cm x 15 cm', '/v1/assets/catalog/feliz-dia1-40b4776d69edf4820a15544850003927-1024-1024.webp', 1, 58
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTARETRATO FELIZ DIA MADERA CRISTAL - CR51' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR51', NULL, 150000, 26, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTARETRATO FELIZ DIA MADERA CRISTAL - CR51' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- POSAVASO CIRCULO MADERA CRISTAL - CR09
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'POSAVASO CIRCULO MADERA CRISTAL - CR09', '10 cm X 10cm No incluye atril precio por unidad', '/v1/assets/catalog/830c1f9c-7dbf-4bd4-ad4a-319218f35cdf_nube-153598525cdffc7dd615921358328076-1024-1024.webp', 1, 59
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'POSAVASO CIRCULO MADERA CRISTAL - CR09' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR09', NULL, 80000, 39, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'POSAVASO CIRCULO MADERA CRISTAL - CR09' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- POSAVASO CUADRADO MADERA CRISTAL - CR08
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'POSAVASO CUADRADO MADERA CRISTAL - CR08', '10 cm X 10cm', '/v1/assets/catalog/d83426f3-89b2-4ec4-88eb-8816a6a376ac_nube-50e360018f4a572ea115921358458468-1024-1024.webp', 1, 60
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'POSAVASO CUADRADO MADERA CRISTAL - CR08' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR08', NULL, 80000, 62, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'POSAVASO CUADRADO MADERA CRISTAL - CR08' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO', 'Remera amarilla infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Color intenso, tela suave y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/a9ae5b8b-d0db-4e83-837e-97f21c0bd971-6c035af01f3f08517617576049861641-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-293381737-1311253792', NULL, 500000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-293381737-1311253795', NULL, 500000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-293381737-1311253798', NULL, 500000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-293381737-1311253800', NULL, 500000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-293381737-1311253802', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-293381737-1311253806', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO', 'Remera azul marino infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Excelente terminación y suavidad. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-20-nov-2025-17_01_05-20d394d169d94d0ba517636688801647-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-308480040-1370548943', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-308480040-1370548945', NULL, 500000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-308480040-1370548948', NULL, 500000, 1, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-308480040-1370548949', NULL, 500000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-308480040-1370548952', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-308480040-1370548953', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - AZUL MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO', 'Remera blanca infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF, serigrafía y aplicaciones textiles. Tela suave que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/blanco-49e7d71ac1a713141117558741468196-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-218417051-956004003', NULL, 500000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-218417051-956004006', NULL, 500000, 8, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-218417051-956004008', NULL, 500000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-218417051-956004010', NULL, 500000, 3, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-218417051-956004012', NULL, 500000, 4, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-218417051-956004014', NULL, 500000, 7, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-218417051-956004017', NULL, 500000, 6, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE', 'Remera celeste infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Tela fresca, suave y resistente que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-inf-celester-20d76bc20a7f48009c17611620552140-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-288833049-1292659950', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-288833049-1292659957', NULL, 500000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-288833049-1292659959', NULL, 500000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-288833049-1292659966', NULL, 500000, 1, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-288833049-1292659969', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-288833049-1292659975', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA', 'Remera fucsia infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF, serigrafía y bordado. No achica y es suave al tacto. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-12-dic-2025-10_11_04-a8ebf1832fba4d4da417655450769105-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-312528312-1385545122', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-312528312-1385545135', NULL, 500000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-312528312-1385545143', NULL, 500000, 2, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-312528312-1385545153', NULL, 500000, 4, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-312528312-1385545159', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-312528312-1385545168', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - LILA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA', 'Remera lila infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF, serigrafía y bordado. Textura suave que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/6432ce18-6992-462e-938d-94f11b307537-7ae72f0397359e5e7317636683263910-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-308477974-1370542440', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-308477974-1370542451', NULL, 500000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-308477974-1370542457', NULL, 500000, 5, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-308477974-1370542462', NULL, 500000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-308477974-1370542465', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-308477974-1370542469', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO', 'Remera negra infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Algodón de alta gama que mantiene el color y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/negro-487a7475d8e17dfa3d17558741154342-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-42647818-1560755451', NULL, 500000, 7, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-42647818-956003531', NULL, 500000, 8, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-42647818-930964636', NULL, 500000, 12, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-42647818-930964638', NULL, 500000, 21, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-42647818-930964640', NULL, 500000, 16, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-42647818-930964642', NULL, 500000, 18, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-42647818-930964644', NULL, 500000, 14, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-42647818-930964647', NULL, 500000, 11, 0, 1, 7, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO', 'Remera roja infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Color intenso, tacto suave y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/rojo-e64f4bee6d4297aaaa17557447182691-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-288832873-1292658653', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-288832873-1292658657', NULL, 500000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-288832873-1292658659', NULL, 500000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-288832873-1292658664', NULL, 500000, 3, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-288832873-1292658667', NULL, 500000, 2, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-288832873-1292658669', NULL, 500000, 1, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA', 'Remera rosa infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF, serigrafía y bordado. Tela suave de alta durabilidad que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-inf-rosa-5a2fd584abfc939e4217611616023376-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-288832986-1292659470', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-288832986-1292659473', NULL, 500000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-288832986-1292659478', NULL, 500000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-288832986-1292659482', NULL, 500000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-288832986-1292659486', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-288832986-1292659490', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE', 'Remera verde infantil de algodón peinado 24.1. Calidad premium ideal para estampar con DTF y serigrafía. Tela resistente que mantiene el color y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-nino-verde-ee8f8a4bfb0cd4d2da17611601846928-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-301624037-1343811267', NULL, 500000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-301624037-1343811271', NULL, 500000, 3, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-301624037-1343811276', NULL, 500000, 2, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-301624037-1343811278', NULL, 500000, 5, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-301624037-1343811281', NULL, 500000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-301624037-1343811284', NULL, 500000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL ALGODON PEINADO 24.1 - VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO', 'Remera blanca infantil de set deportivo (poliéster) ideal para sublimación. Tela técnica de secado rápido, fresca y resistente. Calidad premium para indumentaria escolar o deportiva.', '/v1/assets/catalog/jersey-infantil-blanco-5e3a8ab173eb9c8c3f17612260176626-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-203458329-847224571', NULL, 300000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-203458329-847224576', NULL, 300000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-203458329-847224578', NULL, 300000, 8, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-203458329-847224583', NULL, 300000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-203458329-847224586', NULL, 300000, 6, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-203458329-847224593', NULL, 300000, 5, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL JERSEY DEPORTIVO SUBLIMABLE - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL MODAL - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL MODAL - BLANCO', 'Remera blanca para niños confeccionada en modal de alta calidad. Tela extra suave, elástica y con el blanco perfecto para sublimaciones vibrantes. Comodidad total para los más chicos.', '/v1/assets/catalog/spum-blanco-inf1-3dd7a1af3d5e1eb72117568389245130-1024-1024.jpg', 1, 12
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-291527958-1304097483', NULL, 400000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-291527958-1304097491', NULL, 400000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-291527958-1304097497', NULL, 400000, 6, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-291527958-1304097503', NULL, 400000, 4, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'R', NULL, 400000, 8, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-291527958-1304097517', NULL, 400000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL MODAL - GRIS MELANGE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL MODAL - GRIS MELANGE', 'Remera gris melange para niños confeccionada en modal premium. Tela suave al tacto y elástica, ideal para sublimación de alta definición. Calce cómodo y duradero para uso diario.', '/v1/assets/catalog/gris-infantil1-dbe1b5cc03276e677116512538552143-1024-1024.webp', 1, 13
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-100472596-931052246', NULL, 400000, 42, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-100472596-931052249', NULL, 400000, 6, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-100472596-931052252', NULL, 400000, 8, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-100472596-931052256', NULL, 400000, 26, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-100472596-931052258', NULL, 400000, 8, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-100472596-931052260', NULL, 400000, 9, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE', 'Remera ranglan infantil blanca con mangas verde. Confeccionada en Spum, la tela ideal para sublimar diseños coloridos y duraderos. Calidad premium para uso escolar y recreativo.', '/v1/assets/catalog/remera-infantil-ranglan-verde-61385579cc2ce38c6317160469951416-1024-1024.webp', 1, 14
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-213221661-940070940', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-213221661-940070943', NULL, 350000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-213221661-940070945', NULL, 350000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'TN-213221661-940070947', NULL, 350000, 10, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'TN-213221661-940070949', NULL, 350000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'TN-213221661-940070951', NULL, 350000, 2, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL RANGLAN SPUM - BLANCO/VERDE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA INFANTIL SPUM - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA INFANTIL SPUM - BLANCO', 'Remera blanca para niños confeccionada en Spum de alta calidad. La tela ideal para sublimación: colores vibrantes, gran resistencia a los lavados y secado rápido. Perfecta para uniformes o regalos personalizados.', '/v1/assets/catalog/spum-blanco-inf1-1c8f41f422d3135e1116512539140159-1024-1024.webp', 1, 15
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'RIS6', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'RIS8', NULL, 350000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'RIS10', NULL, 350000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', 'RIS12', NULL, 350000, 15, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', 'RIS14', NULL, 350000, 45, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 16', 'RIS16', NULL, 350000, 12, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA INFANTIL SPUM - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA MUJER ALGODON PEINADO 24.1 - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO', 'Remera blanca de mujer en algodón peinado 24.1 premium. Ideal para emprendedores de DTF, serigrafía y bordado. Calce cómodo, tela suave y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-mujer-blanco-f1d16282d2e0e9a95f17611598209897-1024-1024.webp', 1, 16
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-240059799-1319150954', NULL, 600000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-240059799-1319150959', NULL, 600000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-240059799-1319150967', NULL, 600000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-240059799-1319150971', NULL, 600000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-240059799-1053810877', NULL, 600000, 4, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-240059799-1326978176', NULL, 750000, 2, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-240059799-1484820163', NULL, 750000, 1, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA MUJER ALGODON PEINADO 24.1 - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO', 'Remera negra de mujer en algodón peinado 24.1 premium. Ideal para estampar con DTF y serigrafía. Excelente calce, tela que no achica y tacto suave. ¡Envíos a todo el país!', '/v1/assets/catalog/remera-mujer-negro1-767362ce3b3786d20c16559241175236-1024-1024.webp', 1, 17
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-62969664-931046824', NULL, 600000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-62969664-931046827', NULL, 600000, 3, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-62969664-931046829', NULL, 600000, 2, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-62969664-931046831', NULL, 600000, 4, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-62969664-931046833', NULL, 600000, 3, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-62969664-931046836', NULL, 750000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-62969664-931046837', NULL, 750000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA MUJER MODAL - GRIS MELANGE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA MUJER MODAL - GRIS MELANGE', 'Remera gris melange para mujer confeccionada en modal de alta calidad. Tela suave, elástica y perfecta para sublimación. Un básico versátil con excelente caída para uso diario o marcas.', '/v1/assets/catalog/remera-mujer-gris1-a5407b5d412142f79816626636893212-1024-1024.webp', 1, 18
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-61277399-184677334', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-61277399-184677335', NULL, 350000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-61277399-299227341', NULL, 350000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-61277399-299227342', NULL, 350000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-61277399-299227344', NULL, 350000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE', 'Remera gris melange unisex de algodón mercerizado de alta gama. Textura sedosa, brillo sutil y máxima durabilidad. Ideal para marcas exclusivas, DTF y bordados. ¡Envíos a todo el país!', '/v1/assets/catalog/algodon-mercerizado-gris-fb8e1f75de5d764ea517298835079942-1024-1024.webp', 1, 19
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-237530073-1042133210', NULL, 550000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-237530073-1042133213', NULL, 550000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-237530073-1042133216', NULL, 550000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-237530073-1042133219', NULL, 550000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-237530073-1042133221', NULL, 550000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-237530073-1042133224', NULL, 680000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-237530073-1042133227', NULL, 680000, 5, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON MERCERIZADO - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO', 'Remera negra unisex de algodón 20.1 Deluxe. Tejido de alto gramaje con excelente cuerpo y resistencia. No se deforma y mantiene el color. Ideal para marcas premium, DTF y bordado.', '/v1/assets/catalog/chatgpt-image-20-feb-2026-14_32_56-dbdff0b59d591ab07a17716087937433-1024-1024.webp', 1, 20
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', '201', NULL, 800000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', '201-TN-322515736-1439718258', NULL, 800000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle3', '201-TN-322515736-1439718262', NULL, 800000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', '201-TN-322515736-1439718265', NULL, 800000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', '201-TN-322515736-1439718270', NULL, 800000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', '201-TN-322515736-1439718274', NULL, 950000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', '201-TN-322515736-1439718279', NULL, 950000, 7, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', '201-TN-322515736-1429543006', NULL, 1100000, 0, 0, 1, 7, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 12', '201-TN-322515736-1429546729', NULL, 1100000, 0, 0, 1, 8, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 14', '201-TN-322515736-1455864290', NULL, 1200000, 0, 0, 1, 9, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 20.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO', 'Remera amarilla unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Color vibrante, calce clásico y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-4-nov-2025-16_24_55-fd9400ce339092739d17622843308739-1024-1024.webp', 1, 21
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-305093407-1357038764', NULL, 700000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-305093407-1357038773', NULL, 700000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-305093407-1357038779', NULL, 700000, 1, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-305093407-1357038786', NULL, 700000, 3, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-305093407-1357038792', NULL, 700000, 3, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA', 'Remera azul francia unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Color intenso, calce clásico y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-francia-d938aef19656a1572617557438868223-1024-1024.webp', 1, 22
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-288832465-1292655886', NULL, 700000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-288832465-1292655891', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-288832465-1292655893', NULL, 700000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-288832465-1292655897', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-288832465-1292655900', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-288832465-1357029598', NULL, 800000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-288832465-1357029608', NULL, 800000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 10', 'TN-288832465-1357029614', NULL, 800000, 1, 0, 1, 7, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - AZUL FRANCIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO', 'Remera blanca unisex de algodón peinado 24.1 premium. La base ideal para estampar con DTF, serigrafía y bordado. Calce clásico, tela fresca y duradera que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-blanco-b928a36cca54faefc317611458160998-1024-1024.webp', 1, 23
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-213081140-930923120', NULL, 700000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-213081140-930923122', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-213081140-930923123', NULL, 700000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-213081140-930923125', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-213081140-930923127', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-213081140-1385536604', NULL, 800000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-213081140-1351039387', NULL, 800000, 4, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - BORDO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO', 'Remera bordó unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Calce clásico, tela suave y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-12-feb-2026-14_11_14-36b2aa5b118e28c87017709162838356-1024-1024.webp', 1, 24
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-324701620-1439797613', NULL, 700000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-324701620-1439797620', NULL, 700000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-324701620-1439797625', NULL, 700000, 4, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-324701620-1439797629', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-324701620-1439797635', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - BORDO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA', 'Remera fucsia unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Color vibrante, calce clásico y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-fucsia-5cac9f0e930bf2f67e17611447715531-1024-1024.webp', 1, 25
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-301621487-1343800117', NULL, 700000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-301621487-1343800128', NULL, 700000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-301621487-1343800138', NULL, 700000, 3, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-301621487-1343800142', NULL, 700000, 1, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-301621487-1343800153', NULL, 700000, 2, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - FUCSIA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - LILA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA', 'Características: fabricado con fibras largas y uniformes, lo que le da una textura muy suave, caída elegante y aspecto de prenda de alta gama. Durabilidad: r...', '/v1/assets/catalog/chatgpt-image-17-mar-2026-16_07_34-8994fafbc777de928d17737745534765-1024-1024.webp', 1, 26
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-332110901-1477833525', NULL, 700000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-332110901-1477833529', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-332110901-1477833532', NULL, 700000, 1, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-332110901-1477833534', NULL, 700000, 3, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-332110901-1477833538', NULL, 700000, 3, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - LILA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - MARRON
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON', 'Remera marrón unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Calce clásico, tela suave y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-12-feb-2026-14_12_47-8ee0a7fdcd805baaf417709163740715-1024-1024.webp', 1, 27
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-324701250-1439796334', NULL, 700000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-324701250-1439796338', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-324701250-1439796347', NULL, 700000, 1, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-324701250-1439796350', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-324701250-1439796357', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - MARRON' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO', 'Remera negra unisex de algodón peinado 24.1 premium. Calidad superior, ideal para estampar con DTF, serigrafía y bordado. Calce clásico y tela resistente que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/remera-adulto-negra1-e038cd3bb35254632717159555606152-1024-1024.jpg', 1, 28
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'QQQ', NULL, 700000, 41, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'QQQ-TN-213075142-930899452', NULL, 700000, 41, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'QQQ-TN-213075142-930899454', NULL, 700000, 41, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'QQQ-TN-213075142-930899456', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'QQQ-TN-213075142-930899458', NULL, 700000, 23, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'QQQ-TN-213075142-930917613', NULL, 800000, 11, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'QQQ-TN-213075142-930917615', NULL, 800000, 13, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO', 'Remera roja unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Color vibrante y duradero, calce clásico y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-rojo-ed07e743b3cbf3618017611473071763-1024-1024.webp', 1, 29
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-293431040-1311398171', NULL, 700000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-293431040-1311398179', NULL, 700000, 2, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-293431040-1311398186', NULL, 700000, 6, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-293431040-1311398194', NULL, 700000, 10, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-293431040-1311398200', NULL, 700000, 1, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-293431040-1357036180', NULL, 800000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - ROSA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA', 'Remera rosa unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Calce clásico, tela suave de alta calidad y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/rosa-92ee6255dae992869b17557437903106-1024-1024.webp', 1, 30
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-288832316-1292654907', NULL, 700000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-288832316-1292654912', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-288832316-1292654916', NULL, 700000, 2, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-288832316-1292654919', NULL, 700000, 2, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-288832316-1292654923', NULL, 700000, 2, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES', 'Remera verde inglés unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Color clásico y sofisticado, tela suave y no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/peinado-verde-33f93ed5247521c85617611447498639-1024-1024.webp', 1, 31
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-301620651-1343797037', NULL, 700000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-301620651-1343797041', NULL, 700000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-301620651-1343797043', NULL, 700000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-301620651-1343797049', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-301620651-1343797052', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-301620651-1357027196', NULL, 800000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-301620651-1357027208', NULL, 800000, 1, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE INGLES' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR', 'Remera verde militar unisex de algodón peinado 24.1 premium. Ideal para estampar con DTF, serigrafía y bordado. Calce clásico, color resistente y tela que no achica. ¡Envíos a todo el país!', '/v1/assets/catalog/chatgpt-image-4-nov-2025-16_24_57-3e29f8f830cb76f8cf17622843776238-1024-1024.webp', 1, 32
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-305093506-1357039260', NULL, 700000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-305093506-1357039268', NULL, 700000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-305093506-1357039274', NULL, 700000, 4, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-305093506-1357039280', NULL, 700000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-305093506-1357039285', NULL, 700000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON PEINADO 24.1 - VERDE MILITAR' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX ALGODON SUPER CARDADO - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO', 'Remera negra unisex de algodón cardado. La mejor relación precio-calidad para campañas, eventos o remeras promocionales. Apta para DTF y serigrafía. ¡Envíos a todo el país!', '/v1/assets/catalog/supercardado-369f8c147b1191967417096661302242-1024-1024.webp', 1, 33
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-203466018-930896874', NULL, 470000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-203466018-930896876', NULL, 470000, 9, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-203466018-930896879', NULL, 470000, 8, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-203466018-930896882', NULL, 470000, 17, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-203466018-930896886', NULL, 470000, 10, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX ALGODON SUPER CARDADO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX JERSEY DEPORTIVO - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO', 'Remera blanca de jersey deportivo para sublimación. Tela técnica de secado rápido y excelente tacto, ideal para indumentaria deportiva o promocional. Máxima calidad de transferencia.', '/v1/assets/catalog/jersey-unisex-blanco-e409d888af4a97d8e517612311296521-1024-1024.webp', 1, 34
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-213218379-940621104', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-213218379-940621105', NULL, 350000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-213218379-940621106', NULL, 350000, 6, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-213218379-940621107', NULL, 350000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-213218379-940621108', NULL, 350000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX JERSEY DEPORTIVO - NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO', 'Poliester técnico, flexible y de secado ultrarrápido. Usado en ropa deportiva profesional y prendas de alto rendimiento. Gran fidelidad de colores y resist...', '/v1/assets/catalog/chatgpt-image-6-abr-2026-16_53_23-aac5aba78fe2af559117755052115418-1024-1024.webp', 1, 35
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-335395358-1491729158', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-335395358-1491729166', NULL, 350000, 1, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-335395358-1491729174', NULL, 350000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-335395358-1491729185', NULL, 350000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-335395358-1491729197', NULL, 350000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-335395358-1491731063', NULL, 400000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-335395358-1491731067', NULL, 400000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX JERSEY DEPORTIVO - NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX MODAL - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX MODAL - BLANCO', 'Remera blanca unisex de modal de alta calidad. Tela suave, elástica y con el blanco ideal para lograr sublimaciones con colores vibrantes y definidos. Un básico esencial para personalizadores.', '/v1/assets/catalog/chatgpt-image-6-may-2026-09_50_16-5c6ea299c5907246b717780718270914-1024-1024.webp', 1, 36
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-287094572-1285189336', NULL, 500000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-287094572-1285189341', NULL, 500000, 8, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-287094572-1285189345', NULL, 500000, 15, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-287094572-1285189350', NULL, 500000, 10, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-287094572-1285189352', NULL, 500000, 14, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA UNISEX MODAL - GRIS MELANGE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA UNISEX MODAL - GRIS MELANGE', 'Remera gris melange unisex confeccionada en modal de alta gama. Tela suave, con caída y gran elasticidad. Base ideal para sublimación con resultados duraderos y tacto imperceptible.', '/v1/assets/catalog/remera-adulto-gris1-333915695376af2a5616559130275977-1024-1024.webp', 1, 37
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-84065495-326879602', NULL, 450000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-84065495-326879604', NULL, 450000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-84065495-326879607', NULL, 450000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-84065495-326879610', NULL, 450000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-84065495-326879611', NULL, 450000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-84065495-517496817', NULL, 600000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-84065495-517496818', NULL, 600000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA UNISEX MODAL - GRIS MELANGE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERAS: ¿QUÉ TELA ES MEJOR PARA ESTAMPAR?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERAS: ¿QUÉ TELA ES MEJOR PARA ESTAMPAR?', 'Elegir la tela adecuada es el primer paso para lograr un buen resultado al estampar remeras. No todas se comportan igual: algunas absorben mejor la tinta, ot...', '/v1/assets/catalog/0d810299-dd7d-4111-9f63-30916a0aeb41-698cdd6ffd5bc4f81d17561442192493-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERAS: ¿QUÉ TELA ES MEJOR PARA ESTAMPAR?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289767565-1296389342', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = 'REMERAS: ¿QUÉ TELA ES MEJOR PARA ESTAMPAR?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- RESMA A4 AUTOR 90G - 500 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'RESMA A4 AUTOR 90G - 500 HOJAS', 'Comprá online RESMA A4 AUTOR 90G - 500 HOJAS por $9.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/6c91af91-4022-4937-9cd2-2773739e0e49-22cee8a700f3939c3817848208679188-1024-1024.webp', 1, 84
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'RESMA A4 AUTOR 90G - 500 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-357229927-1564227416', NULL, 900000, 3, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'RESMA A4 AUTOR 90G - 500 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- RESMA A4 BOREAL 80G - 500 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'RESMA A4 BOREAL 80G - 500 HOJAS', 'Comprá online RESMA A4 BOREAL 80G - 500 HOJAS por $8.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/a4-resma-boreal-500-hojas-80g1-72502bfdce6671315b16031328381685-1024-1024.webp', 1, 85
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'RESMA A4 BOREAL 80G - 500 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-66210423-881232838', NULL, 800000, 17, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'RESMA A4 BOREAL 80G - 500 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- RESMA A4 PAMPA 70G 500 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'RESMA A4 PAMPA 70G 500 HOJAS', 'PAPEL A4 PAMPA 500 HOJAS 70G Para todo tipo de fotocopiadoras e impresoras. Tamaño: A4 (210 mm x 297 mm) Cantidad: 500 hojas Gramaje: 70g', '/v1/assets/catalog/resma-papel-pampa-a4-70g1-74530afa66a37603d014952032375115-1024-1024.webp', 1, 86
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'RESMA A4 PAMPA 70G 500 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-19388495-881231955', NULL, 650000, 17, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'RESMA A4 PAMPA 70G 500 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- RESMA A4 PAMPA 75G - 500 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'RESMA A4 PAMPA 75G - 500 HOJAS', 'Para todo tipo de fotocopiadoras e impresoras. Tamaño: A4 (210 mm x 297 mm) Cantidad: 500 hojas Gramaje: 75g', '/v1/assets/catalog/resma-papel-pampa-a4-751-f46aa58c8a9819b0e416178114416825-1024-1024.webp', 1, 87
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'RESMA A4 PAMPA 75G - 500 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-22863907-881232372', NULL, 700000, 17, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'RESMA A4 PAMPA 75G - 500 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ROMPECABEZAS 6 PIEZAS MADERA CRISTAL - CR14
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ROMPECABEZAS 6 PIEZAS MADERA CRISTAL - CR14', 'Dimensiones: 20 cm alto x 29 cm de ancho Espesor : 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de estampado: completo Super...', '/v1/assets/catalog/rompecabeza-6-5fa41c0292143f012417674497594001-1024-1024.webp', 1, 61
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ROMPECABEZAS 6 PIEZAS MADERA CRISTAL - CR14' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR14', NULL, 230000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'ROMPECABEZAS 6 PIEZAS MADERA CRISTAL - CR14' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- SACABOCADOS REDONDO 38MM ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'SACABOCADOS REDONDO 38MM ADORIE', 'Grandes formas para ideas grandes. Ideales para proyectos que necesitan presencia: carteles, decoración, packaging creativo o composiciones en capas. Firmes,...', '/v1/assets/catalog/chatgpt-image-29-jul-2026-05_11_12-p-m-c88e73a50a89fcc35517853558833518-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'SACABOCADOS REDONDO 38MM ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CIRC38MM', NULL, 1400000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'SACABOCADOS REDONDO 38MM ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- SACABOCADOS REDONDO 50MM ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'SACABOCADOS REDONDO 50MM ADORIE', 'Grandes formas para ideas grandes. Ideales para proyectos que necesitan presencia: carteles, decoración, packaging creativo o composiciones en capas. Firmes,...', '/v1/assets/catalog/sacabocado-rendondo-50mm-9cfc1409c3b671aeef17720474016181-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'SACABOCADOS REDONDO 50MM ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CIRC50MM', NULL, 1900000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'SACABOCADOS REDONDO 50MM ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TATETI MADERA CRISTAL - CR17
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TATETI MADERA CRISTAL - CR17', 'incluye 3 fichas sublimables cuadradas y 3 redondas Dimensiones: 14.5 cm alto x 14.5 cm de ancho Espesor : 2.5 mm Color: Blanco Temperatura y tiempo de sub...', '/v1/assets/catalog/tateti-aec4fe5367c09aa73a17279865203164-1024-1024.webp', 1, 62
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TATETI MADERA CRISTAL - CR17' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR17', NULL, 170000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TATETI MADERA CRISTAL - CR17' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TATUFAN ARTJET - 1 HOJA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TATUFAN ARTJET - 1 HOJA', 'Tatufan A4 Kit especial para crear tatuajes temporales y decoración de uñas, compuesto por un papel imprimible Inkjet y un papel de transferencia. Permite ob...', '/v1/assets/catalog/tatufan1-9d9dd9723f605cb8d717541628275269-1024-1024.jpg', 1, 88
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TATUFAN ARTJET - 1 HOJA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '1 Hoja', 'TN-285343361-1321388155', NULL, 400000, 7, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'TATUFAN ARTJET - 1 HOJA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TATUFAN ARTJET - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TATUFAN ARTJET - 10 HOJAS', 'Tatufan A4Kit especial para crear tatuajes temporales y decoración de uñas, compuesto por un papel imprimible Inkjet y un papel de transferencia. Permite obt...', '/v1/assets/catalog/tatufan1-88e357314de0efd8bc17847261901531-1024-1024.jpg', 1, 89
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TATUFAN ARTJET - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '10 Hojas', 'TN-356946746-1563305373', NULL, 2770000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'TATUFAN ARTJET - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TATUFAN: ¿QUÉ ES Y CÓMO USARLO?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TATUFAN: ¿QUÉ ES Y CÓMO USARLO?', '🔹 ¿Qué es Tatufan? El Tatufan es un papel especial de transferencia que te permite hacer tatuajes temporales personalizados. Se aplica sobre la piel ...', '/v1/assets/catalog/whatsapp-image-2025-08-25-at-3-22-08-pm-014dff5cd3daf0b23217561462533202-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TATUFAN: ¿QUÉ ES Y CÓMO USARLO?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289785315-1296439573', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = 'TATUFAN: ¿QUÉ ES Y CÓMO USARLO?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA CERAMICA GLITTER COMBINADA ROSA/CELESTE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA CERAMICA GLITTER COMBINADA ROSA/CELESTE', '9,5 cm de alto x 8 cm de diámetro4 mm de espesorSon sublimables', '/v1/assets/catalog/glitter-degrade-rosa-celeste-1-b762745968323f253617634029385818-1024-1024.webp', 1, 63
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA CERAMICA GLITTER COMBINADA ROSA/CELESTE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-290349324-1298338061', NULL, 500000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA CERAMICA GLITTER COMBINADA ROSA/CELESTE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA CERAMICA IMPORTADA AAA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA CERAMICA IMPORTADA AAA', 'Comprá online TAZA CERAMICA IMPORTADA AAA por $4.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/taza-import-cbd98c3e88c2b008ae17111552871269-1024-1024.webp', 1, 64
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA CERAMICA IMPORTADA AAA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'AAA', NULL, 400000, 140, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA CERAMICA IMPORTADA AAA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA CERAMICA INTERIOR NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA CERAMICA INTERIOR NEGRO', '9,5 cm de alto x 8 cm de diámetro 4 mm de espesor', '/v1/assets/catalog/interior-asa-negro-933f3d9a72aaa297b717683313675384-1024-1024.webp', 1, 65
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA CERAMICA INTERIOR NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-307785429-1367881046', NULL, 400000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA CERAMICA INTERIOR NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA CERAMICA INTERIOR ROJO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA CERAMICA INTERIOR ROJO', '9,5 cm de alto x 8 cm de diámetro 4 mm de espesor', '/v1/assets/catalog/interior-asa-roja-b11ab20d644b63fb2117849003719759-1024-1024.webp', 1, 66
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA CERAMICA INTERIOR ROJO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-266852459-1184108680', NULL, 400000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA CERAMICA INTERIOR ROJO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA MÁGICA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA MÁGICA', 'Comprá online TAZA MÁGICA por $5.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/taza-magica1-306909d1aba97ef3de16830394058236-1024-1024.webp', 1, 67
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA MÁGICA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-137374668-534353911', NULL, 580000, 41, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA MÁGICA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA MÁGICA INTERIOR NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA MÁGICA INTERIOR NEGRO', 'Comprá online TAZA MÁGICA INTERIOR NEGRO por $6.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/taza-magica-int-color-negro-d65720c0a86e15288717849002933329-1024-1024.webp', 1, 68
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA MÁGICA INTERIOR NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-307781556-1367866809', NULL, 600000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA MÁGICA INTERIOR NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA MIMO POLIMERO SUBLIMABLE WORKAT
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA MIMO POLIMERO SUBLIMABLE WORKAT', 'Altura : 8cm Apilables *NOS ENCANTA QUE TE ENCANTE*', '/v1/assets/catalog/mimo-blanca-1-b1e34a114eb62b8a7b17840470085235-1024-1024.webp', 1, 69
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA MIMO POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355519819-1558694021', NULL, 160000, 67, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA MIMO POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA MUCHI POLIMERO SUBLIMABLE WORKAT
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA MUCHI POLIMERO SUBLIMABLE WORKAT', 'Altura : 8cm Apilables *NOS ENCANTA QUE TE ENCANTE*', '/v1/assets/catalog/chatgpt-image-8-jun-2026-11_30_35-a-m-077c772ec4831be69617809291168269-1024-1024.webp', 1, 70
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA MUCHI POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-349103421-1539363183', NULL, 160000, 47, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA MUCHI POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA POLIMERO SUBLIMABLE WORKAT
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA POLIMERO SUBLIMABLE WORKAT', 'Altura: 8cmApilables*NOS ENCANTA QUE TE ENCANTE*todos los productos Workat son libres de BPA', '/v1/assets/catalog/apilable-workat1-1d037deda78bc62e9916505573779997-1024-1024.webp', 1, 71
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'KKK', NULL, 90000, 323, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA POLIMERO SUBLIMABLE WORKAT' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TAZA POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TAZA POLYMER MUG SUBLIMABLE', 'Comprá online TAZA POLYMER MUG SUBLIMABLE por $1.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/tazaa-recta-polimero-a0a2f010995909b78917473143683559-1024-1024.webp', 1, 72
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TAZA POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'MUG', NULL, 160000, 34, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TAZA POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TELA TEFLONADA 40X40
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TELA TEFLONADA 40X40', 'Comprá online TELA TEFLONADA 40X40 por $11.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/tela-teflonda1-6e01290c87bcc4aaf015544675502282-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TELA TEFLONADA 40X40' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563956-87344479', NULL, 1100000, 8, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TELA TEFLONADA 40X40' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TELA TEFLONADA 60X40
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TELA TEFLONADA 60X40', 'Comprá online TELA TEFLONADA 60X40 por $16.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/tela-teflonda11-6e01290c87bcc4aaf015544676415623-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TELA TEFLONADA 60X40' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34563960-87344483', NULL, 1600000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TELA TEFLONADA 60X40' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TERMO BAMBU SUBIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TERMO BAMBU SUBIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_44_54-c04bd78f418700ce3917843103721796-1024-1024.webp', 1, 73
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TERMO BAMBU SUBIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356131954-1560791458', NULL, 1120000, 11, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TERMO BAMBU SUBIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TERMO LECHERO SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TERMO LECHERO SUBLIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_45_03-a4f6aa9e32ac8f628317843104047425-1024-1024.webp', 1, 74
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TERMO LECHERO SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356131527-1560790338', NULL, 1050000, 5, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TERMO LECHERO SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TERMO MATERO POLYMER MUG SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TERMO MATERO POLYMER MUG SUBLIMABLE', 'Comprá online TERMO MATERO POLYMER MUG SUBLIMABLE por $9.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/termo-a109861eb4a635d65617845607290510-1024-1024.webp', 1, 75
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TERMO MATERO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356484200-1562047066', NULL, 900000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TERMO MATERO POLYMER MUG SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TERMO TAZA SUBLIMABLE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TERMO TAZA SUBLIMABLE', 'Aluminio sublimable', '/v1/assets/catalog/chatgpt-image-17-jul-2026-14_44_56-e88aa3abfd234286d517843103885063-1024-1024.webp', 1, 76
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TERMO TAZA SUBLIMABLE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-356131793-1560791083', NULL, 1250000, 9, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'TERMO TAZA SUBLIMABLE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TIJERA GOLD DIARIA ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TIJERA GOLD DIARIA ADORIE', 'Compacta, elegante y funcional, es perfecta para tener siempre a mano y resolver cortes con prolijidad y estilo. ✂️ 16 cm de largo✨ Filo...', '/v1/assets/catalog/chatgpt-image-25-feb-2026-16_32_02-53febd17a1729497cf17720492957678-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TIJERA GOLD DIARIA ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TIJ5503', NULL, 350000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TIJERA GOLD DIARIA ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TIJERA LAVANDA ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TIJERA LAVANDA ADORIE', 'Delicada, cómoda y versátil, es perfecta para quienes buscan precisión con un toque de color y encanto. ✂️ 17 cm de largo💜 Detalle lavan...', '/v1/assets/catalog/chatgpt-image-13-mar-2026-12_15_52-1e209ca2e9a7a5830117734149604109-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TIJERA LAVANDA ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TIJ9906', NULL, 200000, 8, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TIJERA LAVANDA ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TIJERA OLIVA DIARIA ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TIJERA OLIVA DIARIA ADORIE', 'De diseño delicado y espíritu natural, combina precisión y comodidad para acompañarte en tus momentos creativos y tareas diarias. ✂️ 21,5 cm de ...', '/v1/assets/catalog/chatgpt-image-25-feb-2026-16_32_51-5fc88580ff775baacb17720491745848-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TIJERA OLIVA DIARIA ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TIJ9908', NULL, 230000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TIJERA OLIVA DIARIA ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC AMARILLO', 'Comprá online TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC AMARILLO por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/comercial-yellow1-e0234e7caa46cc0fd516928067435938-1024-1024.webp', 1, 0
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-180774530-698234586', NULL, 300000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC CIAN', 'Comprá online TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC CIAN por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/comercial-cyan1-46766f2bb3636834d916928069064225-1024-1024.webp', 1, 1
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-180774896-698236521', NULL, 300000, 11, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC MAGENTA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC MAGENTA', 'Comprá online TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC MAGENTA por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/comercial-magenta1-80393059854f7eeca816928069571128-1024-1024.webp', 1, 2
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC MAGENTA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-180775068-698237067', NULL, 300000, 10, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC MAGENTA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC NEGRO', 'Comprá online TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC NEGRO por $3.000. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/comercial-negro1-e47b351edafd92c5ab16928066619781-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-180774238-698233504', NULL, 300000, 19, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET COMERCIAL 100CC NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC AMARILLO', '', '/v1/assets/catalog/artjet-profesional-amarillo-x1001-f0023a44cc1f1e4a3c16615185122252-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-130800105-512486720', NULL, 800000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT CIAN', '', '/v1/assets/catalog/artjet-profesional-cyan-claro-x100-04dccb6796bc0426de17661659421968-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-314222087-1391646404', NULL, 800000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT MAGENTA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT MAGENTA', '', '/v1/assets/catalog/artjet-profesional-magenta-claro-x100-1a128787ba92b0d19717661659238883-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT MAGENTA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-314222163-1391646545', NULL, 800000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC LIGHT MAGENTA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA SUBLIMACION ARTJET 100CC AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA SUBLIMACION ARTJET 100CC AMARILLO', '', '/v1/assets/catalog/artjet-sublimacion-amarillo1-5eb11efd1e8ee7a70a16747405755847-1024-1024.webp', 1, 7
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA SUBLIMACION ARTJET 100CC AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'SARA', NULL, 700000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA SUBLIMACION ARTJET 100CC AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA SUBLIMACION ARTJET 100CC CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA SUBLIMACION ARTJET 100CC CIAN', '', '/v1/assets/catalog/artjet-sublimacion-cyan1-ac079e89311b388ca915902596794775-1024-1024.webp', 1, 8
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA SUBLIMACION ARTJET 100CC CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'SARC', NULL, 700000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA SUBLIMACION ARTJET 100CC CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA SUBLIMACION ARTJET 100CC MAGENTA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA SUBLIMACION ARTJET 100CC MAGENTA', '', '/v1/assets/catalog/artjet-sublimacion-magenta1-6778a1c083920c224a15902597242944-1024-1024.webp', 1, 9
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA SUBLIMACION ARTJET 100CC MAGENTA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'SARM', NULL, 700000, 14, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA SUBLIMACION ARTJET 100CC MAGENTA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA SUBLIMACION ARTJET 100CC NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA SUBLIMACION ARTJET 100CC NEGRO', '', '/v1/assets/catalog/artjet-fotografico-profesional-negro1-35bae2b215ed21e74c15902595331336-1024-1024.webp', 1, 10
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA SUBLIMACION ARTJET 100CC NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'SARN', NULL, 700000, 22, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA SUBLIMACION ARTJET 100CC NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTAS ART-JET: ¿CUÁL ES LA MEJOR PARA VOS?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTAS ART-JET: ¿CUÁL ES LA MEJOR PARA VOS?', 'Las tintas Art‑Jet están diseñadas para adaptarse a diferentes necesidades de calidad, resistencia y rendimiento. Te contamos sus diferencias para que ...', '/v1/assets/catalog/imagen-de-whatsapp-2025-08-25-a-las-13-14-33_41aca659-5665fb01aaec8b1d4f17561385254478-1024-1024.webp', 1, 3
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTAS ART-JET: ¿CUÁL ES LA MEJOR PARA VOS?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289755434-1296358465', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = 'TINTAS ART-JET: ¿CUÁL ES LA MEJOR PARA VOS?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TRANSFER DURALITE PRENDAS OSCURAS A4 ARTJET - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TRANSFER DURALITE PRENDAS OSCURAS A4 ARTJET - 10 HOJAS', 'PAPEL TRANSFER PARA TELAS OSCURAS ARTJET - 10 HOJAS Papel transfer para telas oscuras Caracteristicas Tamaño: A4 (210x297mm) Cantidad: 1 hoja o 10 hojas Se...', '/v1/assets/catalog/transfer-duralite1-6cf910992045ddc11c17464590850773-1024-1024.jpg', 1, 90
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TRANSFER DURALITE PRENDAS OSCURAS A4 ARTJET - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-268580986-1522860300', NULL, 2130000, 4, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'TRANSFER DURALITE PRENDAS OSCURAS A4 ARTJET - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- VASO CON PICO POLYMER MUG SUBLIMABLE - AZUL
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'VASO CON PICO POLYMER MUG SUBLIMABLE - AZUL', 'Vaso con Pico antivuelco. Dimensiones: 13,5 cm de alto x 8 cm de diámetro Capacidad: 400cc Espesor: 3 mm Color: Blanco , Tapa Rosa Temperatura y tiempo de su...', '/v1/assets/catalog/vaso-con-pico-azul-bd873429ad89183edd17386764717726-1024-1024.webp', 1, 77
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'VASO CON PICO POLYMER MUG SUBLIMABLE - AZUL' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-253663484-1120108989', NULL, 400000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'VASO CON PICO POLYMER MUG SUBLIMABLE - AZUL' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- VASO CON PICO POLYMER MUG SUBLIMABLE ROSA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'VASO CON PICO POLYMER MUG SUBLIMABLE ROSA', 'Vaso con Pico antivuelco. Dimensiones: 13,5 cm de alto x 8 cm de diámetro Capacidad: 400cc Espesor: 3 mm Color: Blanco , Tapa Rosa Temperatura y tiempo de su...', '/v1/assets/catalog/vaso-con-pico-rosa-0562f4111ad496d9b517845612584464-1024-1024.webp', 1, 78
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'VASO CON PICO POLYMER MUG SUBLIMABLE ROSA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-34564050-1120107063', NULL, 400000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'VASO CON PICO POLYMER MUG SUBLIMABLE ROSA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- VINILO SUBLIMABLE SUBLISTICK BLANCO AUTOADHESIVO A4 ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'VINILO SUBLIMABLE SUBLISTICK BLANCO AUTOADHESIVO A4 ARTJET', 'Sublistick Blanco A4Vinilo sublimable con superficie blanca brillante, ideal para transferir diseños con colores vivos y máxima definición. Perfecto para pro...', '/v1/assets/catalog/sublistick-blanco-1-d0c4e1557803546c3817576917214442-1024-1024.webp', 1, 91
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'VINILO SUBLIMABLE SUBLISTICK BLANCO AUTOADHESIVO A4 ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'TN-293621432-1321134993', NULL, 1060000, 30, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'VINILO SUBLIMABLE SUBLISTICK BLANCO AUTOADHESIVO A4 ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- VINILO SUBLIMABLE SUBLISTICK TRANSPARENTE AUTOADHESIVO A4 ARTJET
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'VINILO SUBLIMABLE SUBLISTICK TRANSPARENTE AUTOADHESIVO A4 ARTJET', 'Sublistick Transparente A4Vinilo sublimable con acabado transparente, diseñado para transferir imágenes con gran definición y colores intensos, manteniendo a...', '/v1/assets/catalog/sublistick-transparente-1-28902fe22376be614316987643952541-1024-1024.webp', 1, 92
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'VINILO SUBLIMABLE SUBLISTICK TRANSPARENTE AUTOADHESIVO A4 ARTJET' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'TN-189018356-1321141151', NULL, 1060000, 6, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'VINILO SUBLIMABLE SUBLISTICK TRANSPARENTE AUTOADHESIVO A4 ARTJET' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- WINKY BLANCO A4 ARTJET - 1 HOJA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'WINKY BLANCO A4 ARTJET - 1 HOJA', 'UNA EXPERIENCIA DE TRANSFORMACIÓN Imagina un material que convierte tus ideas en realidad con un toque de calor, un papel que se encoge y se moldea para da...', '/v1/assets/catalog/winky-blanco-ef283ab3fd3610998716999723184323-1024-1024.webp', 1, 93
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'WINKY BLANCO A4 ARTJET - 1 HOJA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-190808073-881241994', NULL, 200000, 13, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'WINKY BLANCO A4 ARTJET - 1 HOJA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- WINKY BLANCO A4 ARTJET - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'WINKY BLANCO A4 ARTJET - 10 HOJAS', 'UNA EXPERIENCIA DE TRANSFORMACIÓN Imagina un material que convierte tus ideas en realidad con un toque de calor, un papel que se encoge y se moldea para da...', '/v1/assets/catalog/winky-blanco-de76df511ecfae7a0817853328243957-1024-1024.jpg', 1, 94
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'WINKY BLANCO A4 ARTJET - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-358221350-1567330699', NULL, 1470000, 2, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'WINKY BLANCO A4 ARTJET - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- WINKY SEMITRANSPARENTE A4 ARTJET - 1 HOJA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'WINKY SEMITRANSPARENTE A4 ARTJET - 1 HOJA', 'UNA EXPERIENCIA DE TRANSFORMACIÓNImagina un material que convierte tus ideas en realidad con un toque de calor, un papel que se encoge y se moldea para dar v...', '/v1/assets/catalog/winky-semi-b62d27fde048d7818617436850735733-1024-1024.webp', 1, 95
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'WINKY SEMITRANSPARENTE A4 ARTJET - 1 HOJA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-263258602-1167789522', NULL, 200000, 17, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'WINKY SEMITRANSPARENTE A4 ARTJET - 1 HOJA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- WINKY SEMITRANSPARENTE A4 ARTJET - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'WINKY SEMITRANSPARENTE A4 ARTJET - 10 HOJAS', 'UNA EXPERIENCIA DE TRANSFORMACIÓNImagina un material que convierte tus ideas en realidad con un toque de calor, un papel que se encoge y se moldea para dar v...', '/v1/assets/catalog/winky-semi-28c67faf09a0b23f9217853328847023-1024-1024.jpg', 1, 96
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'WINKY SEMITRANSPARENTE A4 ARTJET - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-358221510-1567331383', NULL, 1470000, 1, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'WINKY SEMITRANSPARENTE A4 ARTJET - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ¿ME CONVIENE SUBLIMAR CON TRICAPA O COLORUP?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, '¿ME CONVIENE SUBLIMAR CON TRICAPA O COLORUP?', 'Al elegir un papel de sublimación, la duda más común es si conviene usar Tricapa o ColorUP. Ambos funcionan muy bien, pero tienen diferencias importantes que...', '/v1/assets/catalog/imagen-de-whatsapp-2025-08-25-a-las-13-31-36_ad4d44cc-afe3ed5d65cb6b444517561395193759-1024-1024.webp', 1, 4
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = '¿ME CONVIENE SUBLIMAR CON TRICAPA O COLORUP?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289760542-1296373442', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = '¿ME CONVIENE SUBLIMAR CON TRICAPA O COLORUP?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ¿POR QUÉ ME CONVIENE USAR CINTA TÉRMICA WORKAT?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, '¿POR QUÉ ME CONVIENE USAR CINTA TÉRMICA WORKAT?', 'La cinta térmica es fundamental en sublimación: mantiene el papel fijo durante el planchado para que el diseño no se mueva y el resultado salga perfecto. La ...', '/v1/assets/catalog/imagen-de-whatsapp-2025-08-25-a-las-14-18-24_46edf7ad-f37b39a8fae3d25ff117561423176312-1024-1024.webp', 1, 5
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = '¿POR QUÉ ME CONVIENE USAR CINTA TÉRMICA WORKAT?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289774091-1296408246', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = '¿POR QUÉ ME CONVIENE USAR CINTA TÉRMICA WORKAT?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ¿QUÉ ES EL SUBLISTICK?
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, '¿QUÉ ES EL SUBLISTICK?', '¿Qué es el Sublistick? El Sublistick es un vinilo autoadhesivo especialmente diseñado para sublimación. A diferencia de otros adhesivos, este necesita un pro...', '/v1/assets/catalog/imagen-de-whatsapp-2025-08-25-a-las-15-17-10_2cb890de-816747f4208323740517561458428952-1024-1024.webp', 1, 6
FROM categories c
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = '¿QUÉ ES EL SUBLISTICK?' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-289787035-1296444823', NULL, 0, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'APRENDE' COLLATE NOCASE
  AND p.name = '¿QUÉ ES EL SUBLISTICK?' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ATRIL MADERA - CR20
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ATRIL MADERA - CR20', 'Atril exhibidor - no sublimable', '/v1/assets/catalog/atril1-0cbbc9413681df70ea15544705246066-1024-1024.webp', 1, 79
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ATRIL MADERA - CR20' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR20', NULL, 60000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'ATRIL MADERA - CR20' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - MARINO MARINO MARINO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - MARINO MARINO MARINO', 'Comprá online GORRA TRUCKER - MARINO MARINO MARINO por $2.800. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/trucker-marino-abfe1eaf1dd85884fe17635704133763-1024-1024.webp', 1, 17
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - MARINO MARINO MARINO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'MAMAMA', NULL, 280000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - MARINO MARINO MARINO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- GORRA TRUCKER - NEGRO BLANCO NEGRO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'GORRA TRUCKER - NEGRO BLANCO NEGRO', 'vicera y red negro - frente blanco', '/v1/assets/catalog/trucker-negro-blanco1-64525470aa48fbee4715544701587166-1024-1024.webp', 1, 18
FROM categories c
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'GORRA TRUCKER - NEGRO BLANCO NEGRO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'NBN', NULL, 280000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'GORRAS' COLLATE NOCASE
  AND p.name = 'GORRA TRUCKER - NEGRO BLANCO NEGRO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- HOLOFAN ADHESIVO A4 MUNDO TORNASOL ARTJET - H13
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'HOLOFAN ADHESIVO A4 MUNDO TORNASOL ARTJET - H13', '? Qué es Holofan Art-Jet Holofan es una línea de tramas adhesivas holográficas de la marca Art-Jet, diseñada para elevar tus proyectos de impresión con efect...', '/v1/assets/catalog/mundo-tornasol-b84dca903addd19cfd169893476492031-c538e3240df39a3c4716989347833854-1024-1024.jpg', 1, 97
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'HOLOFAN ADHESIVO A4 MUNDO TORNASOL ARTJET - H13' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, '20 Hojas', 'H13-20', NULL, 1200000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'HOLOFAN ADHESIVO A4 MUNDO TORNASOL ARTJET - H13' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- MATELINA TEXTURADO A4 230G TRAMADO DE ALGODON ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'MATELINA TEXTURADO A4 230G TRAMADO DE ALGODON ARTJET - 50 HOJAS', 'Comprá online MATELINA TEXTURADO A4 230G TRAMADO DE ALGODON ARTJET - 50 HOJAS por $11.600. Hacé tu pedido y pagalo online.', '/v1/assets/catalog/matelina-230g-tramado-de-algodon-50-hojas-650002f34810c6c06a17576908936710-1024-1024.webp', 1, 98
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'MATELINA TEXTURADO A4 230G TRAMADO DE ALGODON ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-293616432-1312346470', NULL, 1160000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'MATELINA TEXTURADO A4 230G TRAMADO DE ALGODON ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO DOBLE FAZ A4 240G ARTJET - 100 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO DOBLE FAZ A4 240G ARTJET - 100 HOJAS', 'Papel fotográfico brillante (Glossy) Doble Faz tamaño A4 de 240g ArtJet. Secado instantáneo en ambas caras y máxima rigidez. Ideal para tarjetas, invitaciones y menús premium.', '/v1/assets/catalog/a4-artjet-240g-doble-faz-100-hojas-76ad08a9a9478d9e2d17111576456968-1024-1024.webp', 1, 99
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO DOBLE FAZ A4 240G ARTJET - 100 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', '240A4100', NULL, 1950000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO DOBLE FAZ A4 240G ARTJET - 100 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CUERINA CLASICA ARTJET - 50 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CUERINA CLASICA ARTJET - 50 HOJAS', 'Papel fotográfico con textura Cuerina Clásica de 230g para impresoras inkjet. Formato A4, ideal para cubiertas de fotolibros, diplomas y tarjetería con un acabado sofisticado y táctil.', '/v1/assets/catalog/cuerina-clasica-7c55151dad02fb1a4a17098080684097-1024-1024.webp', 1, 100
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CUERINA CLASICA ARTJET - 50 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-203714224-880973944', NULL, 1160000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'PAPEL FOTOGRAFICO TEXTURADO A4 230G CANVAS CUERINA CLASICA ARTJET - 50 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PLACA TROFEO RECTANGULOS MADERA CRISTAL - CR218D
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PLACA TROFEO RECTANGULOS MADERA CRISTAL - CR218D', 'Medidas 10 x 16,5 cm', '/v1/assets/catalog/placa-trofeo-rectangulo-822d27428a432737cb17211424546984-1024-1024.webp', 1, 80
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PLACA TROFEO RECTANGULOS MADERA CRISTAL - CR218D' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR218D', NULL, 200000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PLACA TROFEO RECTANGULOS MADERA CRISTAL - CR218D' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- PORTA SAHUMERIO MADERA CRISTAL - CR93
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'PORTA SAHUMERIO MADERA CRISTAL - CR93', '26 cm x 6 cm', '/v1/assets/catalog/porta-sahumerio1-c0090d81dab44ee3bc15792066414864-1024-1024.webp', 1, 81
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'PORTA SAHUMERIO MADERA CRISTAL - CR93' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR93', NULL, 130000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'PORTA SAHUMERIO MADERA CRISTAL - CR93' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- REMERA MUJER MODAL - BLANCO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'REMERA MUJER MODAL - BLANCO', 'Remera blanca para mujer de modal premium. Tela suave, elástica y con excelente caída, ideal para sublimación de alta calidad. Calce femenino y cómodo para uso diario o emprendimientos.', '/v1/assets/catalog/remera-blanca-mujer1-541ae88053f04a60e216626525870181-1024-1024.webp', 1, 38
FROM categories c
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 1', 'TN-61284439-268340442', NULL, 350000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 2', 'TN-61284439-267960600', NULL, 350000, 0, 0, 1, 1, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 3', 'TN-61284439-267960601', NULL, 350000, 0, 0, 1, 2, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 4', 'TN-61284439-268340443', NULL, 350000, 0, 0, 1, 3, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 5', 'TN-61284439-267960602', NULL, 350000, 0, 0, 1, 4, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 6', 'TN-61284439-1484817635', NULL, 450000, 0, 0, 1, 5, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Talle 8', 'TN-61284439-1484817637', NULL, 450000, 0, 0, 1, 6, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'REMERAS' COLLATE NOCASE
  AND p.name = 'REMERA MUJER MODAL - BLANCO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- ROMPECABEZAS 30 PIEZAS MADERA CRISTAL - CR16
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'ROMPECABEZAS 30 PIEZAS MADERA CRISTAL - CR16', 'Dimensiones: 20 cm alto x 29 cm de ancho Espesor : 2.5 mm Color: Blanco Temperatura y tiempo de sublimación: 180° 30 seg Área de estampado: completo Super...', '/v1/assets/catalog/rompecabeza-301-001c153444a52ae1dc15544888024116-1024-1024.webp', 1, 82
FROM categories c
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'ROMPECABEZAS 30 PIEZAS MADERA CRISTAL - CR16' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'CR16', NULL, 460000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'SUBLIMABLES' COLLATE NOCASE
  AND p.name = 'ROMPECABEZAS 30 PIEZAS MADERA CRISTAL - CR16' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TIJERA BOTANICA ADORIE
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TIJERA BOTANICA ADORIE', 'En el Atelier donde cada hoja encuentra su lugar perfecto… nacen las Grampas 26/06 📎✨ Pensadas para el ritmo cotidiano, combinan practicidad y...', '/v1/assets/catalog/gemini_generated_image_r23ti4r23ti4r23t-b0646a34858a3ffdac17746267549588-1024-1024.webp', 1, 12
FROM categories c
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TIJERA BOTANICA ADORIE' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TIJ6608', NULL, 300000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'ACCESORIOS' COLLATE NOCASE
  AND p.name = 'TIJERA BOTANICA ADORIE' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC AMARILLO
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC AMARILLO', 'La tinta Eternity es una tinta Dye (tinta líquida a base de colorantes). Se usa en impresoras de inyección de tinta (inkjet). Protege tus fotos del sol y d...', '/v1/assets/catalog/eternity-am-bc9e35e15a2d99b26017840474290090-1024-1024.webp', 1, 11
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC AMARILLO' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355521105-1558697897', NULL, 1190000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC AMARILLO' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC CIAN', 'La tinta Eternity es una tinta Dye (tinta líquida a base de colorantes). Se usa en impresoras de inyección de tinta (inkjet). Protege tus fotos del sol y d...', '/v1/assets/catalog/eternity-70-c-6160f04dc96b462d8417840473897039-1024-1024.webp', 1, 12
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355521023-1558697561', NULL, 1190000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC MAGENTA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC MAGENTA', 'La tinta Eternity es una tinta Dye (tinta líquida a base de colorantes). Se usa en impresoras de inyección de tinta (inkjet). Protege tus fotos del sol y d...', '/v1/assets/catalog/eternity-ma-1082412a3e75ba93a817840474131402-1024-1024.webp', 1, 13
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC MAGENTA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-355521058-1558697677', NULL, 1190000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 100CC MAGENTA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 70CC CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 70CC CIAN', 'Eternity ofrece mayor resistencia anti oxidación y máxima proteción a los rayos UV manteniendo una alta calidad en sus colores a lo largo del tiempo Unica b...', '/v1/assets/catalog/eternity-70-c-6070feae53549593a117532789974999-1024-1024.webp', 1, 14
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 70CC CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-283240933-1267273827', NULL, 900000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET ETERNITY GOLD EDITION 70CC CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC CIAN
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC CIAN', '', '/v1/assets/catalog/artjet-profesional-cyan-x1001-1f42d580ec7dbe40e516615184515021-1024-1024.webp', 1, 15
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC CIAN' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-130799577-512485343', NULL, 800000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC CIAN' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC MAGENTA
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC MAGENTA', '', '/v1/assets/catalog/artjet-profesional-magenta-x1001-e8700c3e82ba8f093d16615184839254-1024-1024.webp', 1, 16
FROM categories c
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC MAGENTA' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-130800053-512485543', NULL, 800000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'TINTAS' COLLATE NOCASE
  AND p.name = 'TINTA FOTOGRAFICA ARTJET PROFESIONAL 100CC MAGENTA' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;

-- TRANSFER DURALITE PRENDAS CLARAS A4 ARTJET - 10 HOJAS
INSERT INTO products(category_id, name, description, image_path, active, sort_order)
SELECT c.id, 'TRANSFER DURALITE PRENDAS CLARAS A4 ARTJET - 10 HOJAS', 'PAPEL TRANSFER PARA TELAS CLARAS ARTJET - 10 HOJAS Papel transfer para telas claras Caracteristicas Tamaño: A4 (210x297mm) Cantidad: 1 hoja o 10 hojas Se pue...', '/v1/assets/catalog/artjet-transfer-telas-claras1-3a5d0a8a4684918d5b17557040764380-1024-1024.jpg', 1, 101
FROM categories c
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND NOT EXISTS (
      SELECT 1 FROM products p
      WHERE p.category_id = c.id
        AND p.name = 'TRANSFER DURALITE PRENDAS CLARAS A4 ARTJET - 10 HOJAS' COLLATE NOCASE
  );
INSERT OR IGNORE INTO product_variants(
    product_id, name, sku, barcode, price_cents,
    stock_on_hand, stock_reserved, min_stock, sort_order, active
)
SELECT p.id, 'Única', 'TN-288660572-1522861066', NULL, 1950000, 0, 0, 1, 0, 1
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE c.name = 'PAPELES' COLLATE NOCASE
  AND p.name = 'TRANSFER DURALITE PRENDAS CLARAS A4 ARTJET - 10 HOJAS' COLLATE NOCASE
ORDER BY p.id
LIMIT 1;
