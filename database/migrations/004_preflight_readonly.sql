-- Phase 4: read-only preflight; run against your development database.
-- IMPORTANT: Do not execute database/agri_supply_chain_FINAL.sql against an existing DB: it drops tables.
SELECT DATABASE() AS active_database;
SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('farmers','stores','transporters','products','orders','transport_requests');
SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, DELETE_RULE
 FROM information_schema.REFERENTIAL_CONSTRAINTS rc
 JOIN information_schema.KEY_COLUMN_USAGE k
 ON k.CONSTRAINT_SCHEMA=rc.CONSTRAINT_SCHEMA AND k.TABLE_NAME=rc.TABLE_NAME
 AND k.CONSTRAINT_NAME=rc.CONSTRAINT_NAME
 WHERE rc.CONSTRAINT_SCHEMA=DATABASE() AND rc.TABLE_NAME IN ('products','orders','transport_requests')
 ORDER BY rc.TABLE_NAME, rc.CONSTRAINT_NAME;
-- Migration 003 preflight; only one delivery request per non-null order should exist.
SELECT order_id, COUNT(*) AS request_count
 FROM transport_requests WHERE order_id IS NOT NULL
 GROUP BY order_id HAVING COUNT(*) > 1;
SELECT 'orders' AS table_name, COUNT(*) AS row_count FROM orders
 UNION ALL SELECT 'transport_requests', COUNT(*) FROM transport_requests
 UNION ALL SELECT 'products', COUNT(*) FROM products;
