<?php

namespace App\Filament\Client\Resources\CategoryTagDefaultResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->numeric()
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_enable')
                            ->label('Ativo')
                            ->default(true)
                            ->required()
                            ->columnSpan(1),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modelLabel('Etiqueta')
            ->pluralModelLabel('Etiquetas')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->sortable(),
                IconColumn::make('is_enable')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
