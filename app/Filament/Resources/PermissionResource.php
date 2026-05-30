<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'الصلاحيات';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'صلاحية';

    protected static ?string $pluralModelLabel = 'الصلاحيات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الصلاحية')
                    ->description('عرض تفاصيل الصلاحية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الصلاحية')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('اسم الصلاحية')
                            ->disabled(),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('الحارس')
                            ->default('web')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('الأدوار')
                    ->description('الأدوار التي تستخدم هذه الصلاحية')
                    ->schema([
                        Forms\Components\CheckboxList::make('roles')
                            ->label('الأدوار')
                            ->relationship('roles', 'name')
                            ->options(Role::orderBy('name')->pluck('name', 'id'))
                            ->columns(2)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الصلاحية')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('الحارس')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('الأدوار')
                    ->counts('roles')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListPermissions::route('/'),
        ];
    }
}
