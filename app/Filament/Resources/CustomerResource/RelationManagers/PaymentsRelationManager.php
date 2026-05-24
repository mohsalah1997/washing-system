<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'الدفعات';

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
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('تاريخ الدفعة')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('طريقة الدفع')
                    ->formatStateUsing(fn ($state) => $state === 'bank_transfer' ? 'تحويل بنكي' : 'كاش'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('مرجع')
                    ->toggleable(),
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

