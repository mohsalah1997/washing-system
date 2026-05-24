<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\MeterReadingSmsLog;
use App\Models\Setting;
use App\Services\MeterReadingSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingSmsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::create(['key' => 'price_per_unit', 'value' => '2.5']);
        Setting::create(['key' => 'minimum_amount', 'value' => '15']);
        Setting::create(['key' => 'sms_enabled', 'value' => '0']);
    }

    public function test_new_reading_has_no_sms_sent_by_default(): void
    {
        $reading = $this->createReading();

        $this->assertNull($reading->sms_sent_at);
        $this->assertFalse($reading->hasSmsBeenSent());
    }

    public function test_sms_service_logs_and_sets_sms_sent_at(): void
    {
        $reading = $this->createReading(['reading_value' => 10]);

        $message = app(MeterReadingSmsService::class)->send($reading, 'initial');

        $this->assertNotEmpty($message);
        $reading->refresh();
        $this->assertNotNull($reading->sms_sent_at);
        $this->assertDatabaseHas('meter_reading_sms_logs', [
            'meter_reading_id' => $reading->id,
            'type' => 'initial',
        ]);
        $this->assertEquals(1, MeterReadingSmsLog::where('meter_reading_id', $reading->id)->count());
    }

    public function test_note_only_change_does_not_require_correction_sms(): void
    {
        $reading = $this->createReading();
        $reading->update(['sms_sent_at' => now()]);

        $this->assertFalse($reading->requiresCorrectionSms([
            'reading_value' => $reading->reading_value,
            'price_per_unit' => $reading->price_per_unit,
            'reading_date' => $reading->reading_date->format('Y-m-d'),
            'note' => 'ملاحظة محدثة',
        ]));
    }

    public function test_weight_change_requires_correction_sms_after_sent(): void
    {
        $reading = $this->createReading();
        $reading->update(['sms_sent_at' => now()]);

        $this->assertTrue($reading->requiresCorrectionSms([
            'reading_value' => 12,
            'price_per_unit' => $reading->price_per_unit,
            'reading_date' => $reading->reading_date->format('Y-m-d'),
        ]));
    }

    public function test_reading_date_change_requires_correction_sms_after_sent(): void
    {
        $reading = $this->createReading(['reading_date' => '2026-05-01']);
        $reading->update(['sms_sent_at' => now()]);

        $this->assertTrue($reading->requiresCorrectionSms([
            'reading_value' => $reading->reading_value,
            'price_per_unit' => $reading->price_per_unit,
            'reading_date' => '2026-05-02',
        ]));
    }

    public function test_correction_sms_blocked_when_data_unchanged(): void
    {
        $reading = $this->createReading(['reading_value' => 10]);

        app(MeterReadingSmsService::class)->send($reading, 'initial');
        $reading->refresh();

        $message = app(MeterReadingSmsService::class)->send($reading, 'correction');

        $this->assertSame('', $message);
        $this->assertEquals(1, MeterReadingSmsLog::where('meter_reading_id', $reading->id)->count());
    }

    public function test_data_changed_since_last_sms_detects_weight_change(): void
    {
        $reading = $this->createReading(['reading_value' => 10]);

        app(MeterReadingSmsService::class)->send($reading, 'initial');
        $reading->refresh();

        $this->assertFalse($reading->dataChangedSinceLastSms([
            'reading_value' => 10,
            'price_per_unit' => 2.5,
            'reading_date' => '2026-05-10',
        ]));

        $this->assertTrue($reading->dataChangedSinceLastSms([
            'reading_value' => 12,
            'price_per_unit' => 2.5,
            'reading_date' => '2026-05-10',
        ]));
    }

    public function test_send_ready_creates_log_without_updating_sms_sent_at(): void
    {
        $reading = $this->createReading();

        $message = app(MeterReadingSmsService::class)->sendReady($reading);

        $this->assertNotEmpty($message);
        $reading->refresh();
        $this->assertNull($reading->sms_sent_at);
        $this->assertTrue($reading->hasReadySmsBeenSent());
        $this->assertDatabaseHas('meter_reading_sms_logs', [
            'meter_reading_id' => $reading->id,
            'type' => 'ready',
        ]);
    }

    public function test_build_ready_replaces_customer_name(): void
    {
        $reading = $this->createReading();

        $message = app(MeterReadingSmsService::class)->buildReadyMessage($reading);

        $this->assertStringContainsString('Test Customer', $message);
        $this->assertStringContainsString('جاهز', $message);
    }

    private function createReading(array $overrides = []): MeterReading
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '0500000099',
            'initial_balance' => 0,
        ]);

        return MeterReading::create(array_merge([
            'customer_id' => $customer->id,
            'reading_value' => 6,
            'reading_date' => '2026-05-10',
            'consumption' => 6,
            'price_per_unit' => 2.5,
            'amount' => 15,
            'net_amount' => 15,
            'is_approved' => true,
        ], $overrides));
    }
}
