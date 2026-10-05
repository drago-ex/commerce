--
--  Database order products
-- ------------------------
CREATE TABLE orders_products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NULL,
    variant_key INT UNSIGNED GENERATED ALWAYS AS (COALESCE(variant_id, 0)) STORED,
    amount INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_products_order_product_variant (order_id, product_id, variant_key),
    KEY idx_orders_products_product (product_id),
    KEY idx_orders_products_variant (variant_id),
    KEY idx_orders_products_product_variant (product_id, variant_id),

    CONSTRAINT fk_orders_products_order
        FOREIGN KEY (order_id)
            REFERENCES orders (id),

    CONSTRAINT fk_orders_products_product
        FOREIGN KEY (product_id)
            REFERENCES products (id),

    CONSTRAINT fk_orders_products_product_variant
        FOREIGN KEY (product_id, variant_id)
            REFERENCES product_variants (product_id, id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
