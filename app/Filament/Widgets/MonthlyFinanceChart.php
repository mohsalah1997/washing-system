<?php

namespace App\Filament\Widgets;

use App\Models\MeterReading;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyFinanceChart extends ChartWidget
{
    protected static ?string $heading = 'مقارنة شهرية: القراءات المعتمدة مقابل الدفعات';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $labels = [];
        $readings = [];
        $payments = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end = $month->copy()->endOfMonth()->toDateString();

            $labels[] = $month->translatedFormat('M Y');
            $readings[] = (float) MeterReading::query()
                ->where('is_approved', true)
                ->whereBetween('reading_date', [$start, $end])
                ->sum('amount');
            $payments[] = (float) Payment::query()
                ->whereBetween('payment_date', [$start, $end])
                ->sum('amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'القراءات المعتمدة',
                    'data' => $readings,
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.2)',
                ],
                [
                    'label' => 'الدفعات',
                    'data' => $payments,
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.2)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

