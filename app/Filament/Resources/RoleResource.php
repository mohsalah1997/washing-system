<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'الأدوار';

    protected static ?string $navigationGroup = 'إدارة الوصول';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'دور';

    protected static ?string $pluralModelLabel = 'الأدوار';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الدور')
                    ->description('تحديد اسم الدور')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الدور')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('أدخل اسم الدور (مثال: مشرف)')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->label('الحارس')
                            ->default('web')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('الصلاحيات')
                    ->description('اختر صلاحيات هذا الدور. تحديد مورد (الأب) يحدّد تلقائياً جميع إجراءاته (الأبناء).')
                    ->schema(function () {
                        $permissions = Permission::orderBy('name')->get();
                        $grouped = [];
                        $allOptions = [];

                        foreach ($permissions as $permission) {
                            $parsed = static::parsePermissionName($permission->name);

                            if ($parsed !== null) {
                                [$action, $resource] = $parsed;

                                if (! isset($grouped[$resource])) {
                                    $grouped[$resource] = [];
                                }
                                $grouped[$resource][] = $permission->id;
                                $allOptions[$permission->id] = static::formatPermissionLabel($action, $resource);
                            } else {
                                $allOptions[$permission->id] = $permission->name;
                            }
                        }

                        $schema = [];
                        foreach ($grouped as $resource => $permissionIds) {
                            $resourceLabel = static::translateResourceName($resource);

                            $schema[] = Forms\Components\Checkbox::make("select_all_{$resource}")
                                ->label("تحديد كل صلاحيات {$resourceLabel}")
                                ->live()
                                ->afterStateUpdated(function ($state, $set, $get) use ($permissionIds) {
                                    $currentPermissions = $get('permissions') ?? [];

                                    if ($state) {
                                        $newPermissions = array_unique(array_merge($currentPermissions, $permissionIds));
                                        $set('permissions', array_values($newPermissions));
                                    } else {
                                        $newPermissions = array_values(array_diff($currentPermissions, $permissionIds));
                                        $set('permissions', $newPermissions);
                                    }
                                })
                                ->dehydrated(false)
                                ->default(function ($get, $record) use ($permissionIds) {
                                    if ($record) {
                                        $selected = $record->permissions()->whereIn('id', $permissionIds)->pluck('id')->toArray();

                                        return count($selected) === count($permissionIds);
                                    }
                                    $currentPermissions = $get('permissions') ?? [];
                                    $selected = array_intersect($currentPermissions, $permissionIds);

                                    return count($selected) === count($permissionIds);
                                });
                        }

                        $schema[] = Forms\Components\CheckboxList::make('permissions')
                            ->label('الصلاحيات')
                            ->relationship('permissions', 'name')
                            ->options($allOptions)
                            ->columns(2)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) use ($grouped) {
                                foreach ($grouped as $resource => $permissionIds) {
                                    $currentPermissions = $state ?? [];
                                    $selected = array_intersect($currentPermissions, $permissionIds);

                                    if (count($selected) === count($permissionIds)) {
                                        $set("select_all_{$resource}", true);
                                    } else {
                                        $set("select_all_{$resource}", false);
                                    }
                                }
                            })
                            ->helperText('اختر صلاحيات فردية أو استخدم مربعات "تحديد الكل" أعلاه لتحديد كل صلاحيات مورد معين.');

                        return $schema;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الدور')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('الحارس')
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('الصلاحيات')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('المستخدمون')
                    ->counts('users')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->action(function ($record) {
                        if ($record->users()->exists()) {
                            throw new \Exception('لا يمكن حذف دور مرتبط بمستخدمين. يرجى إزالة المستخدمين من هذا الدور أولاً.');
                        }
                        $record->delete();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('حذف الدور')
                    ->modalDescription('هل أنت متأكد؟ لا يمكن التراجع عن هذا الإجراء.')
                    ->modalSubmitActionLabel('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('حذف المحدد')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->users()->exists()) {
                                    throw new \Exception('لا يمكن حذف الدور: ' . $record->name . ' (مرتبط بمستخدمين)');
                                }
                                $record->delete();
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    protected static function parsePermissionName(string $permissionName): ?array
    {
        $actionLabels = static::actionLabels();
        $actions = array_keys($actionLabels);
        usort($actions, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($actions as $action) {
            $prefix = $action . '_';
            if (str_starts_with($permissionName, $prefix)) {
                return [$action, substr($permissionName, strlen($prefix))];
            }
        }

        return null;
    }

    protected static function formatPermissionLabel(string $action, string $resource): string
    {
        $actionLabel = static::actionLabels()[$action] ?? $action;

        return trim($actionLabel . ' ' . static::translateResourceName($resource));
    }

    protected static function translateResourceName(string $resource): string
    {
        $resourceLabels = [
            'customer' => 'الزبائن',
            'meter::reading' => 'عمليات الغسيل',
            'payment' => 'الدفعات',
            'setting' => 'الإعدادات',
            'user' => 'المستخدمون',
            'role' => 'الأدوار',
            'permission' => 'الصلاحيات',
            'category' => 'التصنيفات',
            'content' => 'المحتوى',
        ];

        return $resourceLabels[$resource] ?? str_replace(['::', '_'], [' ', ' '], $resource);
    }

    /**
     * @return array<string, string>
     */
    protected static function actionLabels(): array
    {
        return __('filament-shield::filament-shield.resource_permission_prefixes_labels');
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
