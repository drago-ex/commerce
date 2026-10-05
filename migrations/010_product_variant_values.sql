--
--  Database product variant values
-- --------------------------------
CREATE TABLE product_variant_values (
    variant_id INT UNSIGNED NOT NULL,
    attribute_value_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (variant_id, attribute_value_id),

    CONSTRAINT fk_variant_values_variant
        FOREIGN KEY (variant_id)
            REFERENCES product_variants (id)
            ON DELETE CASCADE,

    CONSTRAINT fk_variant_values_attribute_value
        FOREIGN KEY (attribute_value_id)
            REFERENCES product_attribute_values (id)
            ON DELETE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
