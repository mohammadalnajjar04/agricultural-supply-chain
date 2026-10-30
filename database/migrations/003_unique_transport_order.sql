-- Phase 3 optional database hardening. Backup DB before applying.
-- Preflight: This query MUST return zero rows; if not, investigate duplicates first.
SELECT order_id, COUNT(*) AS requests
FROM transport_requests
WHERE order_id IS NOT NULL
GROUP BY order_id
HAVING COUNT(*) > 1;

-- Apply this separately ONLY after confirming the preflight result is empty:
-- ALTER TABLE transport_requests ADD UNIQUE KEY uq_transport_requests_order_id (order_id);
-- Multiple NULL order_id values remain permitted by MySQL.
-- Do not uncomment or execute blindly on production data.
