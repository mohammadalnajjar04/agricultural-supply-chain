<?php
// Returns ML-predicted price series for the next N months using Flask AI service.
// Query:
//  - product (required)
//  - market  (required)
//  - start_year (optional, default current year)
//  - start_month (optional, default current month)
//  - months (optional, default 6, max 12)
//  - quantity_kg (optional, default 500)

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/ai_client.php';

$product = isset($_GET['product']) ? trim($_GET['product']) : '';
$market  = isset($_GET['market']) ? trim($_GET['market']) : '';
$startYear  = isset($_GET['start_year']) ? (int)$_GET['start_year'] : (int)date('Y');
$startMonth = isset($_GET['start_month']) ? (int)$_GET['start_month'] : (int)date('n');
$months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
$months = max(2, min(12, $months));
$quantityKg = isset($_GET['quantity_kg']) ? (int)$_GET['quantity_kg'] : 500;
$quantityKg = max(1, $quantityKg);

if ($product === '' || $market === '') {
    echo json_encode(['ok'=>false, 'error'=>'missing product/market'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($startMonth < 1) $startMonth = 1;
if ($startMonth > 12) $startMonth = 12;

$series = [];
$y = $startYear;
$m = $startMonth;

for ($i = 0; $i < $months; $i++) {
    $resp = ai_ml_predict($product, $market, $y, $m, $quantityKg);
    if (!($resp['ok'] ?? false)) {
        echo json_encode(['ok'=>false, 'error'=>'ai_service_error', 'details'=>$resp], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pred = $resp['data']['predicted_price_jod'] ?? null;
    $series[] = [
        'label' => sprintf('%04d-%02d', $y, $m),
        'year' => $y,
        'month' => $m,
        'predicted_price_jod' => $pred
    ];

    $m++;
    if ($m > 12) { $m = 1; $y++; }
}

echo json_encode(['ok'=>true, 'series'=>$series], JSON_UNESCAPED_UNICODE);
