<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;
use App\Filament\Fiscal\Resources\NfeEntradaResource\RelationManagers;

class NfeEntradaResource extends Resource
{
    protected static ?string $model = NotaFiscalEletronica::class;

    protected static ?string $modelLabel = 'NFe Entrada';

    protected static ?string $pluralLabel = 'NFes Entrada';

    protected static ?string $navigationLabel = 'NFe Entrada';

    protected static ?string $slug = 'nfes-entrada';

    protected static ?string $navigationGroup = 'NFe';

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
            ->recordUrl(null)
            ->extremePaginationLinks()
            ->striped()
            ->searchDebounce('950ms')
            ->columns([
                TextColumn::make('nNF')
                    ->label('Nº')
                    ->searchable()
                    ->sortable(),
                ViewColumn::make('carta_correcao')
                    ->label('')
                    ->view('tables.columns.carta-correcao'),
                TextColumn::make('emitente_razao_social')
                    ->label('Empresa')
                    ->limit(30)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('emitente_razao_social', 'like', "%{$search}%")
                            ->orWhere('emitente_cnpj', $search);
                    })
                    ->size('sm')
                    ->description(function (NotaFiscalEletronica $record) {
                        return $record->emitente_cnpj;
                    })
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    }),
                TextColumn::make('vNfe')
                    ->label('Valor')
                    ->iconPosition('after')
                    ->searchable()
                    ->money('BRL')
                    ->sortable()
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
            'index' => Pages\ListNfeEntradas::route('/'),
            'create' => Pages\CreateNfeEntrada::route('/create'),
            'edit' => Pages\EditNfeEntrada::route('/{record}/edit'),
        ];
    }
}
