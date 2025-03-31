<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use App\Filament\Fiscal\Resources\CteEntradaResource\Pages;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadPdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\CteEntradaResource\Actions\DownloadCtePdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoTableAction;

class CteEntradaResource extends Resource
{
    protected static ?string $model = ConhecimentoTransporteEletronico::class;

    protected static ?string $modelLabel = 'CTe Entrada';

    protected static ?string $pluralLabel = 'CTes Entrada';

    protected static ?string $navigationLabel = 'CTe Entrada';

    protected static ?string $slug = 'ctes-entrada';

    protected static ?string $navigationGroup = 'CTe';

 
    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                return ConhecimentoTransporteEletronico::query()->entradasDestinatario(getOrganizationCached());
            })
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

                TextColumn::make('chaves_nfe_referenciadas')
                    ->label('NF-e Referenciadas')
                    ->state(function (ConhecimentoTransporteEletronico $record) {

                        $referencias = $record->referenciasFeitas()
                            ->where('tipo_referencia', 'NFE')
                            ->get();

                        if ($referencias->isEmpty()) {
                            return 'Nenhuma NF-e referenciada';
                        }

                        return $referencias->pluck('chave_acesso_referenciada');
                    })
                    ->tooltip('Chaves das notas fiscais que referenciam este CT-e')
                    ->view('components.nfe-keys')
                    ->toggleable()
                    ->alignCenter(),

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

                ViewChaveColumn::make('chave_acesso')
                    ->label('Chave Acesso')
                    ->tooltip('Chave Acesso do CT-e')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->defaultSort('data_emissao', 'desc')
            ->filters([])
            ->persistFiltersInSession()
            ->filtersFormColumns(3)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Detalhes'),                   
                    DownloadXmlAction::make(),
                    DownloadCtePdfAction::make(),
                    ToggleEscrituracaoTableAction::make(),
                ]),

            ])
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
            'index' => Pages\ListCteEntradas::route('/'),
            //'create' => Pages\CreateCteEntrada::route('/create'),
           // 'edit' => Pages\EditCteEntrada::route('/{record}/edit'),
            'view' => Pages\ViewCteEntrada::route('/{record}'),
        ];
    }
}
