-- Drago Commerce example data: currency of the example orders. Import after 015_commerce_seed_order_names.sql.

UPDATE orders
SET currency = 'CZK'
WHERE currency = '';
