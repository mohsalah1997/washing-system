<?php

namespace App\Services;

use App\Models\MeterReading;
use App\Models\MeterReadingSmsLog;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MeterReadingSmsService
{
    public function __construct(
        protected SmsApprovalMessageBuilder $messageBuilder,
    ) {}

    public function buildMessage(MeterReading $record, string $type = 'initial'): string
    {
        return $this->messageBuilder->build($record, $type);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function buildMessageFromData(MeterReading $record, array $data, string $type = 'correction'): string
    {
        $preview = $record->replicate();
        $preview->fill([
            'reading_value' => $data['reading_value'] ?? $record->reading_value,
            'price_per_unit' => $data['price_per_unit'] ?? $record->price_per_unit,
            'amount' => $data['amount'] ?? $record->amount,
            'consumption' => $data['consumption'] ?? $record->consumption,
            'net_amount' => $data['net_amount'] ?? $record->net_amount,
            'reading_date' => $data['reading_date'] ?? $record->reading_date,
        ]);
        $preview->setRelation('customer', $record->customer);

        return $this->buildMessage($preview, $type);
    }

    public function buildReadyMessage(MeterReading $record): string
    {
        return $this->messageBuilder->buildReady($record);
    }

    public function sendReady(MeterReading $record): string
    {
        $record->loadMissing('customer');

        if (! $record->customer) {
            return '';
        }

        $message = $this->buildReadyMessage($record);

        if ($message === '') {
            return '';
        }

        DB::transaction(function () use ($record, $message) {
            MeterReadingSmsLog::create([
                'meter_reading_id' => $record->id,
                'user_id' => Auth::id(),
                'type' => 'ready',
                'message' => $message,
                'snapshot' => [
                    'weight' => (float) $record->reading_value,
                    'price_per_unit' => (float) $record->price_per_unit,
                    'amount' => (float) $record->amount,
                    'reading_date' => $record->reading_date?->format('Y-m-d'),
                ],
            ]);
        });

        Notification::make()
            ->title('إشعار SMS (تجريبي)')
            ->body("سيتم إرسال رسالة الجاهزية التالية إلى الزبون:\n{$message}")
            ->success()
            ->send();

        if (Setting::get('sms_enabled', '0') === '1') {
            // هنا تقدر تضيف كود إرسال SMS الحقيقي باستخدام أي مزود خدمة
        }

        return $message;
    }

    public function send(MeterReading $record, string $type = 'initial'): string
    {
        $record->loadMissing('customer');

        if (! $record->customer) {
            return '';
        }

        $message = $this->buildMessage($record, $type);

        if ($message === '') {
            return '';
        }

        if ($type === 'correction' && ! $record->dataChangedSinceLastSms()) {
            Notification::make()
                ->title('لم يتغيّر شيء')
                ->body('لم يتغيّر وزن الغسيل أو السعر أو التاريخ منذ آخر SMS. عدّل البيانات أولاً.')
                ->warning()
                ->send();

            return '';
        }

        DB::transaction(function () use ($record, $type, $message) {
            MeterReadingSmsLog::create([
                'meter_reading_id' => $record->id,
                'user_id' => Auth::id(),
                'type' => $type,
                'message' => $message,
                'snapshot' => [
                    'weight' => (float) $record->reading_value,
                    'price_per_unit' => (float) $record->price_per_unit,
                    'amount' => (float) $record->amount,
                    'reading_date' => $record->reading_date?->format('Y-m-d'),
                ],
            ]);

            $record->forceFill(['sms_sent_at' => now()])->save();
        });

        Notification::make()
            ->title('إشعار SMS (تجريبي)')
            ->body("سيتم إرسال الرسالة التالية إلى الزبون:\n{$message}")
            ->success()
            ->send();

        if (Setting::get('sms_enabled', '0') === '1') {
            // هنا تقدر تضيف كود إرسال SMS الحقيقي باستخدام أي مزود خدمة
        }

        return $message;
    }
}
