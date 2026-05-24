<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم حفظ بيانات الزبون';
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
