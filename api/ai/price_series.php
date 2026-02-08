<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../../config/db.php";

$product = isset($_GET['product']) ? trim($_GET['product']) : '';
$market  = isset($_GET['market']) ? trim($_GET['market']) : 'Irbid';
$date    = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

if ($product === '') {
    echo json_encode(["ok"=>false, "error"=>"missing product"], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "
    SELECT date, price_qirsh_per_kg
    FROM market_prices
    WHERE product_name = ? AND market = ?
      AND date <= ?
    ORDER BY date DESC
    LIMIT 14
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["ok"=>false, "error"=>"query_prepare_failed"], JSON_UNESCAPED_UNICODE);
    exit;
}
$stmt->bind_param("sss", $product, $market, $date);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
$rows = array_reverse($rows);

echo json_encode(["ok"=>true, "series"=>$rows], JSON_UNESCAPED_UNICODE);
