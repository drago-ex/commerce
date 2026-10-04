--
--  Database product attribute values
-- ----------------------------------
CREATE TABLE product_attribute_values (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    attribute_id INT UNSIGNED NOT NULL,
    value VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_attribute_value (attribute_id, value),

    CONSTRAINT fk_attribute_values_attribute
        FOREIGN KEY (attribute_id)
            REFERENCES product_attributes (id)
            ON DELETE CASCADE
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
