<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Customer;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'الدفعات';

    protected static ?string $navigationGroup = 'الدفعات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'الدفعات';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اختيار الزبون')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('الزبون')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ])->columns(1),

                // قسم معلومات الزبون - يظهر دائمًا عند اختيار زبون
                Forms\Components\Section::make('معلومات الزبون المالية')
                    ->schema([
                        Forms\Components\Placeholder::make('customer_total_readings')
                            ->label('إجمالي مبالغ القراءات')
                            ->content(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return '—';
                                }
                                $customer = Customer::find($customerId);
                                if (! $customer) {
                                    return '—';
                                }
                                $total = $customer->meterReadings()->sum('amount');
                                return number_format($total, 2) . ' ₪';
                            }),

                        Forms\Components\Placeholder::make('customer_total_payments')
                            ->label('إجمالي الدفعات')
                            ->content(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return '—';
                                }
                                $customer = Customer::find($customerId);
                                if (! $customer) {
                                    return '—';
                                }
                                $total = $customer->payments()->sum('amount');
                                return number_format($total, 2) . ' ₪';
                            }),

                        Forms\Components\Placeholder::make('customer_balance')
                            ->label('الرصيد الحالي')
                            ->content(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return '—';
                                }
                                $customer = Customer::find($customerId);
                                if (! $customer) {
                                    return '—';
                                }
                                $balance = $customer->balance;
                                if ($balance > 0) {
                                    return new HtmlString('<span style="color: #16a34a; font-weight: bold; font-size: 1.1em;">له رصيد: ' . number_format($balance, 2) . ' ₪</span>');
                                } elseif ($balance < 0) {
                                    return new HtmlString('<span style="color: #dc2626; font-weight: bold; font-size: 1.1em;">عليه مبلغ: ' . number_format(abs($balance), 2) . ' ₪</span>');
                                }
                                return new HtmlString('<span style="color: #6b7280; font-weight: bold;">الرصيد صفر - لا يوجد مستحقات</span>');
                            }),
                    ])
                    ->columns(3)
                    ->visible(fn (Get $get) => filled($get('customer_id')))
                    ->collapsible(),

                Forms\Components\Section::make('بيانات الدفعة')
                    ->schema([
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('تاريخ الدفعة')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->validationMessages([
                                'min' => 'يجب أن يكون المبلغ أكبر من صفر.',
                            ]),

                        Forms\Components\Select::make('method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'كاش',
                                'bank_transfer' => 'تحويل بنكي',
                            ])
                            ->required(),

                        Forms\Components\TextInput::make('reference')
                            ->label('رقم المرجع / التحويل')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('note')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('ILS')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'cash' ? 'كاش' : 'تحويل بنكي')
                    ->color(fn ($state) => $state === 'cash' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('reference')
                    ->label('المرجع')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإدخال')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('الزبون')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('method')
                    ->label('طريقة الدفع')
                    ->options([
                        'cash' => 'كاش',
                        'bank_transfer' => 'تحويل بنكي',
                    ]),
                Tables\Filters\Filter::make('payment_date_range')
                    ->label('فترة التاريخ')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
