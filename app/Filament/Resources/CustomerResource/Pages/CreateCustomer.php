<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء الزبون بنجاح';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
}
