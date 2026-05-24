<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'الأدوار';

    protected static ?string $navigationGroup = 'إدارة الوصول';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role Information')
                    ->description('Define role name')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter role name (e.g., moderator)')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->description('Select permissions for this role. Selecting a resource (parent) will automatically select all its actions (children).')
                    ->schema(function () {
                        $permissions = Permission::orderBy('name')->get();
                        $grouped = [];
                        $allOptions = [];

                        // Group permissions by resource
                        foreach ($permissions as $permission) {
                            $parts = explode('_', $permission->name, 2);
                            if (count($parts) === 2) {
                                $resource = $parts[1];
                                $action = $parts[0];

                                if (!isset($grouped[$resource])) {
                                    $grouped[$resource] = [];
                                }
                                $grouped[$resource][] = $permission->id;
                                $allOptions[$permission->id] = ucfirst($action) . ' ' . ucfirst($resource);
                            } else {
                                $allOptions[$permission->id] = $permission->name;
                            }
                        }

                        $schema = [];
                        foreach ($grouped as $resource => $permissionIds) {
                            $resourceLabel = ucfirst($resource);

                            $schema[] = Forms\Components\Checkbox::make("select_all_{$resource}")
                                ->label("Select All {$resourceLabel} Permissions")
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
                            ->relationship('permissions', 'name')
                            ->options($allOptions)
                            ->columns(2)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) use ($grouped) {
                                // Update parent checkboxes based on children selection
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
                            ->helperText('Select individual permissions or use "Select All" checkboxes above to select all permissions for a resource.');

                        return $schema;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('created_at')
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
                            throw new \Exception('Cannot delete role with assigned users. Please remove users from this role first.');
                        }
                        $record->delete();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Delete Role')
                    ->modalDescription('Are you sure? This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->users()->exists()) {
                                    throw new \Exception('Cannot delete role: ' . $record->name . ' (has users assigned)');
                                }
                                $record->delete();
                            }
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
