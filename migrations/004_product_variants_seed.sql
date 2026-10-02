--
-- Example data for product_variants — demonstrates the feature using
-- products from 002.commerce_seed.sql.
-- Supports idempotent execution (UPSERT / ON DUPLICATE KEY UPDATE).
--

-- 1. Product Attributes
INSERT INTO product_attributes (id, name) VALUES
(1, 'Barva'),
(2, 'Kapacita'),
(3, 'Délka'),
(4, 'Vazba'),
(5, 'Procesor'),
(6, 'Grafická karta'),
(7, 'Operační systém'),
(8, 'Velikost')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);


-- 2. Attribute Values
INSERT INTO product_attribute_values (id, attribute_id, value) VALUES
-- Barva (attribute 1)
(1, 1, 'Černá'),
(2, 1, 'Bílá'),
(3, 1, 'Modrá'),
(4, 1, 'Stříbrná'),
(5, 1, 'Červená'),
(6, 1, 'Černá matná'),
(7, 1, 'Půlnoční modrá'),
(8, 1, 'Námořnická modrá'),

-- Kapacita (attribute 2)
(9, 2, '128 GB'),
(10, 2, '256 GB'),
(11, 2, '512 GB'),
(12, 2, '1 TB'),

-- Délka (attribute 3)
(13, 3, '1 m'),
(14, 3, '2 m'),
(15, 3, '3 m'),

-- Vazba (attribute 4)
(16, 4, 'Brožovaná'),
(17, 4, 'Pevná vazba'),
(18, 4, 'E-kniha (PDF / EPUB)'),

-- Procesor (attribute 5)
(19, 5, 'AMD Ryzen 7 8845HS'),
(20, 5, 'AMD Ryzen 9 8940HX'),

-- Grafická karta (attribute 6)
(21, 6, 'NVIDIA GeForce RTX 5060 8GB'),
(22, 6, 'NVIDIA GeForce RTX 5070 12GB'),
(23, 6, 'NVIDIA GeForce RTX 5080 16GB'),

-- Operační systém (attribute 7)
(24, 7, 'Windows 11 Home'),
(25, 7, 'Bez operačního systému'),

-- Velikost (attribute 8)
(26, 8, 'S'),
(27, 8, 'M'),
(28, 8, 'L'),
(29, 8, 'XL'),
(30, 8, 'XXL')
ON DUPLICATE KEY UPDATE
    attribute_id = VALUES(attribute_id),
    value = VALUES(value);


-- 3. Product Variants
INSERT INTO product_variants (id, product_id, sku, price, stock, active) VALUES
-- Product 1: USB Kabel (base: 150.00)
(1, 1, 'USB-1M-BLK', NULL, 20, 1),
(2, 1, 'USB-2M-BLK', 220.00, 15, 1),
(3, 1, 'USB-3M-BLK', 290.00, 5, 1),
(4, 1, 'USB-1M-WHT', NULL, 12, 1),
(5, 1, 'USB-2M-WHT', 220.00, 8, 1),
(6, 1, 'USB-1M-RED', 170.00, 0, 1),

-- Product 2: Mobilní telefon XYZ (base: 12500.00)
(7, 2, 'XYZ-128-BLK', NULL, 6, 1),
(8, 2, 'XYZ-128-SLV', NULL, 4, 1),
(9, 2, 'XYZ-256-BLK', 14500.00, 5, 1),
(10, 2, 'XYZ-256-BLU', 14900.00, 3, 1),
(11, 2, 'XYZ-512-BLU', 17900.00, 2, 1),
(12, 2, 'XYZ-512-BLK', 17900.00, 0, 1),

-- Product 3: Kniha PHP Programování (base: 399.00)
(13, 3, 'PHP-BOOK-SOFT', NULL, 15, 1),
(14, 3, 'PHP-BOOK-HARD', 549.00, 10, 1),
(15, 3, 'PHP-BOOK-EBOOK', 249.00, 999, 1),

-- Product 4: Herní notebook ASUS ROG Strix (base: 34990.00)
(16, 4, 'ROG-R7-5060-W11', NULL, 8, 1),
(17, 4, 'ROG-R7-5060-NOOS', 32490.00, 5, 1),
(18, 4, 'ROG-R9-5070-W11', 42990.00, 4, 1),
(19, 4, 'ROG-R9-5080-W11', 54990.00, 2, 1),
(20, 4, 'ROG-R9-5080-NOOS', 52490.00, 0, 1),

-- Product 5: Bezdrátová sluchátka SoundPro (base: 2490.00)
(21, 5, 'SOUND-BLK', NULL, 18, 1),
(22, 5, 'SOUND-SLV', NULL, 12, 1),
(23, 5, 'SOUND-BLU', 2690.00, 5, 1),

-- Product 6: Pánské tričko Classic (base: 490.00)
(24, 6, 'TSHIRT-BLK-S', NULL, 10, 1),
(25, 6, 'TSHIRT-BLK-M', NULL, 15, 1),
(26, 6, 'TSHIRT-BLK-L', NULL, 20, 1),
(27, 6, 'TSHIRT-BLK-XL', NULL, 8, 1),
(28, 6, 'TSHIRT-WHT-M', NULL, 12, 1),
(29, 6, 'TSHIRT-WHT-L', NULL, 14, 1),
(30, 6, 'TSHIRT-BLU-M', 520.00, 6, 1),
(31, 6, 'TSHIRT-BLU-L', 520.00, 0, 1)
ON DUPLICATE KEY UPDATE
    product_id = VALUES(product_id),
    sku = VALUES(sku),
    price = VALUES(price),
    stock = VALUES(stock),
    active = VALUES(active);


-- 4. Variant Attribute Values Mapping (M:N)
INSERT INTO product_variant_values (variant_id, attribute_value_id) VALUES
-- Product 1 (USB Kabel: Délka + Barva)
(1, 13), (1, 1),
(2, 14), (2, 1),
(3, 15), (3, 1),
(4, 13), (4, 2),
(5, 14), (5, 2),
(6, 13), (6, 5),

-- Product 2 (Mobilní telefon XYZ: Kapacita + Barva)
(7, 9),  (7, 1),
(8, 9),  (8, 4),
(9, 10), (9, 1),
(10, 10), (10, 3),
(11, 11), (11, 3),
(12, 11), (12, 1),

-- Product 3 (Kniha PHP: Vazba)
(13, 16),
(14, 17),
(15, 18),

-- Product 4 (ASUS ROG: Procesor + Grafika + OS)
(16, 19), (16, 21), (16, 24),
(17, 19), (17, 21), (17, 25),
(18, 20), (18, 22), (18, 24),
(19, 20), (19, 23), (19, 24),
(20, 20), (20, 23), (20, 25),

-- Product 5 (Sluchátka: Barva)
(21, 6),
(22, 4),
(23, 7),

-- Product 6 (Tričko: Velikost + Barva)
(24, 26), (24, 1),
(25, 27), (25, 1),
(26, 28), (26, 1),
(27, 29), (27, 1),
(28, 27), (28, 2),
(29, 28), (29, 2),
(30, 27), (30, 8),
(31, 28), (31, 8)
ON DUPLICATE KEY UPDATE
    variant_id = VALUES(variant_id),
    attribute_value_id = VALUES(attribute_value_id);
