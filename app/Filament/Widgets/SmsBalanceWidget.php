<?php

namespace App\Filament\Widgets;

use App\Services\TweetsmsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SmsBalanceWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $result = Cache::remember('tweetsms_balance', now()->addMinutes(5), function () {
            return app(TweetsmsService::class)->checkBalance();
        });

        if ($result['code'] === null && $result['desc'] === 'غير مُعد') {
            return [
                Stat::make('رصيد SMS', 'غير مُعد')
                    ->description('أضف مفتاح API من إعدادات رسائل SMS')
                    ->descriptionIcon('heroicon-m-cog-6-tooth')
                    ->color('gray'),
            ];
        }

        if ($result['success']) {
            return [
                Stat::make('رصيد SMS', $result['balance'] ?? $result['desc'])
                    ->description('رصيد حساب Tweetsms')
                    ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                    ->color('success'),
            ];
        }

        return [
            Stat::make('رصيد SMS', 'غير متاح')
                ->description($result['desc'])
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($result['code'] === -110 ? 'danger' : 'warning'),
        ];
    }
}
