--
--  Drago commerce sql.
-- -----------------------------------------------

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `carrier`;
CREATE TABLE `carrier` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `phone` varchar(22) NOT NULL,
    `name` varchar(255) NOT NULL,
    `surname` varchar(255) NOT NULL,
    `street` varchar(255) NOT NULL,
    `city` varchar(255) NOT NULL,
    `post_code` varchar(255) NOT NULL,
    `country` varchar(255) NOT NULL,
    `note` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) NOT NULL,
    `carrier_id` int(11) NOT NULL,
    `payment_id` int(11) NOT NULL,
    `carrier_price` decimal(10,2) NOT NULL,
    `payment_price` decimal(10,2) NOT NULL,
    `total_price` decimal(10,2) NOT NULL,
    `date` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status` varchar(20) NOT NULL DEFAULT 'pending',
    PRIMARY KEY (`id`),
    KEY `customer` (`customer_id`),
    KEY `carrier_id` (`carrier_id`),
    KEY `payment_id` (`payment_id`),
    KEY `date` (`date`),
    CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
    CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`carrier_id`) REFERENCES `carrier` (`id`),
    CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`payment_id`) REFERENCES `payment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `orders_products`;
CREATE TABLE `orders_products` (
    `order_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `amount` int(11) NOT NULL,
    UNIQUE KEY `order_id_product_id` (`order_id`,`product_id`),
    KEY `product` (`product_id`),
    CONSTRAINT `orders_products_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
    CONSTRAINT `orders_products_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `category` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `discount` int(11) DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `photo` text NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    KEY `category` (`category`),
    KEY `active` (`active`),
    KEY `discount` (`discount`),
    CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category`) REFERENCES `products_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `products_category`;
CREATE TABLE `products_category` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `parent` int(11) DEFAULT NULL,
    `name` varchar(50) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`),
    CONSTRAINT `products_category_fk_parent` FOREIGN KEY (`parent`) REFERENCES `products_category` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
