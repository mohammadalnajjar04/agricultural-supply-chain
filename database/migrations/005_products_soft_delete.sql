-- Phase 5: Run only after a database backup, on MySQL 8 / InnoDB.
-- Inspect first: SHOW COLUMNS FROM products LIKE 'is_active';
-- Skip if the column already exists. DDL may implicitly commit.
ALTER TABLE products
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
-- Optional after checking index names: CREATE INDEX idx_products_active_quantity ON products (is_active, quantity);
