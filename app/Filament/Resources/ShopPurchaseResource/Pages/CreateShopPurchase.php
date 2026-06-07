<?php

namespace App\Filament\Resources\ShopPurchaseResource\Pages;

use App\Filament\Resources\ShopPurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateShopPurchase extends CreateRecord
{
    protected static string $resource = ShopPurchaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم تسجيل المشترى بنجاح';
    }
}
