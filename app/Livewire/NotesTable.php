<?php

namespace App\Livewire;

use App\Models\TeamNote;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class NotesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(TeamNote::query()->with('user')->latest())
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
            ->emptyStateHeading('لا توجد ملاحظات')
            ->emptyStateDescription('أضف ملاحظة عامة يطلع عليها كل الفريق.');
    }

    public function render(): View
    {
        return view('livewire.notes-table');
    }
}
