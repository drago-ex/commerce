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

-- Demo gallery images for the SoundPro product (ID 5). Replace these
-- placeholder URLs with the real image URLs/paths in the host application.
-- Repair the seed filename for existing demo databases without replacing
-- a real image path that an application may already have configured.
UPDATE `products`
SET `photo` = 'https://placehold.co/600x500/f4f6f8/495057.png?text=SoundPro+-+zepredu'
WHERE `id` = 5 AND `photo` = 'sluchatka_soundpro.jpg';

INSERT INTO `product_images` (`product_id`, `image`, `position`) VALUES
	(5, 'https://placehold.co/600x500/f4f6f8/495057.png?text=SoundPro+-+zleva', 1),
	(5, 'https://placehold.co/600x500/f4f6f8/495057.png?text=SoundPro+-+detail', 2)
ON DUPLICATE KEY UPDATE
	`image` = VALUES(`image`);
