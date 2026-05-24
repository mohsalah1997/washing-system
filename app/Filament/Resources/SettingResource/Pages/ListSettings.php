<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Pages\LaundryPricingSettings;
use App\Filament\Pages\SmsNotificationSettings;
use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('laundryPricing')
                ->label('أسعار الغسيل')
                ->icon('heroicon-o-scale')
                ->url(fn (): string => LaundryPricingSettings::getUrl())
                ->visible(fn (): bool => LaundryPricingSettings::canAccess()),
            Actions\Action::make('smsSettings')
                ->label('إعدادات رسائل SMS')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->url(fn (): string => SmsNotificationSettings::getUrl())
                ->visible(fn (): bool => SmsNotificationSettings::canAccess()),
            Actions\CreateAction::make()->label('إضافة إعداد'),
        ];
    }
}

