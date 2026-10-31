--
--  Database orders
-- ----------------
-- The names of the delivery and payment methods are kept as they were when the order was placed,
-- and every order records the currency of its amounts.
CREATE TABLE orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT UNSIGNED NOT NULL,
    carrier_id INT UNSIGNED NOT NULL,
    carrier_name VARCHAR(100) NOT NULL DEFAULT '',
    payment_id INT UNSIGNED NOT NULL,
    payment_name VARCHAR(100) NOT NULL DEFAULT '',
    carrier_price DECIMAL(10, 2) NOT NULL,
    payment_price DECIMAL(10, 2) NOT NULL,
    subtotal_price DECIMAL(10, 2) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    discount_code VARCHAR(64) NULL,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'new' COMMENT 'new, processing, shipped, completed, cancelled (OrderStatus)',
    paid_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_orders_status (status),
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
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_unicode_ci;
