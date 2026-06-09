<?php

namespace App\Services;

use App\Models\MeterReading;
use App\Models\MeterReadingSmsLog;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class MeterReadingSmsService
{
    public function __construct(
        protected SmsApprovalMessageBuilder $messageBuilder,
        protected TweetsmsService $tweetsmsService,
        protected SmsSegmentCounter $segmentCounter,
    ) {}

    public function formatPreviewWithSegmentCost(string $message): HtmlString
    {
        return new HtmlString($this->segmentCounter->renderPreviewHtml($message));
    }

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

        return $this->dispatchSms($record, $message, 'ready', updateSentAt: false);
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

        return $this->dispatchSms($record, $message, $type, updateSentAt: true);
    }

    private function dispatchSms(MeterReading $record, string $message, string $type, bool $updateSentAt): string
    {
        if (Setting::get('sms_enabled', '0') === '1') {
            return $this->sendViaProvider($record, $message, $type, $updateSentAt);
        }

        return $this->sendDemo($record, $message, $type, $updateSentAt);
    }

    private function sendViaProvider(MeterReading $record, string $message, string $type, bool $updateSentAt): string
    {
        if (! $this->tweetsmsService->isConfigured()) {
            Notification::make()
                ->title('فشل إرسال SMS')
                ->body('إعدادات مزود SMS غير مكتملة. أضف مفتاح API واسم المرسل من إعدادات رسائل SMS.')
                ->danger()
                ->send();

            return '';
        }

        $phone = $record->customer->phone ?? '';
        if (! filled($phone)) {
            Notification::make()
                ->title('فشل إرسال SMS')
                ->body('لا يوجد رقم هاتف للزبون.')
                ->danger()
                ->send();

            return '';
        }

        $result = $this->tweetsmsService->sendSms($phone, $message);

        if (! $result['success']) {
            Notification::make()
                ->title('فشل إرسال SMS')
                ->body($result['desc'])
                ->danger()
                ->send();

            return '';
        }

        $this->persistSmsLog($record, $message, $type, $updateSentAt);

        Notification::make()
            ->title('تم إرسال الرسالة بنجاح')
            ->body($message)
            ->success()
            ->send();

        return $message;
    }

    private function sendDemo(MeterReading $record, string $message, string $type, bool $updateSentAt): string
    {
        $this->persistSmsLog($record, $message, $type, $updateSentAt);

        Notification::make()
            ->title('إشعار SMS (تجريبي)')
            ->body("سيتم إرسال الرسالة التالية إلى الزبون:\n{$message}")
            ->success()
            ->send();

        return $message;
    }

    private function persistSmsLog(MeterReading $record, string $message, string $type, bool $updateSentAt): void
    {
        DB::transaction(function () use ($record, $type, $message, $updateSentAt) {
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

            if ($updateSentAt) {
                $record->forceFill(['sms_sent_at' => now()])->save();
            }
        });
    }
}
