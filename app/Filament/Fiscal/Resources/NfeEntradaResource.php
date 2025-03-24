<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\Tenant\OrigemNfeEnum;
use App\Models\Tenant\Organization;
use Filament\Tables\Actions\Action;
use App\Tables\Columns\TagColumnNfe;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Enums\Tenant\StatusManifestoNfeEnum;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;
use App\Filament\Fiscal\Resources\NfeEntradaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadPdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ClassificarNotaAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ManifestarDocumentoAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\RemoveClassificacaoAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoTableAction;

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
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $organization = getOrganizationCached();
                return $query->where('cnpj_destinatario', $organization->cnpj);
            })
            ->recordUrl(null)
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nome_emitente')
                    ->label('Empresa')
                    ->limit(30)
                    ->searchable()
                    ->size('sm')
                    ->description(function (NotaFiscalEletronica $record) {
                        return $record->cnpj_emitente;
                    })
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
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
                    ->getStateUsing(function (NotaFiscalEletronica $record): bool {
                        return $record->isEscrituradaParaOrganization(getOrganizationCached());
                    }),

                TextColumn::make('cfops')
                    ->label('CFOPs')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('itens', function ($query) use ($search) {
                            $query->where('cfop', 'like', "%{$search}%");
                        });
                    })
                    ->toggleable(),

                TextColumn::make('data_emissao')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('data_entrada')
                    ->label('Entrada')
                    ->sortable()
                    ->toggleable()
                    ->date('d/m/Y'),

                TextColumn::make('status_nota')
                    ->label('Status')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                TagColumnNfe::make('tagged')
                    ->label('Etiqueta')
                    ->alignCenter()
                    ->toggleable()
                    ->showTagCode(function () {
                        return ConfiguracaoGeral::getValue('isNfeMostrarEtiquetaComNomeAbreviado', Auth::user()->last_organization_id) ?? false;
                    }),

                TextColumn::make('status_manifestacao')
                    ->label('Manifestação')
                    ->badge()

                    ->sortable(),

                ViewChaveColumn::make('chave_acesso')
                    ->label('Chave')
                    ->searchable()
                    ->alignCenter(),

            ])
            ->defaultSort('data_emissao', 'desc')
            ->filters([
                Tables\Filters\QueryBuilder::make()
                    ->constraintPickerColumns(3)
                    ->constraintPickerWidth('2xl')
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_total')
                            ->label('Valor Total'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_base_icms')
                            ->label('Valor Base ICMS'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_icms')
                            ->label('Valor ICMS'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_pis')
                            ->label('Valor PIS'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_cofins')
                            ->label('Valor COFINS'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_ipi')
                            ->label('Valor IPI'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_seguro')
                            ->label('Valor Seguro'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_frete')
                            ->label('Valor Frete'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_icms_st')
                            ->label('Valor ST'),

                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('valor_desconto')
                            ->label('Valor Desconto'),

                    ]),

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

                Tables\Filters\Filter::make('data_entrada')
                    ->label('Data de Entrada')
                    ->columnSpan(2)
                    ->form([
                        Forms\Components\DatePicker::make('data_entrada_inicio')
                            ->label('Data Entrada Início')
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('data_entrada_fim')
                            ->label('Data Entrada Final')
                            ->columnSpan(1),
                    ])->columns(2)
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['data_entrada_inicio']) && empty($data['data_entrada_fim'])) {
                            return null;
                        }

                        $inicio = $data['data_entrada_inicio'] ? date('d/m/Y', strtotime($data['data_entrada_inicio'])) : '...';
                        $fim = $data['data_entrada_fim'] ? date('d/m/Y', strtotime($data['data_entrada_fim'])) : '...';

                        return "Entrada: {$inicio} até {$fim}";
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['data_entrada_inicio'])) {
                            $query->whereDate('data_entrada', '>=', $data['data_entrada_inicio']);
                        }
                        if (!empty($data['data_entrada_fim'])) {
                            $query->whereDate('data_entrada', '<=', $data['data_entrada_fim']);
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

                Tables\Filters\Filter::make('cfop')
                    ->label('CFOP')
                    ->columnSpan(1)
                    ->form([
                        Forms\Components\TagsInput::make('cfops')
                            ->label('CFOPs')
                            ->placeholder('Digite os CFOPs')
                            ->separator(',')
                            ->splitKeys(['Enter', ','])
                            ->rules(['regex:/^[0-9]+$/'])
                            ->helperText('Digite os CFOPs que deseja filtrar'),
                    ])
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['cfops'])) {
                            return null;
                        }

                        return 'CFOPs: ' . implode(', ', $data['cfops']);
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['cfops'])) {
                            $query->whereHas('itens', function ($query) use ($data) {
                                $query->whereIn('cfop', $data['cfops']);
                            });
                        }
                        return $query;
                    }),



                Tables\Filters\SelectFilter::make('status_manifestacao')
                    ->label('Status de Manifestação')
                    ->options(collect(StatusManifestoNfeEnum::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->getLabel()];
                    }))
                    ->multiple()
                    ->preload(),

                Tables\Filters\SelectFilter::make('etiquetas')
                    ->label('Filtrar por Etiquetas')
                    ->options([
                        'sem_etiqueta' => 'Sem Etiqueta',
                        'com_etiqueta' => 'Com Etiqueta',
                        'uma_etiqueta' => 'Apenas Uma Etiqueta',
                        'multiplas_etiquetas' => 'Múltiplas Etiquetas',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'sem_etiqueta' => $query->doesntHave('tagged'),
                            'com_etiqueta' => $query->has('tagged'),
                            'uma_etiqueta' => $query->has('tagged', '=', 1),
                            'multiplas_etiquetas' => $query->has('tagged', '>', 1),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('etiquetas_especificas')
                    ->label('Etiquetas Específicas')
                    ->columnSpanFull()
                    ->form([
                        Forms\Components\CheckboxList::make('etiquetas')
                            ->label('Selecione as Etiquetas')
                            ->options(function () {
                                return tagsForFilterNfe();
                            })
                            ->columns(4)
                            ->searchable()
                            ->helperText('Selecione as etiquetas que deseja filtrar')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['etiquetas'])) {
                            $tagIds = collect($data['etiquetas'])
                                ->map(function ($value) {
                                    return explode(' - ', $value)[0];
                                })
                                ->toArray();


                            $query->whereHas('tagged', function ($query) use ($tagIds) {
                                $query->whereIn('tag_id', $tagIds);
                            });
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['etiquetas'])) {
                            return null;
                        }

                        $etiquetas = Tag::whereIn('id', $data['etiquetas'])
                            ->get()
                            ->keyBy('id')
                            ->map(fn($tag) => $tag->code . ' - ' . $tag->name)
                            ->toArray();


                        return 'Etiquetas: ' . implode(', ', $etiquetas);
                    }),

            ])
            ->filtersFormColumns(5)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Detalhes'),
                    ClassificarNotaAction::make(),
                    RemoveClassificacaoAction::make(),
                    ManifestarDocumentoAction::make(),
                    DownloadXmlAction::make(),
                    DownloadPdfAction::make(),
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
            'index' => Pages\ListNfeEntradas::route('/'),
            'create' => Pages\CreateNfeEntrada::route('/create'),
            'edit' => Pages\EditNfeEntrada::route('/{record}/edit'),
            'view' => Pages\ViewNfeEntrada::route('/{record}'),
        ];
    }
}
