<?php
/**
 * AI Client (PHP) - calls Python Flask microservice (ai_service)
 * Default URL: http://127.0.0.1:5000
 * You can override by setting environment variable: AI_SERVICE_URL
 */

function ai_service_url(): string {
    $url = getenv('AI_SERVICE_URL');
    if (!$url) $url = 'http://127.0.0.1:5000';
    return rtrim($url, '/');
}

function ai_http_post_json(string $path, array $payload, int $timeoutSec = 4): array {
    $url = ai_service_url() . $path;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSec);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => 'AI service not reachable', 'details' => $err];
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Invalid AI response', 'http_code' => $code, 'raw' => $resp];
    }

    if ($code >= 400) {
        return ['ok' => false, 'error' => $data['error'] ?? 'AI error', 'http_code' => $code, 'raw' => $data];
    }

    return ['ok' => true, 'data' => $data];
}

function ai_http_get(string $path, int $timeoutSec = 3): array {
    $url = ai_service_url() . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutSec);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSec);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) return ['ok' => false, 'error' => 'AI service not reachable', 'details' => $err];
    $data = json_decode($resp, true);
    if (!is_array($data)) return ['ok' => false, 'error' => 'Invalid AI response', 'http_code' => $code, 'raw' => $resp];
    if ($code >= 400) return ['ok' => false, 'error' => $data['error'] ?? 'AI error', 'http_code' => $code, 'raw' => $data];
    return ['ok' => true, 'data' => $data];
}

/**
 * ML Recommendation (Market)
 * Returns:
 *  - best_market_to_sell {market, predicted_price_jod}
 *  - best_market_to_buy  {market, predicted_price_jod}
 *  - all_markets [...]
 */
function ai_ml_recommend(string $product, int $year, int $month, int $quantityKg = 500): array {
    $payload = [
        'product' => $product,
        'year' => $year,
        'month' => $month,
        'quantity_kg' => $quantityKg
    ];
    return ai_http_post_json('/recommend', $payload);
}

/** Simple price prediction for one market */
function ai_ml_predict(string $product, string $market, int $year, int $month, int $quantityKg = 500): array {
    $payload = [
        'product' => $product,
        'market' => $market,
        'year' => $year,
        'month' => $month,
        'quantity_kg' => $quantityKg
    ];
    return ai_http_post_json('/predict', $payload);
}


/** Daily price prediction for a specific date (recommended for demo days) */
function ai_ml_predict_daily(string $product, string $market, string $dateYmd, int $quantityKg = 500): array {
    $payload = [
        'product' => $product,
        'market' => $market,
        'date' => $dateYmd,
        'quantity_kg' => $quantityKg
    ];
    return ai_http_post_json('/predict_daily', $payload);
}

function ai_ml_meta(): array {
    return ai_http_get('/meta');
}

/** Farmer: suggested listing price for today (uses historical pattern) */
function ai_ml_suggest_price(string $product, string $market, string $dateYmd, int $quantityKg = 500, ?float $currentPrice = null): array {
    $payload = [
        'product' => $product,
        'market' => $market,
        'date' => $dateYmd,
        'quantity_kg' => max(1, (int)$quantityKg),
    ];
    if ($currentPrice !== null) {
        $payload['current_price'] = (float)$currentPrice;
    }
    return ai_http_post_json('/suggest_price', $payload);
}
?>
