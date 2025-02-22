<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RenderHookUrlResource\Pages;
use App\Filament\Resources\RenderHookUrlResource\RelationManagers\RenderHookUrlRelationManager;
use App\Models\RenderHook;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RenderHookUrlResource extends Resource
{
    protected static ?string $model = RenderHook::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('hook_name')
                    ->required()
                    ->maxLength(255),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hook_name'),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RenderHookUrlRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRenderHookUrls::route('/'),
            'create' => Pages\CreateRenderHookUrl::route('/create'),
            'edit' => Pages\EditRenderHookUrl::route('/{record}/edit'),
        ];
    }
}
