-- PHASE 16. READ ONLY. Execute in a STAGING copy of your project's database.
-- No table creation, data mutation, or migration applied.
SELECT DATABASE() AS active_database;
SELECT TABLE_NAME,COLUMN_NAME,DATA_TYPE,IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA=DATABASE()
 AND ((TABLE_NAME='products' AND COLUMN_NAME IN ('is_active','quantity','product_id'))
 OR (TABLE_NAME='orders' AND COLUMN_NAME IN ('product_name_at_order','unit_price_at_order','order_id','quantity'))
 OR (TABLE_NAME='inventory_movements' AND COLUMN_NAME IN ('order_id','movement_type','quantity_change','stock_after')))
ORDER BY TABLE_NAME,COLUMN_NAME;
SELECT TABLE_NAME,CONSTRAINT_NAME,REFERENCED_TABLE_NAME,DELETE_RULE
FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('products','orders','transport_requests','inventory_movements')
ORDER BY TABLE_NAME,CONSTRAINT_NAME;
SELECT TABLE_NAME, INDEX_NAME,NON_UNIQUE,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS indexed_columns
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME IN ('transport_requests','inventory_movements')
GROUP BY TABLE_NAME,INDEX_NAME,NON_UNIQUE
ORDER BY TABLE_NAME,INDEX_NAME;
-- Only run next read-only checks once the indicated tables exist.
-- SELECT order_id, COUNT(*) AS cnt FROM transport_requests WHERE order_id IS NOT NULL GROUP BY order_id HAVING COUNT(*)>1;
-- SELECT order_id, movement_type, COUNT(*) cnt FROM inventory_movements WHERE order_id IS NOT NULL GROUP BY order_id,movement_type HAVING COUNT(*)>1;
