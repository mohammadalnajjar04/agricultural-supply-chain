-- Phase 4, MySQL 8 / InnoDB: preserve order and delivery history.
-- RUN MANUALLY ONLY after backup, review of 004_preflight_readonly.sql results,
-- and confirmation that constraint names match your LIVE database.
-- MySQL DDL implicitly commits; these statements are NOT a single rollback-able transaction.
-- Existing user/product hard deletion routes may now fail with FK constraint errors.
-- Prefer application-level soft deletion in the next phase.
-- Do NOT set FOREIGN_KEY_CHECKS=0.

-- Product ownership: farmer deletion must not cascade to product/orders.
ALTER TABLE products
 DROP FOREIGN KEY products_ibfk_1,
 ADD CONSTRAINT products_ibfk_1 FOREIGN KEY (farmer_id)
 REFERENCES farmers(farmer_id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Order records must survive accidental removal of their store or listed product.
ALTER TABLE orders
 DROP FOREIGN KEY orders_ibfk_1,
 DROP FOREIGN KEY orders_ibfk_2,
 ADD CONSTRAINT orders_ibfk_1 FOREIGN KEY (store_id)
 REFERENCES stores(store_id) ON DELETE RESTRICT ON UPDATE CASCADE,
 ADD CONSTRAINT orders_ibfk_2 FOREIGN KEY (product_id)
 REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Keep links from delivery requests to orders and business participants.
-- Previously nullable order_id used SET NULL; retain historic linkage instead.
ALTER TABLE transport_requests
 DROP FOREIGN KEY fk_transport_order,
 DROP FOREIGN KEY transport_requests_ibfk_1,
 DROP FOREIGN KEY transport_requests_ibfk_2,
 DROP FOREIGN KEY transport_requests_ibfk_3,
 ADD CONSTRAINT fk_transport_order FOREIGN KEY (order_id)
 REFERENCES orders(order_id) ON DELETE RESTRICT ON UPDATE CASCADE,
 ADD CONSTRAINT transport_requests_ibfk_1 FOREIGN KEY (product_id)
 REFERENCES products(product_id) ON DELETE RESTRICT ON UPDATE CASCADE,
 ADD CONSTRAINT transport_requests_ibfk_2 FOREIGN KEY (transporter_id)
 REFERENCES transporters(transporter_id) ON DELETE RESTRICT ON UPDATE CASCADE,
 ADD CONSTRAINT transport_requests_ibfk_3 FOREIGN KEY (store_id)
 REFERENCES stores(store_id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Useful access-path indexes for common dashboard filters.
-- Verify absence of these index names using SHOW INDEX FROM ... before applying.
CREATE INDEX idx_orders_store_status_date ON orders (store_id, status, order_date);
CREATE INDEX idx_orders_product_status ON orders (product_id, status);
CREATE INDEX idx_transport_requests_driver_status ON transport_requests (transporter_id, status);
CREATE INDEX idx_transport_requests_farmer_status ON transport_requests (farmer_id, status);
