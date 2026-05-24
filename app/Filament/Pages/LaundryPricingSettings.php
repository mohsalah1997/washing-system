<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class LaundryPricingSettings extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'أسعار الغسيل';

    protected static ?string $navigationGroup = 'إعدادات النظام';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.laundry-pricing-settings';

    protected static ?string $title = 'أسعار الغسيل';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        if (! Setting::where('key', 'price_per_unit')->exists()) {
            Setting::set('price_per_unit', '5');
        }

        if (! Setting::where('key', 'minimum_amount')->exists()) {
            Setting::set('minimum_amount', '15');
        }

        $this->form->fill([
            'price_per_unit' => (float) Setting::get('price_per_unit', 0),
            'minimum_amount' => (float) Setting::get('minimum_amount', 0),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('price_per_unit')
                    ->label('سعر الكيلو')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('₪ / كغ')
                    ->helperText('يُستخدم لحساب تكلفة الغسل = الوزن × سعر الكيلو'),
                Forms\Components\TextInput::make('minimum_amount')
                    ->label('الحد الأدنى للمبلغ')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('₪')
                    ->helperText('أقل مبلغ يُحسب للزبون عند وجود وزن غسيل'),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        Setting::set('price_per_unit', (string) ($state['price_per_unit'] ?? 0));
        Setting::set('minimum_amount', (string) ($state['minimum_amount'] ?? 0));

        Notification::make()
            ->title('تم حفظ أسعار الغسيل')
            ->success()
            ->send();
    }
}
