<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MeterReadingsRelationManager extends RelationManager
{
    protected static string $relationship = 'meterReadings';

    protected static ?string $title = 'عمليات الغسيل';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('reading_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reading_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reading_value')
                    ->label('وزن الغسيل')
                    ->suffix(' كغ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_unit')
                    ->label('سعر الكيلو')
                    ->suffix(' ₪')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('تكلفة الغسل')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('is_approved')
                    ->label('الحالة')
                    ->getStateUsing(fn ($record) => $record->is_approved ? 'معتمدة' : 'غير معتمدة')
                    ->badge()
                    ->color(fn ($record) => $record->is_approved ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('note')
                    ->label('ملاحظات')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
