CREATE TABLE IF NOT EXISTS `product_images` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`product_id` INT UNSIGNED NOT NULL,
	`image` TEXT NOT NULL,
	`position` INT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`id`),
	UNIQUE KEY `uq_product_images_product_position` (`product_id`, `position`),
	CONSTRAINT `fk_product_images_product`
		FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
