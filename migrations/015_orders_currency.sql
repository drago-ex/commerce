-- Drago Commerce: every order records the currency of its amounts.

ALTER TABLE orders
    ADD COLUMN currency CHAR(3) NOT NULL DEFAULT '' AFTER discount_amount;
