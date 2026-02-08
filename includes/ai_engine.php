<?php
/**
 * AI Engine (PHP) - Forecasting + Recommendations
 * Project: AgriLink (Agri Supply Chain)
 * Notes:
 * - Uses sample historical market prices (market_prices) for 2024-2026
 * - Forecast method: 7-day moving average * seasonal index (month)
 */

function ai_normalize_location(string $location): string {
    $loc = trim(mb_strtolower($location));
    $loc = str_replace(['،',',','-','_'], ' ', $loc);
    $loc = preg_replace('/\s+/', ' ', $loc);
    return $loc;
}

function ai_location_coords(string $location): ?array {
    // Simple, believable mapping for Jordan (governorates / main cities)
    // (We keep it small and practical for a university project.)
    $map = [
        'amman' => [31.9539, 35.9106],
        'zarqa' => [32.0728, 36.0870],
        'irbid' => [32.5569, 35.8469],
        'ajloun' => [32.3333, 35.7500],
        'jerash' => [32.2808, 35.8993],
        'mafraq' => [32.3429, 36.2080],
        'madaba' => [31.7167, 35.8000],
        'salt' => [32.0392, 35.7272],
        'balqa' => [32.0392, 35.7272],
        'karak' => [31.1856, 35.7047],
        'tafila' => [30.8333, 35.6000],
        'maan' => [30.1949, 35.7342],
        'aqaba' => [29.5321, 35.0063],
    ];

    $loc = ai_normalize_location($location);

    foreach ($map as $k => $coords) {
        if ($loc === $k || str_contains($loc, $k)) {
            return ['lat' => $coords[0], 'lng' => $coords[1], 'label' => ucfirst($k)];
        }
    }

    return null;
}

function ai_haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

function ai_estimate_delivery_fee(float $km_total, int $qty_kg = 0): float {
    // Simple pricing model (JD)
    $base = 0.75;
    $km_rate = 0.20;
    $weight_rate = 0.00; // optional
    $fee = $base + ($km_rate * $km_total) + ($weight_rate * max(0, $qty_kg));
    // round to 2 decimals
    return round($fee, 2);
}

function ai_market_from_location(?string $location): string {
    // Use location as market label, fallback to Amman
    $loc = trim((string)$location);
    if ($loc === '') return 'Amman';
    $coords = ai_location_coords($loc);
    return $coords ? $coords['label'] : 'Amman';
}

