<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeterReadingResource\Pages;
use App\Models\Customer;
use App\Models\MeterReading;
use App\Models\Setting;
use App\Services\MeterReadingCalculator;
use App\Services\MeterReadingSmsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\HtmlString;

class MeterReadingResource extends Resource
{
    protected static ?string $model = MeterReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'عمليات الغسيل';

    protected static ?string $navigationGroup = 'إدارة الزبائن والغسيل';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'عملية غسيل';

    protected static ?string $pluralModelLabel = 'عمليات الغسيل';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بيانات عملية الغسيل')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('الزبون')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (?MeterReading $record) => $record?->hasSmsBeenSent() ?? false)
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('reading_value', null);
                                $set('amount', null);
                                $set('price_per_unit', (float) Setting::get('price_per_unit', 0));
                            }),
                    ])->columns(1),

                Forms\Components\Section::make('معلومات الزبون')
                    ->schema([
                        Forms\Components\Placeholder::make('customer_last_order')
                            ->label('آخر عملية غسيل')
                            ->content(function (Get $get, ?MeterReading $record) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return '—';
                                }
                                $customer = Customer::find($customerId);
                                if (! $customer) {
                                    return '—';
                                }
                                $last = app(MeterReadingCalculator::class)->getLastWashOrder($customer, $record?->id);
                                if (! $last) {
                                    return 'لا توجد عمليات سابقة';
                                }

                                return $last['weight'] . ' كغ (بتاريخ ' . $last['date'] . ' | تكلفة: ' . number_format($last['cost'], 2) . ' ₪ | سعر الكيلو: ' . $last['price_per_unit'] . ')';
                            }),

                        Forms\Components\Placeholder::make('customer_total_readings')
                            ->label('إجمالي تكلفة الغسيل المعتمدة')
                            ->content(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return '—';
                                }
                                $customer = Customer::find($customerId);
                                if (! $customer) {
                                    return '—';
                                }
                                $total = $customer->meterReadings()
                                    ->where('is_approved', true)
                                    ->sum('amount');
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
                            ->label('الرصيد')
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
                                    return new HtmlString('<span style="color: #16a34a; font-weight: bold;">له رصيد: ' . number_format($balance, 2) . ' ₪</span>');
                                } elseif ($balance < 0) {
                                    return new HtmlString('<span style="color: #dc2626; font-weight: bold;">عليه مبلغ: ' . number_format(abs($balance), 2) . ' ₪</span>');
                                }
                                return new HtmlString('<span style="color: #6b7280;">الرصيد صفر</span>');
                            }),
                    ])
                    ->columns(4)
                    ->visible(fn(Get $get) => filled($get('customer_id')))
                    ->collapsible(),

                Forms\Components\Section::make('تفاصيل العملية')
                    ->schema([
                        Forms\Components\DatePicker::make('reading_date')
                            ->label('تاريخ العملية')
                            ->default(now())
                            ->required(),

                        Forms\Components\TextInput::make('reading_value')
                            ->label('وزن الغسيل')
                            ->numeric()
                            ->required()
                            ->minValue(0.001)
                            ->suffix('كغ')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::recalculateWashCost($set, $get);
                            }),

                        Forms\Components\TextInput::make('price_per_unit')
                            ->label('سعر الكيلو')
                            ->numeric()
                            ->default(fn() => (float) Setting::get('price_per_unit', 0))
                            ->required()
                            ->live(onBlur: true)
                            ->helperText('يُعبّأ من الإعدادات ويمكن تعديله لهذه العملية.')
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                static::recalculateWashCost($set, $get);
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->label('تكلفة الغسل')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('₪'),

                        Forms\Components\Hidden::make('consumption')
                            ->dehydrated(),

                        Forms\Components\Hidden::make('net_amount')
                            ->dehydrated(),

                        Forms\Components\Textarea::make('note')
                            ->label('ملاحظات')
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function recalculateWashCost(Set $set, Get $get, string $prefix = ''): void
    {
        $weight = (float) $get($prefix . 'reading_value');
        if ($weight <= 0) {
            return;
        }

        $pricePerUnit = (float) $get($prefix . 'price_per_unit');
        if (! $pricePerUnit) {
            $pricePerUnit = (float) Setting::get('price_per_unit', 0);
            $set($prefix . 'price_per_unit', $pricePerUnit);
        }

        try {
            $calculated = app(MeterReadingCalculator::class)->calculateFromWeight($weight, $pricePerUnit);
            $set($prefix . 'amount', $calculated['amount']);
            $set($prefix . 'consumption', $calculated['consumption']);
            $set($prefix . 'net_amount', $calculated['net_amount']);
        } catch (\RuntimeException) {
            // weight validation handled elsewhere
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reading_date', 'desc')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sms_sent_at')
                    ->label('حالة SMS')
                    ->getStateUsing(fn (MeterReading $record) => $record->hasSmsBeenSent() ? 'أُرسل' : 'لم يُرسل')
                    ->description(fn (MeterReading $record) => $record->sms_sent_at?->format('Y-m-d H:i'))
                    ->badge()
                    ->color(fn (MeterReading $record) => $record->hasSmsBeenSent() ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('ready_sms')
                    ->label('حالة الجاهزية')
                    ->getStateUsing(fn (MeterReading $record) => $record->hasReadySmsBeenSent() ? 'أُبلغ' : '—')
                    ->badge()
                    ->color(fn (MeterReading $record) => $record->hasReadySmsBeenSent() ? 'success' : 'gray')
                    ->toggleable(),
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
                    ->sortable()
                    ->suffix(' ₪'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('تكلفة الغسل')
                    ->money('ILS')
                    ->sortable()
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->label('المجموع')
                            ->money('ILS'),
                    ),
                Tables\Columns\TextColumn::make('note')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإدخال')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('display_period')
                    ->label('فترة العرض')
                    ->options([
                        'today' => 'اليوم',
                        'all' => 'الكل',
                    ])
                    ->default('today')
                    ->selectablePlaceholder(false)
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? 'today') === 'all') {
                            return $query;
                        }

                        return $query->whereDate('reading_date', today());
                    }),
                Tables\Filters\SelectFilter::make('is_approved')
                    ->label('حالة الاعتماد')
                    ->options([
                        '1' => 'معتمدة',
                        '0' => 'غير معتمدة',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return $query->where('is_approved', (bool) $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('الزبون')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('reading_date_range')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('reading_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn(Builder $query, $date): Builder => $query->whereDate('reading_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (MeterReading $record) => ! $record->hasSmsBeenSent()),
                Tables\Actions\Action::make('sendSms')
                    ->label('إرسال SMS')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('إرسال رسالة للزبون')
                    ->modalDescription(function (MeterReading $record) {
                        $service = app(MeterReadingSmsService::class);

                        return $service->formatPreviewWithSegmentCost(
                            $service->buildMessage($record, 'initial')
                        );
                    })
                    ->visible(fn (MeterReading $record) => filled($record->customer?->phone) && ! $record->hasSmsBeenSent())
                    ->action(fn (MeterReading $record) => app(MeterReadingSmsService::class)->send($record, 'initial')),
                Tables\Actions\Action::make('sendReadySms')
                    ->label('غسيل جاهز')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('إبلاغ الزبون بجاهزية الغسيل')
                    ->modalDescription(function (MeterReading $record) {
                        $service = app(MeterReadingSmsService::class);

                        return $service->formatPreviewWithSegmentCost(
                            $service->buildReadyMessage($record)
                        );
                    })
                    ->visible(fn (MeterReading $record) => filled($record->customer?->phone))
                    ->action(fn (MeterReading $record) => app(MeterReadingSmsService::class)->sendReady($record)),
                Tables\Actions\Action::make('viewSmsLog')
                    ->label('السجل')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->modalHeading('سجل رسائل SMS')
                    ->modalContent(fn (MeterReading $record) => view('filament.modals.meter-reading-sms-log', [
                        'logs' => $record->smsLogs()->with('user')->latest()->get(),
                    ]))
                    ->visible(fn (MeterReading $record) => $record->smsLogs()->exists()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $withSms = $records->filter(fn (MeterReading $record) => $record->hasSmsBeenSent())->count();
                            if ($withSms > 0) {
                                throw ValidationException::withMessages([
                                    'table' => 'لا يمكن حذف عمليات أُرسل لها SMS. قم بإلغاء تحديدها أولاً.',
                                ]);
                            }
                        }),
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
            'index' => Pages\ListMeterReadings::route('/'),
            'create' => Pages\CreateMeterReading::route('/create'),
            'edit' => Pages\EditMeterReading::route('/{record}/edit'),
        ];
    }
}
