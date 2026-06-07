<?php

namespace App\Livewire;

use App\Models\ShopPurchase;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class PurchasesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public string $displayPeriod = 'today';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('displayPeriod')
                    ->label('فترة العرض')
                    ->options([
                        'today' => 'اليوم',
                        'all' => 'الكل',
                    ])
                    ->live()
                    ->selectablePlaceholder(false),
            ]);
    }

    public function updatedDisplayPeriod(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $query = ShopPurchase::query()->with('user')->latest();

                if ($this->displayPeriod !== 'all') {
                    $query->whereDate('purchase_date', today());
                }

                return $query;
            })
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
                    ->formatStateUsing(fn ($state) => $state === 'cash' ? 'كاش' : 'تحويل بنكي')
                    ->color(fn ($state) => $state === 'cash' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('supplier')
                    ->label('المورد')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('note')
                    ->label('ملاحظة')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('أضافه')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة مشترى')
                    ->model(ShopPurchase::class)
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('purchase_date')
                            ->label('تاريخ الشراء')
                            ->default(now())
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffix('₪'),
                        \Filament\Forms\Components\Select::make('method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'كاش',
                                'bank_transfer' => 'تحويل بنكي',
                            ])
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('supplier')
                            ->label('المورد')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('ملاحظة')
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, ['user_id' => auth()->id()]))
                    ->successNotificationTitle('تم تسجيل المشترى بنجاح'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('purchase_date')
                            ->label('تاريخ الشراء')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('description')
                            ->label('الوصف')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->suffix('₪'),
                        \Filament\Forms\Components\Select::make('method')
                            ->label('طريقة الدفع')
                            ->options([
                                'cash' => 'كاش',
                                'bank_transfer' => 'تحويل بنكي',
                            ])
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('supplier')
                            ->label('المورد')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('note')
                            ->label('ملاحظة')
                            ->columnSpanFull(),
                    ])
                    ->successNotificationTitle('تم تحديث المشترى'),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('تم حذف المشترى'),
            ])
            ->emptyStateHeading('لا توجد مشتريات اليوم')
            ->emptyStateDescription('لا توجد مشتريات بتاريخ اليوم. غيّر فترة العرض إلى «الكل» أو أضف مشترى جديد.');
    }

    public function render(): View
    {
        return view('livewire.purchases-table');
    }
}
