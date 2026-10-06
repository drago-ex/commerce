-- Drago Commerce example data: names of the example order lines. Import after 014_commerce_seed.sql.

UPDATE orders_products op
    INNER JOIN products p ON p.id = op.product_id
SET op.product_name = p.name
WHERE op.product_name = '';
