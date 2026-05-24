<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\SmsApprovalMessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsApprovalMessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    private SmsApprovalMessageBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new SmsApprovalMessageBuilder;
    }

    public function test_default_template_uses_laundry_terminology(): void
    {
        $template = SmsApprovalMessageBuilder::defaultTemplate();

        $this->assertStringContainsString('عملية غسيل', $template);
        $this->assertStringContainsString('وزن الغسيل', $template);
        $this->assertStringContainsString('تكلفة الغسل', $template);
    }

    public function test_default_ready_template_mentions_pickup(): void
    {
        $template = SmsApprovalMessageBuilder::defaultReadyTemplate();

        $this->assertStringContainsString('جاهز للاستلام', $template);
        $this->assertStringContainsString('{customer_name}', $template);
        $this->assertStringContainsString('للمغسلة', $template);
    }

    public function test_it_replaces_custom_template_placeholders(): void
    {
        Setting::set('sms_approval_message_template', 'وزن: {reading_value} تكلفة: {amount}');

        $customer = Customer::create([
            'name' => 'أحمد',
            'phone' => '0501234567',
            'initial_balance' => 0,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 5,
            'reading_date' => '2026-05-01',
            'consumption' => 5,
            'price_per_unit' => 3,
            'amount' => 15.00,
            'is_approved' => true,
        ]);

        $message = $this->builder->build($reading);

        $this->assertSame('وزن: 5.000 تكلفة: 15.00', $message);
    }

    public function test_it_includes_price_per_unit_in_template(): void
    {
        Setting::set('sms_approval_message_template', 'سعر: {price_per_unit}');

        $customer = Customer::create([
            'name' => 'زبون',
            'phone' => '0500000099',
            'initial_balance' => 0,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 10,
            'reading_date' => '2026-05-01',
            'consumption' => 10,
            'price_per_unit' => 2.5,
            'amount' => 25,
            'is_approved' => true,
        ]);

        $this->assertSame('سعر: 2.50', $this->builder->build($reading));
    }

    public function test_it_returns_empty_string_when_customer_is_missing(): void
    {
        $reading = new MeterReading([
            'reading_value' => 100,
            'consumption' => 10,
            'amount' => 20,
        ]);

        $this->assertSame('', $this->builder->build($reading));
    }

    public function test_balance_placeholder_when_customer_has_credit(): void
    {
        Setting::set('sms_approval_message_template', 'رصيد: {balance}');

        $customer = Customer::create([
            'name' => 'زبون',
            'phone' => '0500000000',
            'initial_balance' => 50,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 10,
            'reading_date' => '2026-05-01',
            'consumption' => 10,
            'price_per_unit' => 1,
            'amount' => 0,
            'is_approved' => false,
        ]);

        $message = $this->builder->build($reading);

        $this->assertSame('رصيد: لك رصيد: 50.00 ₪', $message);
    }

    public function test_balance_placeholder_when_customer_owes(): void
    {
        Setting::set('sms_approval_message_template', 'رصيد: {balance}');

        $customer = Customer::create([
            'name' => 'زبون',
            'phone' => '0500000001',
            'initial_balance' => 0,
        ]);

        MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 100,
            'reading_date' => '2026-04-01',
            'consumption' => 100,
            'price_per_unit' => 1,
            'amount' => 75.50,
            'is_approved' => true,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 10,
            'reading_date' => '2026-05-01',
            'consumption' => 10,
            'price_per_unit' => 1,
            'amount' => 10,
            'is_approved' => false,
        ]);

        $message = $this->builder->build($reading);

        $this->assertSame('رصيد: عليك مبلغ: 75.50 ₪', $message);
    }

    public function test_balance_placeholder_when_balance_is_zero(): void
    {
        Setting::set('sms_approval_message_template', 'رصيد: {balance}');

        $customer = Customer::create([
            'name' => 'زبون',
            'phone' => '0500000002',
            'initial_balance' => 0,
        ]);

        Payment::create([
            'customer_id' => $customer->id,
            'amount' => 100,
            'payment_date' => '2026-05-01',
            'method' => 'cash',
        ]);

        MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 100,
            'reading_date' => '2026-04-01',
            'consumption' => 100,
            'price_per_unit' => 1,
            'amount' => 100,
            'is_approved' => true,
        ]);

        $reading = MeterReading::create([
            'customer_id' => $customer->id,
            'reading_value' => 10,
            'reading_date' => '2026-05-01',
            'consumption' => 10,
            'price_per_unit' => 1,
            'amount' => 0,
            'is_approved' => false,
        ]);

        $message = $this->builder->build($reading);

        $this->assertSame('رصيد: الرصيد صفر', $message);
    }
}
