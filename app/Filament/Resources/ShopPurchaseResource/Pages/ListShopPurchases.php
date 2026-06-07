<?php

namespace App\Filament\Resources\ShopPurchaseResource\Pages;

use App\Filament\Resources\ShopPurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShopPurchases extends ListRecords
{
    protected static string $resource = ShopPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة مشترى'),
        ];
    }
}
