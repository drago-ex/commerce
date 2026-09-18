-- Product attributes (e.g. "Barva", "Velikost") and their predefined values.
-- These are managed independently of any product — a value is only ever
-- assigned to a product by creating a row in product_variants /
-- product_variant_values below.

CREATE TABLE product_attributes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,

    UNIQUE KEY uq_product_attributes_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE product_attribute_values (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribute_id INT UNSIGNED NOT NULL,
    value VARCHAR(50) NOT NULL,

    UNIQUE KEY uq_attribute_value (attribute_id, value),

    CONSTRAINT fk_attribute_values_attribute
        FOREIGN KEY (attribute_id)
            REFERENCES product_attributes (id)
            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- A concrete sellable variant of a product = one specific combination of
-- attribute values (e.g. "Blue" + "M"). Has its own stock, and an optional
-- price override — NULL means it inherits the price from `products`.

CREATE TABLE product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    sku VARCHAR(64) NULL,
    price DECIMAL(10, 2) NULL,
    stock INT NOT NULL DEFAULT 0,
    active BOOLEAN NOT NULL DEFAULT TRUE,

    UNIQUE KEY uq_variant_sku (sku),
    KEY idx_variant_product (product_id),

    CONSTRAINT fk_variant_product
        FOREIGN KEY (product_id)
            REFERENCES products (id)
            ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Which attribute values a given variant is made of (M:N). Variant #5
-- might be made of (Barva=Modrá) + (Velikost=M), i.e. two rows here.

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
