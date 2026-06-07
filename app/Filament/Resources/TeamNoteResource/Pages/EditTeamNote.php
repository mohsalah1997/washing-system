<?php

namespace App\Filament\Resources\TeamNoteResource\Pages;

use App\Filament\Resources\TeamNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeamNote extends EditRecord
{
    protected static string $resource = TeamNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث الملاحظة';
    }
}
