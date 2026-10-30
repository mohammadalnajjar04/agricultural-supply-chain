-- Phase 6 — apply ONLY after backing up and reviewing the active schema.
-- Requires MySQL 8+ / InnoDB and prior phase migrations (especially Phase 5).
-- DDL auto-commits: execute once in a disposable staging DB first.
-- Existing orders receive snapshots from their CURRENT product metadata; historical names/prices
-- cannot be reconstructed with certainty when products were edited after purchase.

ALTER TABLE orders
 ADD COLUMN product_name_at_order VARCHAR(100) NULL,
 ADD COLUMN unit_price_at_order DECIMAL(10,2) NULL;

UPDATE orders o JOIN products p ON o.product_id = p.product_id
 SET o.product_name_at_order = p.name,
     o.unit_price_at_order = CASE WHEN o.quantity > 0 THEN ROUND(o.total_price / o.quantity, 2) ELSE NULL END
 WHERE o.product_name_at_order IS NULL;

CREATE TABLE inventory_movements (
 movement_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 product_id INT NOT NULL,
 order_id INT NULL,
 movement_type VARCHAR(40) NOT NULL,
 quantity_change INT NOT NULL,
 stock_before INT NOT NULL,
 stock_after INT NOT NULL,
 actor_farmer_id INT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_inventory_movement_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT,
 CONSTRAINT fk_inventory_movement_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE RESTRICT,
 CONSTRAINT fk_inventory_movement_farmer FOREIGN KEY (actor_farmer_id) REFERENCES farmers(farmer_id) ON DELETE RESTRICT,
 CONSTRAINT chk_inventory_consistency CHECK (stock_after = stock_before + quantity_change),
 CONSTRAINT chk_inventory_nonnegative CHECK (stock_before >= 0 AND stock_after >= 0),
 UNIQUE KEY uq_inventory_order_movement (order_id, movement_type),
 INDEX idx_inventory_product_created (product_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Existing stock changes are not backfilled; ledger starts with new approvals only.
