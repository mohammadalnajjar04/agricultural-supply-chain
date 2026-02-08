<?php
/**
 * Pricing Engine (PHP)
 * Fair, zone-based delivery pricing for Agri Supply Chain.
 *
 * Rules (JOD):
 * - Zone base fee:
 *   - same area: 5.00
 *   - near areas: 7.00
 *   - far areas: 10.00
 * - Weight add-on (kg):
 *   - <= 20kg: +0.00
 *   - 20-50kg: +1.00
 *   - 50-100kg: +3.00
 *   - > 100kg: +5.00
 * - Platform fee: fixed 3% of delivery fee only (not from product value).
 */

require_once __DIR__ . '/ai_engine.php';

function pricing_normalize_place(string $s): string {
    $s = ai_normalize_location($s);
    // Keep common city labels consistent
    $aliases = [
        'al salt' => 'salt',
        'as salt' => 'salt',
        'balqa' => 'salt',
        'al balqa' => 'salt',
        'al zarqa' => 'zarqa',
        'al mafraq' => 'mafraq',
        'al karak' => 'karak',
        'al aqaba' => 'aqaba',
        'al maan' => 'maan',
        'ma`an' => 'maan',
    ];
    return $aliases[$s] ?? $s;
}

function pricing_zone(string $from, string $to): string {
    $a = pricing_normalize_place($from);
    $b = pricing_normalize_place($to);
    if ($a === '' || $b === '') return 'far';
    if ($a === $b) return 'same';

    // Near neighbors in Jordan (simple, believable for project)
    $near = [
        'irbid' => ['ajloun', 'jerash', 'mafraq'],
        'ajloun' => ['irbid', 'jerash'],
        'jerash' => ['ajloun', 'irbid', 'amman'],
        'amman' => ['zarqa', 'madaba', 'salt', 'jerash'],
        'zarqa' => ['amman', 'mafraq'],
        'madaba' => ['amman', 'karak'],
        'salt' => ['amman', 'jerash'],
        'mafraq' => ['irbid', 'zarqa'],
        'karak' => ['madaba', 'tafila', 'maan'],
        'tafila' => ['karak', 'maan'],
        'maan' => ['tafila', 'aqaba', 'karak'],
        'aqaba' => ['maan'],
    ];

    if (isset($near[$a]) && in_array($b, $near[$a], true)) return 'near';
    if (isset($near[$b]) && in_array($a, $near[$b], true)) return 'near';
    return 'far';
}

function pricing_base_fee(string $zone): float {
    return match ($zone) {
        'same' => 5.00,
        'near' => 7.00,
        default => 10.00,
    };
}

function pricing_weight_addon(float $weightKg): float {
    if ($weightKg <= 20) return 0.00;
    if ($weightKg <= 50) return 1.00;
    if ($weightKg <= 100) return 3.00;
    return 5.00;
}

/**
 * Calculate pricing and split.
 * Returns:
 * - zone, base_fee, weight_addon, delivery_fee, platform_pct, platform_fee, driver_earning
 */
function calculate_delivery_pricing(string $fromLocation, string $toLocation, float $totalWeightKg, float $platformPct = 0.03): array {
    $zone = pricing_zone($fromLocation, $toLocation);
    $base = pricing_base_fee($zone);
    $addon = pricing_weight_addon($totalWeightKg);

    $deliveryFee = round($base + $addon, 2);
    // Platform fee is fixed at 3% (only from delivery fee)
    $platformPct = 0.03;
    $platformFee = round($deliveryFee * 0.03, 2);
    $driverEarn = round($deliveryFee - $platformFee, 2);

    return [
        'zone' => $zone,
        'base_fee' => $base,
        'weight_addon' => $addon,
        'delivery_fee' => $deliveryFee,
        'platform_pct' => $platformPct,
        'platform_fee' => $platformFee,
        'driver_earning' => $driverEarn,
    ];
}
