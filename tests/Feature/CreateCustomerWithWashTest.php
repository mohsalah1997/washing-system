<?php

namespace Tests\Feature;

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class CreateCustomerWithWashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'price_per_unit', 'value' => '2.5']);
        Setting::create(['key' => 'minimum_amount', 'value' => '15']);

        Gate::before(fn () => true);
    }

    public function test_it_creates_customer_with_first_wash(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'زبون جديد',
                'phone' => '0501234567',
                'balance_type' => 'none',
                'add_first_wash' => true,
                'wash_reading_date' => '2026-05-30',
                'wash_reading_value' => 4,
                'wash_price_per_unit' => 2.5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::where('phone', '0501234567')->first();

        $this->assertNotNull($customer);
        $this->assertSame('زبون جديد', $customer->name);

        $this->assertDatabaseHas('meter_readings', [
            'customer_id' => $customer->id,
            'reading_value' => 4,
            'amount' => 15,
            'source' => 'admin_panel',
            'is_approved' => true,
        ]);

        $this->assertSame(1, MeterReading::where('customer_id', $customer->id)->count());
    }

    public function test_it_creates_customer_without_wash_when_toggle_disabled(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'زبون بدون غسلة',
                'phone' => '0509876543',
                'balance_type' => 'none',
                'add_first_wash' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::where('phone', '0509876543')->first();

        $this->assertNotNull($customer);
        $this->assertSame(0, MeterReading::where('customer_id', $customer->id)->count());
    }
}
