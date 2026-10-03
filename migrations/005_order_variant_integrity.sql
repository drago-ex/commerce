-- Keep the product/variant pair on an order line consistent at the database level.
ALTER TABLE product_variants
    ADD UNIQUE KEY uq_product_variants_product_id (product_id, id);

ALTER TABLE orders_products
    DROP FOREIGN KEY fk_orders_products_variant,
    ADD KEY idx_orders_products_product_variant (product_id, variant_id),
    ADD CONSTRAINT fk_orders_products_product_variant
        FOREIGN KEY (product_id, variant_id)
            REFERENCES product_variants (product_id, id);
