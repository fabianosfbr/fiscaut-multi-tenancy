<?php

namespace App\Filament\Client\Resources\CategoryTagResource\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class TagsRelationManager extends RelationManager
{

    protected static string $relationship = 'tags';
    protected static ?string $title = 'Etiquetas';

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
                Tables\Actions\CreateAction::make()
                    ->modalWidth('md')
                    ->after(function () {
                        $this->clearCache();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('md')
                    ->after(function () {
                        $this->clearCache();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function () {
                        $this->clearCache();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    protected function clearCache()
    {
        $organizationId = getTenant()->id;
        Cache::forget('categoryWithDifal-' . $organizationId);
        Cache::forget('categoryWithTagForSearching-' . $organizationId);

        $this->redirect(request()->header('Referer'));
    }
}
