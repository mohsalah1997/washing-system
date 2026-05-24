<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerReadingsTemplateExport implements FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        return Customer::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'weight' => null,
            ]);
    }

    public function headings(): array
    {
        return [
            'id',
            'اسم الزبون',
            'وزن الغسيل',
        ];
    }

    public function title(): string
    {
        return 'عمليات الغسيل';
    }
}
