<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\AcumuladorResource\Pages;
use App\Models\Tenant\Acumulador;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AcumuladorResource extends Resource
{
    protected static ?string $model = Acumulador::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Acumulador';

    protected static ?string $pluralLabel = 'Acumuladores';

    protected static ?int $navigationSort = 2;

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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('organization_id', auth()->user()->last_organization_id);
            })
            ->columns([
                TextColumn::make('codi_acu')
                    ->label('Código Acumulador')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nome_acu')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([])
            ->bulkActions([]);
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
            'index' => Pages\ListAcumuladors::route('/'),
            // 'create' => Pages\CreateAcumulador::route('/create'),
            //  'edit' => Pages\EditAcumulador::route('/{record}/edit'),
        ];
    }
}