function ai_forecast_market_price(mysqli $conn, string $product_name, string $market, string $date_ymd): array {
    // Return: ['ok'=>true,'expected'=>int,'yesterday'=>?int,'trend'=>'up|down|flat','pct'=>float, 'method'=>string]
    $product_name = trim($product_name);
    $market = trim($market);
    $date = $date_ymd;

    // Yesterday date
    $yesterday = date('Y-m-d', strtotime($date . ' -1 day'));

    // Pull last 30 days data (including yesterday)
    $stmt = $conn->prepare("
        SELECT date, price_qirsh_per_kg
        FROM market_prices
        WHERE product_name = ? AND market = ?
          AND date <= ? AND date >= DATE_SUB(?, INTERVAL 30 DAY)
        ORDER BY date DESC
    ");
    $stmt->bind_param("ssss", $product_name, $market, $yesterday, $yesterday);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    $rows_count = count($rows);
    if ($rows_count < 5) {
        return ['ok'=>false,'error'=>'Not enough data for forecast', 'data_points'=>$rows_count];
    }

    // Yesterday price (if exact), else most recent
    $y_price = null;
    foreach ($rows as $r) {
        if ($r['date'] === $yesterday) { $y_price = (int)$r['price_qirsh_per_kg']; break; }
    }
    if ($y_price === null) $y_price = (int)$rows[0]['price_qirsh_per_kg'];

    // 7-day moving average
    $last7 = array_slice($rows, 0, min(7, $rows_count));
    $sum7 = 0;
    foreach ($last7 as $r) $sum7 += (int)$r['price_qirsh_per_kg'];
    $ma7 = $sum7 / count($last7);

    // Seasonal index for month
    $month = (int)date('m', strtotime($date));
    $stmtM = $conn->prepare("
        SELECT AVG(price_qirsh_per_kg) AS m_avg
        FROM market_prices
        WHERE product_name = ? AND market = ?
          AND MONTH(date) = ?
    ");
    $stmtM->bind_param("ssi", $product_name, $market, $month);
    $stmtM->execute();
    $m_avg = (float)($stmtM->get_result()->fetch_assoc()['m_avg'] ?? 0);

    $stmtAll = $conn->prepare("
        SELECT AVG(price_qirsh_per_kg) AS all_avg
        FROM market_prices
        WHERE product_name = ? AND market = ?
    ");
    $stmtAll->bind_param("ss", $product_name, $market);
    $stmtAll->execute();
    $all_avg = (float)($stmtAll->get_result()->fetch_assoc()['all_avg'] ?? 0);

    $season_index = 1.0;
    if ($m_avg > 0 && $all_avg > 0) {
        $season_index = $m_avg / $all_avg;
    }

    $expected = (int)round($ma7 * $season_index);

    $trend = 'flat';
    if ($expected > $y_price) $trend = 'up';
    elseif ($expected < $y_price) $trend = 'down';

    $pct = 0.0;
    if ($y_price > 0) $pct = round((($expected - $y_price) / $y_price) * 100, 1);

    // Confidence based on available points (simple + explainable)
    $confidence = 'low';
    if ($rows_count >= 14) $confidence = 'high';
    elseif ($rows_count >= 7) $confidence = 'medium';

    return [
        'ok'=>true,
        'product'=>$product_name,
        'market'=>$market,
        'date'=>$date,
        'expected'=>$expected,
        'yesterday'=>$y_price,
        'trend'=>$trend,
        'pct'=>$pct,
        'ma7'=>$ma7,
        'season_index'=>$season_index,
        'method'=>'MA(7) × SeasonalIndex(month)',
        'data_points'=>$rows_count,
        'confidence'=>$confidence
    ];
}

function ai_store_best_offer(mysqli $conn, int $store_id, string $product_name, string $date_ymd): array {
    // Find forecast + cheapest farmer offer in products table
    $stmtS = $conn->prepare("SELECT location, name FROM stores WHERE store_id = ?");
    $stmtS->bind_param("i", $store_id);
    $stmtS->execute();
    $store = $stmtS->get_result()->fetch_assoc();
    $market = ai_market_from_location($store['location'] ?? '');

    $forecast = ai_forecast_market_price($conn, $product_name, $market, $date_ymd);

    $stmt = $conn->prepare("
        SELECT p.product_id, p.price, p.quantity, f.farmer_id, f.name AS farmer_name, f.location AS farmer_location
        FROM products p
        JOIN farmers f ON f.farmer_id = p.farmer_id
        WHERE p.name = ? AND p.quantity > 0
        ORDER BY p.price ASC, p.quantity DESC
        LIMIT 5
    ");
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $res = $stmt->get_result();

    $offers = [];
    while ($r = $res->fetch_assoc()) {
        $offers[] = [
            'product_id' => (int)$r['product_id'],
            'farmer_id' => (int)$r['farmer_id'],
            'farmer_name' => $r['farmer_name'],
            'price_jd' => (float)$r['price'],
            'price_qirsh' => (int)round(((float)$r['price']) * 100),
            'qty' => (int)$r['quantity'],
            'farmer_location' => $r['farmer_location'] ?? ''
        ];
    }

    $best = $offers[0] ?? null;

    $rec_text = null;
    if ($best) {
        $rec_text = "أفضل خيار هو الشراء من " . $best['farmer_name'] . " لأن السعر هو الأقل (" . number_format((float)$best['price_jd'], 2) . " د.أ/كغم) مع توفر كمية " . $best['qty'] . " كغم.";
    } else {
        $rec_text = "لا يوجد عروض متاحة حالياً لهذا المنتج.";
    }

    return [
        'ok'=>true,
        'store'=>['id'=>$store_id,'name'=>$store['name'] ?? ''],
        'market'=>$market,
        'forecast'=>$forecast,
        'offers'=>$offers,
        'best_offer'=>$best,
        'recommendation_text'=>$rec_text
    ];
}

function ai_transporter_recommendations(mysqli $conn, int $transporter_id, string $date_ymd, int $limit = 5): array {
    $stmtT = $conn->prepare("SELECT name, location FROM transporters WHERE transporter_id = ?");
    $stmtT->bind_param("i", $transporter_id);
    $stmtT->execute();
    $t = $stmtT->get_result()->fetch_assoc();
    $t_loc = $t['location'] ?? '';
    $t_coords = ai_location_coords($t_loc);

    // Get pending requests not yet assigned
    $stmt = $conn->prepare("
        SELECT tr.request_id, tr.quantity, tr.transport_date, tr.status,
               p.name AS product_name, p.location AS farm_loc_alt, p.farm_location AS farm_loc,
               f.name AS farmer_name, f.location AS farmer_location,
               s.name AS store_name, s.location AS store_location
        FROM transport_requests tr
        JOIN products p ON p.product_id = tr.product_id
        JOIN farmers f ON f.farmer_id = tr.farmer_id
        LEFT JOIN stores s ON s.store_id = tr.store_id
        WHERE tr.status = 'pending' AND (tr.transporter_id IS NULL OR tr.transporter_id = 0)
        ORDER BY tr.request_date DESC
        LIMIT 50
    ");
    $stmt->execute();
    $res = $stmt->get_result();

    $candidates = [];
    while ($r = $res->fetch_assoc()) {
        $farm_loc = $r['farm_loc'] ?: ($r['farm_loc_alt'] ?: ($r['farmer_location'] ?? ''));
        $store_loc = $r['store_location'] ?? '';
        $farm_c = ai_location_coords($farm_loc);
        $store_c = ai_location_coords($store_loc);

        $d1 = null; $d2 = null; $total = null; $fee = null;

        if ($t_coords && $farm_c) {
            $d1 = ai_haversine_km($t_coords['lat'], $t_coords['lng'], $farm_c['lat'], $farm_c['lng']);
        }
        if ($farm_c && $store_c) {
            $d2 = ai_haversine_km($farm_c['lat'], $farm_c['lng'], $store_c['lat'], $store_c['lng']);
        }
        if ($d1 !== null) {
            $total = $d1 + ($d2 ?? 0.0);
            $fee = ai_estimate_delivery_fee($total, (int)$r['quantity']);
        }

        $candidates[] = [
            'request_id' => (int)$r['request_id'],
            'product' => $r['product_name'],
            'quantity' => (int)$r['quantity'],
            'farmer' => $r['farmer_name'],
            'store' => $r['store_name'] ?? '',
            'farm_location' => $farm_loc,
            'store_location' => $store_loc,
            'driver_location' => $t_loc,
            'km_driver_to_farm' => $d1 !== null ? round($d1, 1) : null,
            'km_farm_to_store' => $d2 !== null ? round($d2, 1) : null,
            'km_total' => $total !== null ? round($total, 1) : null,
            'delivery_fee_jd' => $fee
        ];
    }

    // Sort by closest pickup distance
    usort($candidates, function($a, $b) {
        $da = $a['km_driver_to_farm'] ?? 999999;
        $db = $b['km_driver_to_farm'] ?? 999999;
        if ($da == $db) {
            $ta = $a['km_total'] ?? 999999;
            $tb = $b['km_total'] ?? 999999;
            return $ta <=> $tb;
        }
        return $da <=> $db;
    });

    $top = array_slice($candidates, 0, max(1, $limit));

    $explain = "نقوم بترتيب طلبات النقل حسب أقرب مزرعة إلى موقعك (pickup distance) ثم حسب المسافة الكلية. الأجرة تقديرية: base + (km_rate × km).";

    return [
        'ok'=>true,
        'transporter'=>['id'=>$transporter_id,'name'=>$t['name'] ?? '', 'location'=>$t_loc],
        'recommendations'=>$top,
        'explain'=>$explain
    ];
}
