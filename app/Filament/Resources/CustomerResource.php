<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers\MeterReadingsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\PaymentsRelationManager;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'الزبائن';

    protected static ?string $navigationGroup = 'إدارة الزبائن والغسيل';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'زبون';

    protected static ?string $pluralModelLabel = 'الزبائن';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الزبون')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الزبون')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الجوال')
                            ->required()
                            ->tel()
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Forms\Components\Section::make('الرصيد الافتتاحي')
                    ->description('حدد الرصيد الافتتاحي للزبون عند إضافته للنظام. موجب = له رصيد عندنا، سالب = عليه مبلغ.')
                    ->schema([
                        Forms\Components\Select::make('balance_type')
                            ->label('نوع الرصيد')
                            ->options([
                                'none' => 'لا يوجد رصيد افتتاحي',
                                'credit' => 'له رصيد عندنا (دفع مسبقًا)',
                                'debit' => 'عليه مبلغ (مديون)',
                            ])
                            ->default('none')
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                                if (! $record) {
                                    return;
                                }
                                $balance = (float) $record->initial_balance;
                                if ($balance > 0) {
                                    $component->state('credit');
                                } elseif ($balance < 0) {
                                    $component->state('debit');
                                } else {
                                    $component->state('none');
                                }
                            }),

                        Forms\Components\TextInput::make('initial_balance_amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->visible(fn (Forms\Get $get) => in_array($get('balance_type'), ['credit', 'debit']))
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $record) {
                                if (! $record) {
                                    return;
                                }
                                $component->state(abs((float) $record->initial_balance));
                            })
                            ->suffixIcon('heroicon-o-currency-dollar'),

                        Forms\Components\Hidden::make('initial_balance')
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('معلومات الزبون')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('رقم الزبون'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('اسم الزبون'),
                        Infolists\Components\TextEntry::make('phone')
                            ->label('رقم الجوال'),
                        Infolists\Components\TextEntry::make('last_reading')
                            ->label('آخر وزن')
                            ->state(function (Customer $record): string {
                                $lastReading = $record->meterReadings()
                                    ->orderByDesc('reading_date')
                                    ->orderByDesc('id')
                                    ->first();

                                if (! $lastReading) {
                                    return '—';
                                }

                                return number_format((float) $lastReading->reading_value, 3);
                            }),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime('Y-m-d H:i'),
                    ])->columns(3),

                Infolists\Components\Section::make('الملخص المالي')
                    ->schema([
                        Infolists\Components\TextEntry::make('initial_balance')
                            ->label('الرصيد الافتتاحي')
                            ->state(function (Customer $record): string {
                                $value = (float) $record->initial_balance;

                                if ($value > 0) {
                                    return 'له: ' . number_format($value, 2) . ' ₪';
                                }
                                if ($value < 0) {
                                    return 'عليه: ' . number_format(abs($value), 2) . ' ₪';
                                }

                                return '—';
                            }),
                        Infolists\Components\TextEntry::make('approved_readings_total')
                            ->label('إجمالي تكلفة الغسيل المعتمدة')
                            ->state(fn (Customer $record): string => number_format((float) $record->meterReadings()->where('is_approved', true)->sum('amount'), 2) . ' ₪'),
                        Infolists\Components\TextEntry::make('payments_total')
                            ->label('إجمالي الدفعات')
                            ->state(fn (Customer $record): string => number_format((float) $record->payments()->sum('amount'), 2) . ' ₪'),
                        Infolists\Components\TextEntry::make('balance')
                            ->label('الرصيد الحالي')
                            ->state(function (Customer $record): string {
                                $value = (float) $record->balance;

                                if ($value > 0) {
                                    return 'له: ' . number_format($value, 2) . ' ₪';
                                }
                                if ($value < 0) {
                                    return 'عليه: ' . number_format(abs($value), 2) . ' ₪';
                                }

                                return 'صفر';
                            }),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الجوال')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_reading')
                    ->label('آخر وزن')
                    ->getStateUsing(function (Customer $record) {
                        $lastReading = $record->meterReadings()
                            ->orderByDesc('reading_date')
                            ->orderByDesc('id')
                            ->first();

                        if (! $lastReading) {
                            return '—';
                        }

                        return number_format((float) $lastReading->reading_value, 3);
                    })
                    ->sortable()
                    ->tooltip('آخر وزن مسجل في عملية غسيل سابقة.'),
                Tables\Columns\TextColumn::make('initial_balance')
                    ->label('الرصيد الافتتاحي')
                    ->formatStateUsing(function ($state) {
                        $val = (float) $state;
                        if ($val > 0) {
                            return 'له: ' . number_format($val, 2) . ' ₪';
                        } elseif ($val < 0) {
                            return 'عليه: ' . number_format(abs($val), 2) . ' ₪';
                        }
                        return '—';
                    })
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : ((float) $state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('balance')
                    ->label('الرصيد الحالي')
                    ->getStateUsing(fn (Customer $record) => $record->balance)
                    ->formatStateUsing(function ($state) {
                        $val = (float) $state;
                        if ($val > 0) {
                            return 'له: ' . number_format($val, 2) . ' ₪';
                        } elseif ($val < 0) {
                            return 'عليه: ' . number_format(abs($val), 2) . ' ₪';
                        }
                        return 'صفر';
                    })
                    ->badge()
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : ((float) $state < 0 ? 'danger' : 'gray')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('balance_status')
                    ->label('حالة الرصيد')
                    ->options([
                        'has_credit' => 'له رصيد (موجب)',
                        'has_debit' => 'عليه مبلغ (سالب)',
                        'zero' => 'صفر',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        $customersTable = $query->getModel()->getTable();
                        $paymentsTable = (new \App\Models\Payment())->getTable();
                        $meterReadingsTable = (new \App\Models\MeterReading())->getTable();

                        return $query->whereRaw("(
                            COALESCE({$customersTable}.initial_balance, 0) +
                            COALESCE((SELECT SUM(amount) FROM {$paymentsTable} WHERE {$paymentsTable}.customer_id = {$customersTable}.id), 0) -
                            COALESCE((SELECT SUM(amount) FROM {$meterReadingsTable} WHERE {$meterReadingsTable}.customer_id = {$customersTable}.id AND {$meterReadingsTable}.is_approved = 1), 0)
                        ) " . match($data['value']) {
                            'has_credit' => '> 0',
                            'has_debit' => '< 0',
                            'zero' => '= 0',
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('تفاصيل'),
                Tables\Actions\EditAction::make(),
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
            MeterReadingsRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
