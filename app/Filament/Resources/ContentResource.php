<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContentResource\Pages;
use App\Models\Content;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'محتوى';

    protected static ?string $pluralModelLabel = 'المحتوى';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('title.en')->label('العنوان (إنجليزي)')->required(),
            TextInput::make('title.ar')->label('العنوان')->required(),

            Textarea::make('body.en')->label('المحتوى (إنجليزي)')->rows(6),
            Textarea::make('body.ar')->label('المحتوى')->rows(6),

            TextInput::make('slug')
                ->label('الرابط المختصر')
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('category_id')
                ->label('التصنيف')
                ->relationship('category', 'name')
                ->required(),

            Select::make('subcategory_id')
                ->label('التصنيف الفرعي')
                ->relationship('subcategory', 'name')
                ->searchable()
                ->nullable(),

            TagsInput::make('tags')
                ->label('الوسوم'),

            Toggle::make('is_published')
                ->label('منشور'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }
}
