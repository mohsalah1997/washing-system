<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\MeterReadingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private MeterReadingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('price_per_unit', '5');
        Setting::set('minimum_amount', '15');

        $this->calculator = new MeterReadingCalculator;
    }

    public function test_it_calculates_cost_from_weight_and_price_per_kilo(): void
    {
        $result = $this->calculator->calculateFromWeight(4, 5);

        $this->assertSame(4.0, $result['reading_value']);
        $this->assertSame(20.0, $result['amount']);
        $this->assertSame(5.0, $result['price_per_unit']);
    }

    public function test_it_applies_minimum_amount_when_cost_is_lower(): void
    {
        $result = $this->calculator->calculateFromWeight(2, 5);

        $this->assertSame(15.0, $result['amount']);
    }

    public function test_it_rejects_zero_or_negative_weight(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->calculator->calculateFromWeight(0, 5);
    }
}
