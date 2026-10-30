<?php
/**
 * Pricing Engine Tests
 * Tests zone determination, fee calculation, and weight add-ons.
 */

use PHPUnit\Framework\TestCase;

// Load pricing (which also loads ai_engine via require_once)
require_once __DIR__ . '/../includes/pricing.php';

class PricingTest extends TestCase
{
    // ─── Zone Determination ───

    public function testSameZone(): void
    {
        $this->assertEquals('same', pricing_zone('Amman', 'Amman'));
        $this->assertEquals('same', pricing_zone('Irbid', 'Irbid'));
    }

    public function testNearZone(): void
    {
        $this->assertEquals('near', pricing_zone('Amman', 'Zarqa'));
        $this->assertEquals('near', pricing_zone('Amman', 'Salt'));
        $this->assertEquals('near', pricing_zone('Irbid', 'Ajloun'));
        // Near should be symmetric
        $this->assertEquals('near', pricing_zone('Zarqa', 'Amman'));
    }

    public function testFarZone(): void
    {
        $this->assertEquals('far', pricing_zone('Amman', 'Aqaba'));
        $this->assertEquals('far', pricing_zone('Irbid', 'Maan'));
    }

    public function testEmptyLocationReturnsFar(): void
    {
        $this->assertEquals('far', pricing_zone('', 'Amman'));
        $this->assertEquals('far', pricing_zone('Amman', ''));
    }

    // ─── Base Fee ───

    public function testBaseFeeSame(): void
    {
        $this->assertEquals(5.00, pricing_base_fee('same'));
    }

    public function testBaseFeeNear(): void
    {
        $this->assertEquals(7.00, pricing_base_fee('near'));
    }

    public function testBaseFeeFar(): void
    {
        $this->assertEquals(10.00, pricing_base_fee('far'));
    }

    // ─── Weight Add-on ───

    public function testWeightAddonLight(): void
    {
        $this->assertEquals(0.00, pricing_weight_addon(0));
        $this->assertEquals(0.00, pricing_weight_addon(10));
        $this->assertEquals(0.00, pricing_weight_addon(20));
    }

    public function testWeightAddonMedium(): void
    {
        $this->assertEquals(1.00, pricing_weight_addon(21));
        $this->assertEquals(1.00, pricing_weight_addon(50));
    }

    public function testWeightAddonHeavy(): void
    {
        $this->assertEquals(3.00, pricing_weight_addon(51));
        $this->assertEquals(3.00, pricing_weight_addon(100));
    }

    public function testWeightAddonVeryHeavy(): void
    {
        $this->assertEquals(5.00, pricing_weight_addon(101));
        $this->assertEquals(5.00, pricing_weight_addon(500));
    }

    // ─── Full Calculation ───

    public function testCalculateDeliveryPricingSameZone(): void
    {
        $result = calculate_delivery_pricing('Amman', 'Amman', 10);

        $this->assertEquals('same', $result['zone']);
        $this->assertEquals(5.00, $result['base_fee']);
        $this->assertEquals(0.00, $result['weight_addon']);
        $this->assertEquals(5.00, $result['delivery_fee']);
        $this->assertEquals(0.15, $result['platform_fee']); // 3% of 5.00
        $this->assertEquals(4.85, $result['driver_earning']);
    }

    public function testCalculateDeliveryPricingFarZoneHeavy(): void
    {
        $result = calculate_delivery_pricing('Amman', 'Aqaba', 150);

        $this->assertEquals('far', $result['zone']);
        $this->assertEquals(10.00, $result['base_fee']);
        $this->assertEquals(5.00, $result['weight_addon']);
        $this->assertEquals(15.00, $result['delivery_fee']);
        $this->assertEquals(0.45, $result['platform_fee']); // 3% of 15.00
        $this->assertEquals(14.55, $result['driver_earning']);
    }

    public function testPlatformFeeIsThreePercentOfDeliveryOnly(): void
    {
        $result = calculate_delivery_pricing('Irbid', 'Ajloun', 30);

        $expectedPlatformFee = round($result['delivery_fee'] * 0.03, 2);
        $this->assertEquals($expectedPlatformFee, $result['platform_fee']);
        $this->assertEquals(round($result['delivery_fee'] - $result['platform_fee'], 2), $result['driver_earning']);
    }

    // ─── Location Normalization ───

    public function testPricingNormalizePlace(): void
    {
        $this->assertEquals('salt', pricing_normalize_place('Al Salt'));
        $this->assertEquals('zarqa', pricing_normalize_place('Al Zarqa'));
        $this->assertEquals('amman', pricing_normalize_place('Amman'));
    }
}
