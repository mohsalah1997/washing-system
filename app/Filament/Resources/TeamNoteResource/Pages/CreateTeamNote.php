<?php

namespace App\Filament\Resources\TeamNoteResource\Pages;

use App\Filament\Resources\TeamNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamNote extends CreateRecord
{
    protected static string $resource = TeamNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تمت إضافة الملاحظة بنجاح';
    }
}
