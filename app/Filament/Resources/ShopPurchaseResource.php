<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopPurchaseResource\Pages;
use App\Models\ShopPurchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShopPurchaseResource extends Resource
{
    protected static ?string $model = ShopPurchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'المشتريات';

    protected static ?string $modelLabel = 'مشترى';

    protected static ?string $pluralModelLabel = 'المشتريات';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('purchase_date')
                    ->label('تاريخ الشراء')
                    ->default(now())
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('الوصف')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('المبلغ')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->suffix('₪'),
                Forms\Components\Select::make('method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('supplier')
                    ->label('المورد')
                    ->maxLength(255),
                Forms\Components\Textarea::make('note')
                    ->label('ملاحظة')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('purchase_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'cash' ? 'كاش' : 'تحويل بنكي'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('أضافه')
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
            'index' => Pages\ListShopPurchases::route('/'),
            'create' => Pages\CreateShopPurchase::route('/create'),
            'edit' => Pages\EditShopPurchase::route('/{record}/edit'),
        ];
    }
}
