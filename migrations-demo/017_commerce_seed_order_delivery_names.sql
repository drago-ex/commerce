-- Drago Commerce example data: names of the delivery and payment methods stored with orders.

UPDATE orders o
    INNER JOIN carrier c ON c.id = o.carrier_id
SET o.carrier_name = c.name
WHERE o.carrier_name = '';

UPDATE orders o
    INNER JOIN payment p ON p.id = o.payment_id
SET o.payment_name = p.name
WHERE o.payment_name = '';
