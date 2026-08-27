-- Drago Commerce SQL
-- ------------------
CREATE TABLE carrier (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(22) NOT NULL,
    name VARCHAR(255) NOT NULL,
    surname VARCHAR(255) NOT NULL,
    street VARCHAR(255) NOT NULL,
    city VARCHAR(255) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(255) NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE payment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10, 2) NOT NULL
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE products_category (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent INT UNSIGNED NULL,
    name VARCHAR(50) NOT NULL,

    UNIQUE KEY uq_products_category_name (name),
    CONSTRAINT fk_products_category_parent
        FOREIGN KEY (parent)
            REFERENCES products_category (id)
            ON DELETE SET NULL
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    discount INT NULL,
    price DECIMAL(10, 2) NOT NULL,
    photo TEXT NOT NULL,
    active BOOLEAN NOT NULL DEFAULT FALSE,
    stock INT NOT NULL DEFAULT 0,

    UNIQUE KEY uq_products_name (name),

    KEY idx_products_category (category),
    KEY idx_products_active (active),
    KEY idx_products_discount (discount),

    CONSTRAINT fk_products_category
        FOREIGN KEY (category)
            REFERENCES products_category (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    carrier_id INT UNSIGNED NOT NULL,
    payment_id INT UNSIGNED NOT NULL,
    carrier_price DECIMAL(10, 2) NOT NULL,
    payment_price DECIMAL(10, 2) NOT NULL,
    subtotal_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    discount_code VARCHAR(64) NULL,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',

    KEY idx_orders_customer (customer_id),
    KEY idx_orders_carrier (carrier_id),
    KEY idx_orders_payment (payment_id),

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
            REFERENCES customers (id),

    CONSTRAINT fk_orders_carrier
        FOREIGN KEY (carrier_id)
            REFERENCES carrier (id),

    CONSTRAINT fk_orders_payment
        FOREIGN KEY (payment_id)
            REFERENCES payment (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE orders_products (
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    amount INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,

    PRIMARY KEY (order_id, product_id),

    KEY idx_orders_products_product (product_id),

    CONSTRAINT fk_orders_products_order
        FOREIGN KEY (order_id)
            REFERENCES orders (id),

    CONSTRAINT fk_orders_products_product
        FOREIGN KEY (product_id)
            REFERENCES products (id)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4;


CREATE TABLE discount_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(64) NOT NULL,
    type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    value DECIMAL(10, 2) NOT NULL,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    minimum_order_amount DECIMAL(10, 2) NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_discount_codes_code (code),
    KEY idx_discount_codes_validity (active, valid_from, valid_to)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
