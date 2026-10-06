-- Drago Commerce: order lines keep the product name and variant label as they were when the order was placed.

ALTER TABLE orders_products
    ADD COLUMN product_name VARCHAR(100) NOT NULL DEFAULT '' AFTER variant_key,
    ADD COLUMN variant_label VARCHAR(255) NULL AFTER product_name;

-- Fill existing lines from the current catalog.
UPDATE orders_products op
    INNER JOIN products p ON p.id = op.product_id
SET op.product_name = p.name
WHERE op.product_name = '';

UPDATE orders_products op
SET op.variant_label = (
    SELECT GROUP_CONCAT(CONCAT(a.name, ': ', v.value) ORDER BY a.id SEPARATOR ', ')
    FROM product_variant_values vv
        INNER JOIN product_attribute_values v ON v.id = vv.attribute_value_id
        INNER JOIN product_attributes a ON a.id = v.attribute_id
    WHERE vv.variant_id = op.variant_id
)
WHERE op.variant_id IS NOT NULL
  AND op.variant_label IS NULL;
