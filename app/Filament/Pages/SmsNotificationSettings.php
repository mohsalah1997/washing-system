<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\SmsApprovalMessageBuilder;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SmsNotificationSettings extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'إعدادات رسائل SMS';

    protected static ?string $navigationGroup = 'إعدادات النظام';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.sms-notification-settings';

    protected static ?string $title = 'إعدادات رسائل SMS';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::check();
    }

    public function mount(): void
    {
        if (! Setting::where('key', 'sms_approval_message_template')->exists()) {
            Setting::set('sms_approval_message_template', SmsApprovalMessageBuilder::defaultTemplate());
        }

        if (! Setting::where('key', 'sms_enabled')->exists()) {
            Setting::set('sms_enabled', '0');
        }

        if (! Setting::where('key', 'sms_ready_message_template')->exists()) {
            Setting::set('sms_ready_message_template', SmsApprovalMessageBuilder::defaultReadyTemplate());
        }

        $this->form->fill([
            'sms_enabled' => Setting::get('sms_enabled', '0') === '1',
            'template' => Setting::get('sms_approval_message_template', SmsApprovalMessageBuilder::defaultTemplate()),
            'ready_template' => Setting::get('sms_ready_message_template', SmsApprovalMessageBuilder::defaultReadyTemplate()),
        ]);
    }

    public function form(Form $form): Form
    {
        $variablesList = collect(SmsApprovalMessageBuilder::availableVariables())
            ->map(fn (array $var) => "<code>{$var['placeholder']}</code> — {$var['label']}")
            ->implode('<br>');

        return $form
            ->schema([
                Forms\Components\Toggle::make('sms_enabled')
                    ->label('تفعيل إرسال SMS')
                    ->helperText('عند التفعيل سيتم إرسال رسائل التكلفة والجاهزية للزبون (يتطلب إعداد مزود SMS).'),
                Forms\Components\Textarea::make('template')
                    ->label('نص رسالة اعتماد السجل')
                    ->required()
                    ->rows(8)
                    ->helperText('استخدم المتغيرات أدناه داخل النص. مثال: وزن الغسيل: {reading_value}'),
                Forms\Components\Textarea::make('ready_template')
                    ->label('نص رسالة جاهزية الغسيل')
                    ->required()
                    ->rows(6)
                    ->helperText('رسالة إبلاغ الزبون بأن غسيله جاهز للاستلام من المغسلة.'),
                Forms\Components\Placeholder::make('variables')
                    ->label('المتغيرات المتاحة')
                    ->content(new HtmlString($variablesList)),
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

        Setting::set('sms_enabled', ($state['sms_enabled'] ?? false) ? '1' : '0');
        Setting::set('sms_approval_message_template', $state['template']);
        Setting::set('sms_ready_message_template', $state['ready_template']);

        Notification::make()
            ->title('تم حفظ إعدادات رسائل SMS')
            ->success()
            ->send();
    }
}
