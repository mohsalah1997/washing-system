<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;
use RuntimeException;

class MeterReadingCalculator
{
    public function getLastWashOrder(Customer $customer, ?int $excludeReadingId = null): ?array
    {
        $query = $customer->meterReadings()
            ->orderByDesc('reading_date')
            ->orderByDesc('id');

        if ($excludeReadingId) {
            $query->where('id', '!=', $excludeReadingId);
        }

        $lastReading = $query->first();

        if (! $lastReading) {
            return null;
        }

        return [
            'date' => $lastReading->reading_date->format('Y-m-d'),
            'weight' => (float) $lastReading->reading_value,
            'cost' => (float) $lastReading->amount,
            'price_per_unit' => (float) $lastReading->price_per_unit,
        ];
    }

    /**
     * @deprecated Kept for backward compatibility with custom SMS templates.
     */
    public function getPreviousReadingValue(Customer $customer, ?int $excludeReadingId = null): float
    {
        $last = $this->getLastWashOrder($customer, $excludeReadingId);

        return $last['weight'] ?? 0;
    }

    public function calculateFromWeight(float $weightKg, ?float $pricePerKilo = null): array
    {
        if ($weightKg <= 0) {
            throw new RuntimeException('Weight must be greater than zero.');
        }

        $resolvedPricePerUnit = $pricePerKilo ?? (float) Setting::get('price_per_unit', 0);
        $amount = $weightKg * $resolvedPricePerUnit;
        $minimumAmount = (float) Setting::get('minimum_amount', 0);

        if ($amount < $minimumAmount) {
            $amount = $minimumAmount;
        }

        return [
            'reading_value' => $weightKg,
            'consumption' => $weightKg,
            'price_per_unit' => $resolvedPricePerUnit,
            'amount' => $amount,
            'net_amount' => $amount,
        ];
    }
}
