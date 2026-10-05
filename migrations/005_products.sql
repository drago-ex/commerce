--
--  Database products
-- ------------------
CREATE TABLE products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    discount INT NULL,
    price DECIMAL(10, 2) NOT NULL,
    photo TEXT NOT NULL,
    active BOOLEAN NOT NULL DEFAULT FALSE,
    stock INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_name (name),
    KEY idx_products_category (category),
    KEY idx_products_active (active),
    KEY idx_products_discount (discount),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category)
            REFERENCES products_category (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
