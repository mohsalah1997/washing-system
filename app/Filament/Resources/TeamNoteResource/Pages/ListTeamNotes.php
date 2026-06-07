<?php

namespace App\Filament\Resources\TeamNoteResource\Pages;

use App\Filament\Resources\TeamNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeamNotes extends ListRecords
{
    protected static string $resource = TeamNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة ملاحظة'),
        ];
    }
}
