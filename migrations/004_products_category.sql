--
--  Database product categories
-- ----------------------------
CREATE TABLE products_category (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent INT UNSIGNED NULL,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_category_name (name),

    CONSTRAINT fk_products_category_parent
        FOREIGN KEY (parent)
            REFERENCES products_category (id)
            ON DELETE SET NULL
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
