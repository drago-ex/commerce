--
--  Drago commerce data sql.
-- -----------------------------------------------

INSERT INTO `carrier` (`id`, `name`, `price`) VALUES
(1, 'DHL', 150.00),
(2, 'Česká pošta', 120.00),
(3, 'Osobní odběr', 0.00);

INSERT INTO `payment` (`id`, `name`, `price`) VALUES
(1, 'Platba kartou', 0.00),
(2, 'Dobírka', 50.00),
(3, 'Bankovní převod', 0.00);

INSERT INTO `customers` (`id`, `email`, `phone`, `name`, `surname`, `street`, `city`, `post_code`, `country`, `note`, `created_at`) VALUES
(1, 'jan.novak@example.com', '+420777123456', 'Jan', 'Novák', 'Hlavní 123', 'Praha', '11000', 'Česká republika', '', '2025-05-30 10:00:00'),
(2, 'petra.svobodova@example.com', '+420777654321', 'Petra', 'Svobodová', 'Náměstí 45', 'Brno', '60200', 'Česká republika', 'Zákazník preferuje dodání po poledni', '2025-05-31 09:30:00');

INSERT INTO `products_category` (`id`, `parent`, `name`) VALUES
(1, NULL, 'Elektronika'),
(2, NULL, 'Knihy'),
(3, 1, 'Mobilní telefony');

INSERT INTO `products` (`id`, `category`, `name`, `description`, `discount`, `price`, `photo`, `active`, `stock`) VALUES
(1, 1, 'USB Kabel', 'Kvalitní USB kabel délky 1m.', NULL, 150.00, 'usb_kabel.jpg', 1, 50),
(2, 3, 'Mobilní telefon XYZ', 'Nejnovější model telefonu XYZ.', 10, 12500.00, 'mobil_xyz.jpg', 1, 10),
(3, 2, 'Kniha PHP Programování', 'Průvodce programováním v PHP.', NULL, 399.00, 'php_kniha.jpg', 1, 25);

INSERT INTO `orders` (`id`, `customer_id`, `carrier_id`, `payment_id`, `carrier_price`, `payment_price`, `total_price`, `created_at`, `status`) VALUES
(1, 1, 1, 2, 150.00, 50.00, 1200.00, '2025-06-01 12:00:00', 'pending'),
(2, 2, 3, 1, 0.00, 0.00, 399.00, '2025-06-02 15:30:00', 'pending');

INSERT INTO `orders_products` (`order_id`, `product_id`, `amount`) VALUES
(1, 2, 1),
(2, 3, 1),
(1, 1, 3);
