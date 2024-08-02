<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\CategoryTagResource\Pages;
use App\Filament\Client\Resources\CategoryTagResource\RelationManagers;
use App\Models\Tenant\CategoryTag;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryTagResource extends Resource
{
    protected static ?string $model = CategoryTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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
            'index' => Pages\ListCategoryTags::route('/'),
            'create' => Pages\CreateCategoryTag::route('/create'),
            'edit' => Pages\EditCategoryTag::route('/{record}/edit'),
        ];
    }
}
