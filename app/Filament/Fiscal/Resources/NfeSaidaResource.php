<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\NfeSaidaResource\Pages;
use App\Filament\Fiscal\Resources\NfeSaidaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadPdfAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadPdfBulkAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlBulkAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoTableAction;

class NfeSaidaResource extends Resource
{
    protected static ?string $model = NotaFiscalEletronica::class;

    protected static ?string $modelLabel = 'NFe Saída';

    protected static ?string $pluralLabel = 'NFes Saída';

    protected static ?string $navigationLabel = 'NFe Saída';

    protected static ?string $slug = 'nfes-saida';

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
            ->modifyQueryUsing(function (Builder $query) {
                $organization = getOrganizationCached();
                return $query->where('cnpj_emitente', $organization->cnpj);
            })
            ->recordUrl(null)
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nome_destinatario')
                    ->label('Empresa')
                    ->limit(30)
                    ->searchable()
                    ->size('sm')
                    ->description(function (NotaFiscalEletronica $record) {
                        return $record->cnpj_destinatario;
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



                TextColumn::make('status_nota')
                    ->label('Status')
                    ->badge()
                    ->toggleable()
                    ->sortable(),

                ViewChaveColumn::make('chave_acesso')
                    ->label('Chave')
                    ->searchable()
                    ->alignCenter(),

                IconColumn::make('possui_referencias')
                    ->label('Ref')
                    ->boolean()
                    ->alignCenter()
                    ->getStateUsing(fn(NotaFiscalEletronica $record): bool => $record->possuiReferencias())
                    ->tooltip('Possui Referências a Outras Notas')
                    ->url(function (NotaFiscalEletronica $record): ?string {
                        if (!$record->possuiReferencias()) {
                            return null;
                        }

                        $primeiraReferencia = $record->referenciasFeitas()
                            ->where('documento_origem_type', NotaFiscalEletronica::class)
                            ->first();

                        return $primeiraReferencia
                            ? route('filament.fiscal.resources.nfes-entrada.view', ['record' => $primeiraReferencia->documento_origem_id])
                            : null;
                    })
                    ->openUrlInNewTab(),

                IconColumn::make('eh_referenciada')
                    ->label('Ref. por')
                    ->boolean()
                    ->alignCenter()
                    ->url(function (NotaFiscalEletronica $record): ?string {
                        if (!$record->ehReferenciado()) {
                            return null;
                        }

                        $primeiraReferencia = $record->referenciasRecebidas()
                            ->where('documento_origem_type', NotaFiscalEletronica::class)
                            ->first();

                        return $primeiraReferencia
                            ? route('filament.fiscal.resources.nfes-entrada.view', ['record' => $primeiraReferencia->documento_origem_id])
                            : null;
                    })
                    ->getStateUsing(fn(NotaFiscalEletronica $record): bool => $record->ehReferenciado())
                    ->tooltip('Referenciada por Outras Notas')
                    ->openUrlInNewTab(),
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

                Tables\Filters\SelectFilter::make('referencias')
                    ->label('Referências')
                    ->options([
                        'possui_referencias' => 'Possui Referências',
                        'eh_referenciada' => 'É Referenciada',
                        'sem_referencias' => 'Sem Referências',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'possui_referencias' => $query->whereHas('referenciasFeitas'),
                            'eh_referenciada' => $query->whereHas('referenciasRecebidas'),
                            'sem_referencias' => $query->whereDoesntHave('referenciasFeitas')
                                ->whereDoesntHave('referenciasRecebidas'),
                            default => $query,
                        };
                    }),
            ])
            ->persistFiltersInSession()
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Detalhes'),
                    DownloadXmlAction::make(),
                    DownloadPdfAction::make(),
                    ToggleEscrituracaoTableAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DownloadXmlBulkAction::make(),
                    DownloadPdfBulkAction::make(),
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
            'index' => Pages\ListNfeSaidas::route('/'),
            'create' => Pages\CreateNfeSaida::route('/create'),
            'view' => Pages\ViewNfeSaida::route('/{record}'),
            'edit' => Pages\EditNfeSaida::route('/{record}/edit'),
        ];
    }
}
