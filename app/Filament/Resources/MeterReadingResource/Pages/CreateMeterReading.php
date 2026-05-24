<?php

namespace App\Filament\Resources\MeterReadingResource\Pages;

use App\Filament\Resources\MeterReadingResource;
use App\Services\MeterReadingCalculator;
use Filament\Resources\Pages\CreateRecord;

class CreateMeterReading extends CreateRecord
{
    protected static string $resource = MeterReadingResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم حفظ عملية الغسيل بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = $this->applyCalculatedFields($data);
        $data['is_approved'] = true;

        return $data;
    }

    private function applyCalculatedFields(array $data): array
    {
        $calculated = app(MeterReadingCalculator::class)->calculateFromWeight(
            (float) ($data['reading_value'] ?? 0),
            isset($data['price_per_unit']) ? (float) $data['price_per_unit'] : null,
        );

        $data['consumption'] = $calculated['consumption'];
        $data['amount'] = $calculated['amount'];
        $data['net_amount'] = $calculated['net_amount'];
        $data['price_per_unit'] = $calculated['price_per_unit'];

        return $data;
    }
}
