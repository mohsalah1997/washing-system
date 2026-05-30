<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Services\MeterReadingCalculator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected bool $washCreated = false;

    /** @var array<string, mixed>|null */
    protected ?array $pendingWashData = null;

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->washCreated
            ? 'تم إنشاء الزبون وعملية الغسيل بنجاح'
            : 'تم إنشاء الزبون بنجاح';
    }

    protected function getRedirectUrl(): string
    {
        return CustomerResource::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['add_first_wash'] ?? false) && auth()->user()?->can('create_meter::reading')) {
            $this->pendingWashData = [
                'reading_date' => $data['wash_reading_date'] ?? null,
                'reading_value' => $data['wash_reading_value'] ?? null,
                'price_per_unit' => $data['wash_price_per_unit'] ?? null,
                'note' => $data['wash_note'] ?? null,
            ];
        }

        unset(
            $data['add_first_wash'],
            $data['wash_reading_date'],
            $data['wash_reading_value'],
            $data['wash_price_per_unit'],
            $data['wash_amount'],
            $data['wash_consumption'],
            $data['wash_net_amount'],
            $data['wash_note'],
        );

        $balanceType = $data['balance_type'] ?? 'none';
        $amount = abs((float) ($data['initial_balance_amount'] ?? 0));

        if ($balanceType === 'credit') {
            $data['initial_balance'] = $amount;
        } elseif ($balanceType === 'debit') {
            $data['initial_balance'] = -$amount;
        } else {
            $data['initial_balance'] = 0;
        }

        unset($data['balance_type'], $data['initial_balance_amount']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $customer = static::getModel()::create($data);

            if ($this->pendingWashData !== null) {
                $calculated = app(MeterReadingCalculator::class)->calculateFromWeight(
                    (float) ($this->pendingWashData['reading_value'] ?? 0),
                    isset($this->pendingWashData['price_per_unit'])
                        ? (float) $this->pendingWashData['price_per_unit']
                        : null,
                );

                $customer->meterReadings()->create([
                    'reading_date' => $this->pendingWashData['reading_date'],
                    'reading_value' => $calculated['reading_value'],
                    'consumption' => $calculated['consumption'],
                    'price_per_unit' => $calculated['price_per_unit'],
                    'amount' => $calculated['amount'],
                    'net_amount' => $calculated['net_amount'],
                    'is_approved' => true,
                    'source' => 'admin_panel',
                    'note' => $this->pendingWashData['note'] ?? null,
                ]);

                $this->washCreated = true;
            }

            return $customer;
        });
    }
}
