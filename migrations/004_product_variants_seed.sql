-- Example data for product_variants — demonstrates the feature using
-- product id 2 ("Mobilní telefon XYZ") from 002.commerce_seed.sql.

INSERT INTO product_attributes (id, name) VALUES
(1, 'Barva');

INSERT INTO product_attribute_values (id, attribute_id, value) VALUES
(1, 1, 'Černá'),
(2, 1, 'Bílá');

INSERT INTO product_variants (id, product_id, sku, price, stock, active) VALUES
(1, 2, 'XYZ-BLACK', NULL, 6, 1),
(2, 2, 'XYZ-WHITE', 12900.00, 4, 1);

-- Variant 1 (black) has no price override, so it inherits products.price (12500.00).
-- Variant 2 (white) is 400 Kč more expensive than the base color.

INSERT INTO product_variant_values (variant_id, attribute_value_id) VALUES
(1, 1),
(2, 2);
