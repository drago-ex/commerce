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
(4, 'Vazba')
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

-- Kapacita (attribute 2)
(6, 2, '128 GB'),
(7, 2, '256 GB'),
(8, 2, '512 GB'),

-- Délka (attribute 3)
(9, 3, '1 m'),
(10, 3, '2 m'),
(11, 3, '3 m'),

-- Vazba (attribute 4)
(12, 4, 'Brožovaná'),
(13, 4, 'Pevná vazba'),
(14, 4, 'E-kniha (PDF / EPUB)')
ON DUPLICATE KEY UPDATE
    attribute_id = VALUES(attribute_id),
    value = VALUES(value);


-- 3. Product Variants
INSERT INTO product_variants (id, product_id, sku, price, stock, active) VALUES
-- Product 1: USB Kabel (base price: 150.00)
(1, 1, 'USB-1M-BLK', NULL, 20, 1),
(2, 1, 'USB-2M-BLK', 220.00, 15, 1),
(3, 1, 'USB-3M-BLK', 290.00, 5, 1),
(4, 1, 'USB-1M-WHT', NULL, 12, 1),
(5, 1, 'USB-2M-WHT', 220.00, 8, 1),
(6, 1, 'USB-1M-RED', 170.00, 0, 1),

-- Product 2: Mobilní telefon XYZ (base price: 12500.00)
(7, 2, 'XYZ-128-BLK', NULL, 6, 1),
(8, 2, 'XYZ-128-SLV', NULL, 4, 1),
(9, 2, 'XYZ-256-BLK', 14500.00, 5, 1),
(10, 2, 'XYZ-256-BLU', 14900.00, 3, 1),
(11, 2, 'XYZ-512-BLU', 17900.00, 2, 1),
(12, 2, 'XYZ-512-BLK', 17900.00, 0, 1),

-- Product 3: Kniha PHP Programování (base price: 399.00)
(13, 3, 'PHP-BOOK-SOFT', NULL, 15, 1),
(14, 3, 'PHP-BOOK-HARD', 549.00, 10, 1),
(15, 3, 'PHP-BOOK-EBOOK', 249.00, 999, 1)
ON DUPLICATE KEY UPDATE
    product_id = VALUES(product_id),
    sku = VALUES(sku),
    price = VALUES(price),
    stock = VALUES(stock),
    active = VALUES(active);


-- 4. Variant Attribute Values Mapping (M:N)
INSERT INTO product_variant_values (variant_id, attribute_value_id) VALUES
-- Product 1 (USB Kabel: Délka + Barva)
(1, 9),  -- 1 m
(1, 1),  -- Černá

(2, 10), -- 2 m
(2, 1),  -- Černá

(3, 11), -- 3 m
(3, 1),  -- Černá

(4, 9),  -- 1 m
(4, 2),  -- Bílá

(5, 10), -- 2 m
(5, 2),  -- Bílá

(6, 9),  -- 1 m
(6, 5),  -- Červená

-- Product 2 (Mobilní telefon XYZ: Kapacita + Barva)
(7, 6),  -- 128 GB
(7, 1),  -- Černá

(8, 6),  -- 128 GB
(8, 4),  -- Stříbrná

(9, 7),  -- 256 GB
(9, 1),  -- Černá

(10, 7), -- 256 GB
(10, 3), -- Modrá

(11, 8), -- 512 GB
(11, 3), -- Modrá

(12, 8), -- 512 GB
(12, 1), -- Černá

-- Product 3 (Kniha PHP: Vazba)
(13, 12), -- Brožovaná
(14, 13), -- Pevná vazba
(15, 14)  -- E-kniha
ON DUPLICATE KEY UPDATE
    variant_id = VALUES(variant_id),
    attribute_value_id = VALUES(attribute_value_id);
