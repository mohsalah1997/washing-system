<?php

namespace App\Services;

use App\Models\MeterReading;
use App\Models\Setting;

class SmsApprovalMessageBuilder
{
    public static function defaultTemplate(): string
    {
        return "تم اعتماد عملية غسيل جديدة.\n"
            . "وزن الغسيل: {reading_value} كغ\n"
            . "سعر الكيلو: {price_per_unit}\n"
            . "تكلفة الغسل: {amount}\n"
            . 'رصيدك الحالي: {balance}';
    }

    public static function defaultCorrectionTemplate(): string
    {
        return "تم تعديل عملية الغسيل.\n"
            . "وزن الغسيل: {reading_value} كغ\n"
            . "سعر الكيلو: {price_per_unit}\n"
            . "تكلفة الغسل: {amount}\n"
            . 'رصيدك الحالي: {balance}';
    }

    public static function defaultReadyTemplate(): string
    {
        return "مرحباً {customer_name}،\n"
            . "غسيلك جاهز للاستلام.\n"
            . "يرجى التوجه للمغسلة لاستلامه.\n"
            . 'تكلفة الغسل: {amount} ₪';
    }

    /**
     * @return array<int, array{placeholder: string, label: string}>
     */
    public static function availableVariables(): array
    {
        return [
            ['placeholder' => '{reading_value}', 'label' => 'وزن الغسيل (كغ)'],
            ['placeholder' => '{price_per_unit}', 'label' => 'سعر الكيلو'],
            ['placeholder' => '{amount}', 'label' => 'تكلفة الغسل'],
            ['placeholder' => '{balance}', 'label' => 'رصيد الزبون (نص جاهز)'],
            ['placeholder' => '{customer_name}', 'label' => 'اسم الزبون'],
            ['placeholder' => '{customer_phone}', 'label' => 'هاتف الزبون'],
            ['placeholder' => '{reading_date}', 'label' => 'تاريخ العملية'],
            ['placeholder' => '{previous_reading}', 'label' => 'آخر وزن (قديم)'],
            ['placeholder' => '{consumption}', 'label' => 'الوزن (قديم)'],
        ];
    }

    public function build(MeterReading $record, string $type = 'initial'): string
    {
        $record->loadMissing('customer');
        $customer = $record->customer;

        if (! $customer) {
            return '';
        }

        $balance = $customer->balance;
        $balanceText = $balance > 0
            ? 'لك رصيد: ' . number_format($balance, 2) . ' ₪'
            : ($balance < 0
                ? 'عليك مبلغ: ' . number_format(abs($balance), 2) . ' ₪'
                : 'الرصيد صفر');

        $settingKey = $type === 'correction'
            ? 'sms_correction_message_template'
            : 'sms_approval_message_template';

        $defaultTemplate = $type === 'correction'
            ? self::defaultCorrectionTemplate()
            : self::defaultTemplate();

        $template = Setting::get($settingKey, $defaultTemplate);

        $replacements = [
            '{previous_reading}' => (string) $record->reading_value,
            '{reading_value}' => (string) $record->reading_value,
            '{consumption}' => (string) $record->consumption,
            '{price_per_unit}' => number_format((float) $record->price_per_unit, 2),
            '{amount}' => number_format((float) $record->amount, 2),
            '{balance}' => $balanceText,
            '{customer_name}' => $customer->name,
            '{customer_phone}' => $customer->phone ?? '',
            '{reading_date}' => $record->reading_date?->format('Y-m-d') ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template,
        );
    }

    public function buildReady(MeterReading $record): string
    {
        $record->loadMissing('customer');
        $customer = $record->customer;

        if (! $customer) {
            return '';
        }

        $template = Setting::get(
            'sms_ready_message_template',
            self::defaultReadyTemplate(),
        );

        $balance = $customer->balance;
        $balanceText = $balance > 0
            ? 'لك رصيد: ' . number_format($balance, 2) . ' ₪'
            : ($balance < 0
                ? 'عليك مبلغ: ' . number_format(abs($balance), 2) . ' ₪'
                : 'الرصيد صفر');

        $replacements = [
            '{reading_value}' => (string) $record->reading_value,
            '{price_per_unit}' => number_format((float) $record->price_per_unit, 2),
            '{amount}' => number_format((float) $record->amount, 2),
            '{balance}' => $balanceText,
            '{customer_name}' => $customer->name,
            '{customer_phone}' => $customer->phone ?? '',
            '{reading_date}' => $record->reading_date?->format('Y-m-d') ?? '',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template,
        );
    }
}
