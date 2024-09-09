<?php

namespace App\Filament\Client\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\CategoriaEtiquetaPadrao;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\CategoryTagResource;
use App\Filament\Client\Resources\CategoryTagDefaultResource\Pages;
use App\Filament\Client\Resources\CategoryTagDefaultResource\RelationManagers;
use App\Filament\Client\Resources\CategoryTagDefaultResource\RelationManagers\TagsRelationManager;

class CategoryTagDefaultResource extends Resource
{
    protected static ?string $model = CategoriaEtiquetaPadrao::class;

    protected static ?string $pluralLabel = 'Etiquetas Padrão';

    protected static ?string $modelLabel = 'Etiqueta Padrão';

    protected static ?string $navigationGroup = 'Configurações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema(CategoryTagResource::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(CategoryTagResource::getColumnTableSchema())
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
            TagsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoryTagDefaults::route('/'),
            'create' => Pages\CreateCategoryTagDefault::route('/create'),
            'edit' => Pages\EditCategoryTagDefault::route('/{record}/edit'),
        ];
    }
}
