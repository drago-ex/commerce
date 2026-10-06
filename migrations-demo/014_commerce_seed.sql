-- Drago Commerce example data. Import after all schema migrations; never on a production database.

--
--  Drago commerce data sql (Expanded with diverse product catalog)
-- -----------------------------------------------------------------
SET NAMES utf8mb4;

INSERT INTO carrier (id, name, price) VALUES
(1, 'DHL', 150.00),
(2, 'Česká pošta', 120.00),
(3, 'Osobní odběr', 0.00)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price = VALUES(price);

INSERT INTO payment (id, name, price) VALUES
(1, 'Platba kartou', 0.00),
(2, 'Dobírka', 50.00),
(3, 'Bankovní převod', 0.00)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price = VALUES(price);

INSERT INTO customers (id, email, phone, name, surname, street, city, postal_code, country, note, created_at) VALUES
(1, 'jan.novak@example.com', '+420777123456', 'Jan', 'Novák', 'Hlavní 123', 'Praha', '11000', 'Česká republika', '', '2025-05-30 10:00:00'),
(2, 'petra.svobodova@example.com', '+420777654321', 'Petra', 'Svobodová', 'Náměstí 45', 'Brno', '60200', 'Česká republika', 'Zákazník preferuje dodání po poledni', '2025-05-31 09:30:00')
ON DUPLICATE KEY UPDATE
    email = VALUES(email),
    phone = VALUES(phone),
    name = VALUES(name),
    surname = VALUES(surname),
    street = VALUES(street),
    city = VALUES(city),
    postal_code = VALUES(postal_code),
    country = VALUES(country),
    note = VALUES(note);

INSERT INTO products_category (id, parent, name) VALUES
(1, NULL, 'Elektronika'),
(2, NULL, 'Knihy'),
(3, 1, 'Mobilní telefony'),
(4, 1, 'Počítače a notebooky'),
(5, 1, 'Audio a video'),
(6, NULL, 'Oblečení a móda')
ON DUPLICATE KEY UPDATE
    parent = VALUES(parent),
    name = VALUES(name);

INSERT INTO products (id, category, name, description, discount, price, photo, active, stock) VALUES
(1, 1, 'USB Kabel', 'Odolný USB-C kabel pro rychlé nabíjení a přenos dat.', NULL, 150.00, 'https://images.unsplash.com/photo-1660820936305-3e8df25adf0d?auto=format&fit=crop&w=1000&q=85', 1, 50),
(2, 3, 'Mobilní telefon XYZ', 'Moderní telefon s AMOLED displejem, kvalitním fotoaparátem a dlouhou výdrží baterie.', 10, 12500.00, 'https://images.unsplash.com/photo-1642101686083-71776082a4a2?auto=format&fit=crop&w=1000&q=85', 1, 10),
(3, 2, 'Kniha PHP Programování', 'Praktický průvodce moderním programováním v PHP 8.', NULL, 399.00, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=1000&q=85', 1, 25),
(4, 4, 'Herní notebook ASUS ROG Strix', 'Výkonný herní notebook s procesorem AMD Ryzen a grafikou NVIDIA RTX.', 5, 34990.00, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=1000&q=85', 1, 15),
(5, 5, 'Bezdrátová sluchátka SoundPro', 'Bezdrátová sluchátka s aktivním potlačením hluku a pohodlnými náušníky.', 15, 2490.00, 'https://images.unsplash.com/photo-1599955051125-571f47e04316?auto=format&fit=crop&w=1000&q=85', 1, 30),
(6, 6, 'Pánské tričko Classic', 'Pohodlné bavlněné tričko pro každodenní nošení.', NULL, 490.00, 'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?auto=format&fit=crop&w=1000&q=85', 1, 40)
ON DUPLICATE KEY UPDATE
    category = VALUES(category),
    name = VALUES(name),
    description = VALUES(description),
    discount = VALUES(discount),
    price = VALUES(price),
    photo = VALUES(photo),
    active = VALUES(active),
    stock = VALUES(stock);

INSERT INTO orders (id, customer_id, carrier_id, payment_id, carrier_price, payment_price, subtotal_price, total_price, discount_code, discount_amount, created_at, status) VALUES
(1, 1, 1, 2, 150.00, 50.00, 1000.00, 1200.00, NULL, 0.00, '2025-06-01 12:00:00', 'pending'),
(2, 2, 3, 1, 0.00, 0.00, 399.00, 399.00, NULL, 0.00, '2025-06-02 15:30:00', 'pending')
ON DUPLICATE KEY UPDATE
    customer_id = VALUES(customer_id),
    carrier_id = VALUES(carrier_id),
    payment_id = VALUES(payment_id),
    carrier_price = VALUES(carrier_price),
    payment_price = VALUES(payment_price),
    subtotal_price = VALUES(subtotal_price),
    total_price = VALUES(total_price),
    discount_code = VALUES(discount_code),
    discount_amount = VALUES(discount_amount),
    status = VALUES(status);

INSERT INTO orders_products (order_id, product_id, amount, unit_price) VALUES
(1, 2, 1, 550.00),
(2, 3, 1, 399.00),
(1, 1, 3, 150.00)
ON DUPLICATE KEY UPDATE
    amount = VALUES(amount),
    unit_price = VALUES(unit_price);

INSERT INTO discount_codes (code, type, value, active) VALUES
('TEST10', 'percent', 10, 1),
('TEST500', 'fixed', 500, 1)
ON DUPLICATE KEY UPDATE
    type = VALUES(type),
    value = VALUES(value),
    active = VALUES(active);


--
-- Example data for product_variants — demonstrates the feature using
-- products from this seed file.
-- Supports idempotent execution (UPSERT / ON DUPLICATE KEY UPDATE).
--
SET NAMES utf8mb4;

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


-- Repair image filenames from older demo seeds without replacing custom
-- image paths configured by the host application.
UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1660820936305-3e8df25adf0d?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 1 AND `photo` = 'usb_kabel.jpg';

UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1642101686083-71776082a4a2?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 2 AND `photo` = 'mobil_xyz.jpg';

UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 3 AND `photo` = 'php_kniha.jpg';

UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 4 AND `photo` = 'asus_rog.jpg';

UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1599955051125-571f47e04316?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 5 AND (`photo` = 'sluchatka_soundpro.jpg' OR `photo` LIKE 'https://placehold.co/%SoundPro%');

UPDATE `products`
SET `photo` = 'https://images.unsplash.com/photo-1651761179569-4ba2aa054997?auto=format&fit=crop&w=1000&q=85'
WHERE `id` = 6 AND `photo` = 'tricko_classic.jpg';

INSERT INTO `product_images` (`product_id`, `image`, `position`) VALUES
	(2, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=1000&q=85', 1),
	(4, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1000&q=85', 1),
	(5, 'https://images.unsplash.com/photo-1599855129764-f4cc28295202?auto=format&fit=crop&w=1000&q=85', 1),
	(5, 'https://images.unsplash.com/photo-1600019154417-70c9f205f406?auto=format&fit=crop&w=1000&q=85', 2)
ON DUPLICATE KEY UPDATE
	`image` = VALUES(`image`);
