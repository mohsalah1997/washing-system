<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FieldMeterReadingSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'price_per_unit', 'value' => '2.5']);
        Setting::create(['key' => 'minimum_amount', 'value' => '15']);
    }

    public function test_it_syncs_new_wash_order_with_weight_pricing(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000000',
            'initial_balance' => 0,
        ]);

        $response = $this->postJson('/api/field/readings/sync', [
            'readings' => [
                [
                    'client_uuid' => '11111111-1111-1111-1111-111111111111',
                    'customer_id' => $customer->id,
                    'reading_date' => '2026-05-01',
                    'reading_value' => 4,
                    'note' => 'offline',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.synced', 1)
            ->assertJsonPath('summary.rejected', 0);

        $this->assertDatabaseHas('meter_readings', [
            'customer_id' => $customer->id,
            'reading_value' => 4,
            'amount' => 15,
            'source' => 'mobile_offline_sync',
            'is_approved' => true,
        ]);
    }

    public function test_it_handles_client_uuid_idempotency(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000001',
            'initial_balance' => 0,
        ]);

        $payload = [
            'readings' => [[
                'client_uuid' => '22222222-2222-2222-2222-222222222222',
                'customer_id' => $customer->id,
                'reading_date' => '2026-05-02',
                'reading_value' => 10,
            ]],
        ];

        $this->postJson('/api/field/readings/sync', $payload)->assertOk();
        $response = $this->postJson('/api/field/readings/sync', $payload);

        $response->assertOk()
            ->assertJsonPath('summary.synced', 1)
            ->assertJsonPath('summary.rejected', 0);

        $this->assertEquals(1, MeterReading::query()->where('client_uuid', '22222222-2222-2222-2222-222222222222')->count());
    }

    public function test_it_rejects_zero_weight(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000002',
            'initial_balance' => 0,
        ]);

        $response = $this->postJson('/api/field/readings/sync', [
            'readings' => [[
                'client_uuid' => '33333333-3333-3333-3333-333333333333',
                'customer_id' => $customer->id,
                'reading_date' => '2026-05-04',
                'reading_value' => 0,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.synced', 0)
            ->assertJsonPath('summary.rejected', 1);
    }

    public function test_it_allows_multiple_orders_on_same_day(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000003',
            'initial_balance' => 0,
        ]);

        MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 6,
            'reading_date' => '2026-05-06',
            'consumption' => 6,
            'price_per_unit' => 2.5,
            'amount' => 15,
            'net_amount' => 15,
            'is_approved' => true,
        ]);

        $response = $this->postJson('/api/field/readings/sync', [
            'readings' => [[
                'client_uuid' => '44444444-4444-4444-4444-444444444444',
                'customer_id' => $customer->id,
                'reading_date' => '2026-05-06',
                'reading_value' => 8,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('summary.synced', 1)
            ->assertJsonPath('summary.rejected', 0);

        $this->assertEquals(2, MeterReading::query()->where('customer_id', $customer->id)->count());
    }

    public function test_meter_reading_defaults_to_approved(): void
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000004',
            'initial_balance' => 0,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 5,
            'reading_date' => '2026-05-07',
            'consumption' => 5,
            'price_per_unit' => 2.5,
            'amount' => 15,
            'net_amount' => 15,
        ]);

        $this->assertTrue($reading->is_approved);
        $this->assertDatabaseHas('meter_readings', [
            'id' => $reading->id,
            'is_approved' => true,
        ]);
    }
}
