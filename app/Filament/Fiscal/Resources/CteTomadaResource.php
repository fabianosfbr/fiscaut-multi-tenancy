<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\CteTomada;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Tables\Columns\TagColumnCte;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use App\Filament\Fiscal\Resources\CteTomadaResource\Pages;
use App\Filament\Fiscal\Resources\CteTomadaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\CteEntradaResource\Actions\DownloadCtePdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ManifestarDocumentoAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoTableAction;

class CteTomadaResource extends Resource
{
    protected static ?string $model = ConhecimentoTransporteEletronico::class;

    protected static ?string $modelLabel = 'CTe Tomador';

    protected static ?string $pluralLabel = 'CTes Tomador';

    protected static ?string $navigationLabel = 'CTe Tomador';

    protected static ?string $slug = 'ctes-tomador';

    protected static ?string $navigationGroup = 'CTe';

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
                $query->with('tagged')
                    ->where('conhecimentos_transportes_eletronico.cnpj_tomador', getOrganizationCached()->cnpj)
                    ->orderBy('conhecimentos_transportes_eletronico.data_emissao', 'DESC');
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

                TextColumn::make('nome_destinatario')
                    ->label('Destinatário')
                    ->limit(30)
                    ->searchable()
                    ->size('sm')
                    ->description(function (ConhecimentoTransporteEletronico $record) {
                        return $record->cnpj_destinatario;
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

                TagColumnCte::make('tagged')
                    ->label('Etiqueta')
                    ->alignCenter()
                    ->toggleable()
                    ->showTagCode(function () {
                        $organizationId = getOrganizationCached()->id;
                        return config_organizacao($organizationId, 'geral', null, null, 'mostrar_codigo_etiqueta', false);
                    }),

                ViewChaveColumn::make('chave_acesso')
                    ->label('Chave Acesso')
                    ->tooltip('Chave Acesso do CT-e')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Detalhes'),
                    ManifestarDocumentoAction::make(),
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
            'index' => Pages\ListCteTomadas::route('/'),
            'create' => Pages\CreateCteTomada::route('/create'),
            'view' => Pages\ViewCteTomada::route('/{record}'),
            'edit' => Pages\EditCteTomada::route('/{record}/edit'),
        ];
    }
}
