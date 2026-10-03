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
(1, 1, 'USB Kabel', 'Kvalitní USB kabel délky 1m.', NULL, 150.00, 'usb_kabel.jpg', 1, 50),
(2, 3, 'Mobilní telefon XYZ', 'Nejnovější model telefonu XYZ s AMOLED displejem.', 10, 12500.00, 'mobil_xyz.jpg', 1, 10),
(3, 2, 'Kniha PHP Programování', 'Komplexní průvodce moderním programováním v PHP 8.', NULL, 399.00, 'php_kniha.jpg', 1, 25),
(4, 4, 'Herní notebook ASUS ROG Strix', 'Špičkový herní notebook s procesorem AMD Ryzen a grafikou NVIDIA RTX.', 5, 34990.00, 'asus_rog.jpg', 1, 15),
(5, 5, 'Bezdrátová sluchátka SoundPro', 'Prémiová bezdrátová sluchátka s aktivním potlačením hluku ANC.', 15, 2490.00, 'sluchatka_soundpro.jpg', 1, 30),
(6, 6, 'Pánské tričko Classic', 'Pohodlné tričko ze 100% organické bavlny pro každodenní nošení.', NULL, 490.00, 'tricko_classic.jpg', 1, 40)
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
