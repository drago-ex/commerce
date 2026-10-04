--
--  Database product variants
-- --------------------------
CREATE TABLE product_variants (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(64) NULL,
    price DECIMAL(10, 2) NULL,
    stock INT NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variant_sku (sku),
    UNIQUE KEY uq_product_variants_product_id (product_id, id),
    KEY idx_variant_product (product_id),

    CONSTRAINT fk_variant_product
        FOREIGN KEY (product_id)
            REFERENCES products (id)
            ON DELETE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
