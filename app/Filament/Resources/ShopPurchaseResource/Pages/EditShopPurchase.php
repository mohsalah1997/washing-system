<?php

namespace App\Filament\Resources\ShopPurchaseResource\Pages;

use App\Filament\Resources\ShopPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShopPurchase extends EditRecord
{
    protected static string $resource = ShopPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث المشترى';
    }
}
