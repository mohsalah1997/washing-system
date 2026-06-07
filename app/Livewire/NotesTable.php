<?php

namespace App\Livewire;

use App\Models\TeamNote;
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

class NotesTable extends Component implements HasForms, HasTable
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
                $query = TeamNote::query()->with('user')->latest();

                if ($this->displayPeriod !== 'all') {
                    $query->whereDate('created_at', today());
                }

                return $query;
            })
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة ملاحظة')
                    ->model(TeamNote::class)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('body')
                            ->label('الملاحظة')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, ['user_id' => auth()->id()]))
                    ->successNotificationTitle('تمت إضافة الملاحظة بنجاح'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('العنوان')
                            ->maxLength(255),
                        \Filament\Forms\Components\Textarea::make('body')
                            ->label('الملاحظة')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->successNotificationTitle('تم تحديث الملاحظة'),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('تم حذف الملاحظة'),
            ])
            ->emptyStateHeading('لا توجد ملاحظات اليوم')
            ->emptyStateDescription('لا توجد ملاحظات أُضيفت اليوم. غيّر فترة العرض إلى «الكل» أو أضف ملاحظة جديدة.');
    }

    public function render(): View
    {
        return view('livewire.notes-table');
    }
}
