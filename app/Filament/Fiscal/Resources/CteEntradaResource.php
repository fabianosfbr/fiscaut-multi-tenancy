<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Fiscal\Resources\CteEntradaResource\Pages;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;

class CteEntradaResource extends Resource
{
    protected static ?string $model = ConhecimentoTransporteEletronico::class;

    protected static ?string $modelLabel = 'CT-e Entrada';

    protected static ?string $pluralLabel = 'CT-es Entrada';

    protected static ?string $navigationLabel = 'CT-e Entrada';

    protected static ?string $slug = 'ctes-entrada';

    protected static ?string $navigationGroup = 'CT-e';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->query(function () {
            //     return ConhecimentoTransporteEletronico::query()->entradasDestinatario(getOrganizationCached());
            // })
            ->recordUrl(null)
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serie')
                    ->label('Série')
                    ->searchable(),

                TextColumn::make('nome_emitente')
                    ->label('Emitente')
                    ->limit(30)
                    ->searchable()
                    ->size('sm')
                    ->description(function (ConhecimentoTransporteEletronico $record) {
                        return $record->cnpj_emitente;
                    }),

                TextColumn::make('nome_destinatario')
                    ->label('Destinatário')
                    ->limit(30)
                    ->searchable()
                    ->size('sm')
                    ->description(function (ConhecimentoTransporteEletronico $record) {
                        return $record->cnpj_destinatario;
                    }),

                TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),

                IconColumn::make('escriturada_destinatario')
                    ->boolean()
                    ->alignCenter()
                    ->toggleable()
                    ->label('Escriturada')
                    ->getStateUsing(function (ConhecimentoTransporteEletronico $record): bool {
                        return $record->isEscrituradaParaOrganization(getOrganizationCached());
                    }),


                TextColumn::make('data_emissao')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('status_cte')
                    ->label('Status')
                    ->badge()
                    ->toggleable()
                    ->sortable(),
            ])
            ->defaultSort('data_emissao', 'desc')
            ->filters([
                
            ])
            ->persistFiltersInSession()
            ->filtersFormColumns(3)
            ->deferFilters()
            ->actions([
                Tables\Actions\ViewAction::make(),
                
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
            'index' => Pages\ListCteEntradas::route('/'),
            'create' => Pages\CreateCteEntrada::route('/create'),
            'edit' => Pages\EditCteEntrada::route('/{record}/edit'),
           // 'view' => Pages\ViewCteEntrada::route('/{record}'),
        ];
    }
}
