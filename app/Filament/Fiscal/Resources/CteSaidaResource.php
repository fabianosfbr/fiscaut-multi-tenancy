<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\CteSaida;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use App\Filament\Fiscal\Resources\CteSaidaResource\Pages;
use App\Filament\Fiscal\Resources\CteSaidaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\CteEntradaResource\Actions\DownloadCtePdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoTableAction;

class CteSaidaResource extends Resource
{
    protected static ?string $model = ConhecimentoTransporteEletronico::class;

    protected static ?string $modelLabel = 'CTe Saída';

    protected static ?string $pluralLabel = 'CTes Saída';

    protected static ?string $navigationLabel = 'CTe Saída';

    protected static ?string $slug = 'ctes-saida';

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
            ->query(function () {
                return ConhecimentoTransporteEletronico::query()->emitidos(getOrganizationCached());
            })
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

                ViewChaveColumn::make('chave_acesso')
                    ->label('Chave Acesso')
                    ->tooltip('Chave Acesso do CT-e')
                    ->alignCenter()
                    ->toggleable(),
            ])
            ->defaultSort('data_emissao', 'desc')
            ->filters([
                Tables\Filters\Filter::make('data_emissao')
                    ->label('Data de Emissão')
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\DatePicker::make('data_emissao_inicio')
                            ->label('Data Emissão Início')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('data_emissao_fim')
                            ->label('Data Emissão Final')
                            ->columnSpan(1),
                    ])->columns(2)
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['data_emissao_inicio']) && empty($data['data_emissao_fim'])) {
                            return null;
                        }

                        $inicio = $data['data_emissao_inicio'] ? date('d/m/Y', strtotime($data['data_emissao_inicio'])) : '...';
                        $fim = $data['data_emissao_fim'] ? date('d/m/Y', strtotime($data['data_emissao_fim'])) : '...';

                        return "Emissão: {$inicio} até {$fim}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['data_emissao_inicio'])) {
                            $query->whereDate('data_emissao', '>=', $data['data_emissao_inicio']);
                        }
                        if (!empty($data['data_emissao_fim'])) {
                            $query->whereDate('data_emissao', '<=', $data['data_emissao_fim']);
                        }
                        return $query;
                    }),


                Tables\Filters\TernaryFilter::make('escriturada_destinatario')
                    ->label('Escriturada')
                    ->columnSpan(1)
                    ->placeholder('Todos')
                    ->trueLabel('Sim')
                    ->falseLabel('Não')
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === null) {
                            return $query;
                        }

                        $organization = getOrganizationCached();

                        return $data['value']
                            ? $query->whereHas('organizacoesEscrituradas', function ($query) use ($organization) {
                                $query->where('organization_id', $organization->id);
                            })
                            : $query->whereDoesntHave('organizacoesEscrituradas', function ($query) use ($organization) {
                                $query->where('organization_id', $organization->id);
                            });
                    }),
            ])
            ->persistFiltersInSession()
            ->filtersFormColumns(5)
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
            'index' => Pages\ListCteSaidas::route('/'),
            //  'create' => Pages\CreateCteSaida::route('/create'),
            'view' => Pages\ViewCteSaida::route('/{record}'),
            // 'edit' => Pages\EditCteSaida::route('/{record}/edit'),
        ];
    }
}
