<?php
declare(strict_types=1);

final class ProductCatalog
{
    public function __construct(private mysqli $db) {}

    public function forStore(int $limit, int $offset): array
    {
        // Matches phase 5's products.is_active migration. Avoid exposing internal user data.
        $sql = 'SELECT p.product_id, p.name, p.quantity, p.price, p.harvest_date, '
             . 'f.name AS farmer_name, f.location AS farmer_location '
             . 'FROM products p INNER JOIN farmers f ON f.farmer_id = p.farmer_id '
             . 'WHERE p.is_active = 1 AND p.quantity > 0 '
             . 'ORDER BY p.product_id DESC LIMIT ? OFFSET ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
