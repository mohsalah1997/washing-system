<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WashingStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $customersCount = Customer::query()->count();
        $unapprovedReadingsCount = MeterReading::query()->where('is_approved', false)->count();

        $approvedReadingsTotal = (float) MeterReading::query()
            ->where('is_approved', true)
            ->sum('amount');

        $paymentsTotal = (float) Payment::query()->sum('amount');
        $initialBalancesTotal = (float) Customer::query()->sum('initial_balance');

        $monthApprovedReadingsTotal = (float) MeterReading::query()
            ->where('is_approved', true)
            ->whereBetween('reading_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $monthPaymentsTotal = (float) Payment::query()
            ->whereBetween('payment_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $netBalance = $initialBalancesTotal + $paymentsTotal - $approvedReadingsTotal;

        return [
            Stat::make('عدد الزبائن', number_format($customersCount))
                ->description('إجمالي الزبائن المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('عمليات غير معتمدة', number_format($unapprovedReadingsCount))
                ->description('بانتظار المراجعة والاعتماد')
                ->descriptionIcon('heroicon-m-clock')
                ->color($unapprovedReadingsCount > 0 ? 'warning' : 'success'),

            Stat::make('إجمالي تكلفة الغسيل المعتمدة', number_format($approvedReadingsTotal, 2) . ' ₪')
                ->description('كل الفترات')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('danger'),

            Stat::make('إجمالي الدفعات', number_format($paymentsTotal, 2) . ' ₪')
                ->description('كل الفترات')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('تكلفة الغسيل — الشهر الحالي', number_format($monthApprovedReadingsTotal, 2) . ' ₪')
                ->description('مجموع العمليات المعتمدة هذا الشهر')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('تحصيل الشهر الحالي', number_format($monthPaymentsTotal, 2) . ' ₪')
                ->description('مجموع الدفعات هذا الشهر')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make(
                'الصافي العام',
                ($netBalance >= 0 ? 'له رصيد: ' : 'عليه مبلغ: ') . number_format(abs($netBalance), 2) . ' ₪'
            )
                ->description('الرصيد الكلي لجميع الزبائن')
                ->descriptionIcon($netBalance >= 0 ? 'heroicon-m-scale' : 'heroicon-m-exclamation-circle')
                ->color($netBalance >= 0 ? 'warning' : 'danger'),
        ];
    }
}
