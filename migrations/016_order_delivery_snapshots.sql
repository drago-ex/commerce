-- Keep delivery and payment method names as they were when the order was placed.

ALTER TABLE orders
    ADD COLUMN carrier_name VARCHAR(100) NOT NULL DEFAULT '' AFTER carrier_id,
    ADD COLUMN payment_name VARCHAR(100) NOT NULL DEFAULT '' AFTER payment_id;

UPDATE orders o
    INNER JOIN carrier c ON c.id = o.carrier_id
SET o.carrier_name = c.name
WHERE o.carrier_name = '';

UPDATE orders o
    INNER JOIN payment p ON p.id = o.payment_id
SET o.payment_name = p.name
WHERE o.payment_name = '';
