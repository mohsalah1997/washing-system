<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamNoteResource\Pages;
use App\Models\TeamNote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TeamNoteResource extends Resource
{
    protected static ?string $model = TeamNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'الملاحظات';

    protected static ?string $modelLabel = 'ملاحظة';

    protected static ?string $pluralModelLabel = 'الملاحظات';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('العنوان')
                    ->maxLength(255),
                Forms\Components\Textarea::make('body')
                    ->label('الملاحظة')
                    ->required()
                    ->columnSpanFull()
                    ->rows(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('body')
                    ->label('الملاحظة')
                    ->limit(80)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('أضافها')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamNotes::route('/'),
            'create' => Pages\CreateTeamNote::route('/create'),
            'edit' => Pages\EditTeamNote::route('/{record}/edit'),
        ];
    }
}
