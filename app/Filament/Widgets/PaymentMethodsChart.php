<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع الدفعات حسب طريقة الدفع';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $cashTotal = (float) Payment::query()->where('method', 'cash')->sum('amount');
        $bankTransferTotal = (float) Payment::query()->where('method', 'bank_transfer')->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'إجمالي المبالغ',
                    'data' => [$cashTotal, $bankTransferTotal],
                    'backgroundColor' => ['#16a34a', '#2563eb'],
                ],
            ],
            'labels' => ['كاش', 'تحويل بنكي'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

