<?php

namespace App\Filament\Pages;

use Filament\Actions;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected static ?string $title = 'لوحة التحكم';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadDatabaseBackup')
                ->label('تحميل نسخة احتياطية')
                ->icon('heroicon-o-circle-stack')
                ->color('gray')
                ->url(route('admin.database-backup.download'))
                ->visible(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
        ];
    }
}
