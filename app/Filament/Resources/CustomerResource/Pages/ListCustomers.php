<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Route;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة زبون'),
            Actions\Action::make('exportReadingsTemplate')
                ->label('تصدير قالب القراءات (Excel)')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('customers.readings-template.export'))
                ->openUrlInNewTab(false),
        ];
    }
}

