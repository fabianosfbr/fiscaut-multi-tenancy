<?php

namespace App\Filament\Contabil\Resources\LayoutResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Layout;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\HistoricoContabil;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class LayoutRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'layoutRules';



    protected static ?string $title = 'Regras de Exportação';
    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Radio::make('rule_type')
                    ->label('Tipo da Regra')
                    ->columns(3)
                    ->live()
                    ->default('data_da_operacao')
                    ->options([
                        'data_da_operacao' => 'Data da Operação',
                        'operacao_de_debito' => 'Operação de Débito',
                        'operacao_de_credito' => 'Operação de Crédito',
                        'valor_da_operacao' => 'Valor da Operação',
                    ])
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('position')
                    ->label('Posição')
                    ->required()
                    ->numeric(),

                Forms\Components\TextInput::make('name')
                    ->label('Nome da Regra')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('data_source_type')
                    ->label('Tipo de Fonte de Dados')
                    ->options([
                        'column' => 'Coluna',
                        'constant' => 'Constante',
                        'query' => 'Consulta',
                        'parametros_gerais' => 'Parâmetros Gerais',
                    ])
                    ->required()
                    ->reactive() // Atualiza os campos dependentes quando o valor muda
                    ->visible(function (Get $get) {
                        return $get('rule_type') !== 'historico_contabil';
                    })
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state !== 'column' && $state !== 'query' && $state !== 'parametros_gerais') {
                            $set('data_source', null);
                        }
                        if ($state !== 'constant') {
                            $set('data_source_constant', null);
                        }
                        if ($state !== 'parametros_gerais') {
                            $set('data_source_parametros_gerais_target_columns', null);
                        }
                    }),

                Forms\Components\Select::make('data_source')
                    ->label('Coluna de Layout')
                    ->options(function (Get $get, RelationManager $livewire): array {
                        if ($get('data_source_type') === 'column') {
                            // Obtém as colunas cadastradas no Layout (LayoutColumn)
                            $layoutColumns = $livewire->getOwnerRecord()->layoutColumns;

                            // Formata as opções para o Select
                            return $layoutColumns->pluck('target_column_name', 'target_column_name')->toArray();
                        }

                        return [];
                    })
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'column')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'column')
                    ->searchable(),

                Forms\Components\TextInput::make('data_source_constant')
                    ->label('Valor Constante')
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'constant')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'constant'),

                Forms\Components\Select::make('data_source_table')
                    ->label('Tabela')
                    ->options([
                        'contabil_plano_de_contas' => 'Plano de Contas',
                        'contabil_bancos' => 'Bancos',
                        'contabil_clientes' => 'Clientes',
                        'contabil_fornecedores' => 'Fornecedores',
                    ])
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->live()
                    ->afterStateUpdated(function (Set $set) {
                        $set('data_source_attribute', null);
                    }),

                Forms\Components\Select::make('data_source_attribute')
                    ->label('Atributo')
                    ->options(function (Get $get): array {
                        $table = $get('data_source_table');
                        if ($table === 'contabil_plano_de_contas') {
                            return [
                                'nome' => 'Nome',
                                'codigo' => 'Conta Contábil',
                                'classificacao' => 'Classificação',
                            ];
                        } elseif ($table === 'contabil_bancos') {
                            return [
                                'nome' => 'Nome',
                            ];
                        } elseif ($table === 'contabil_clientes') {
                            return [
                                'cnpj' => 'CNPJ',
                                'nome' => 'Razão Social',
                            ];
                        } elseif ($table === 'contabil_fornecedores') {
                            return [
                                'cnpj' => 'CNPJ',
                                'nome' => 'Razão Social',
                            ];
                        }
                        return [];
                    })
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->reactive(),

                Forms\Components\Select::make('data_source_condition')
                    ->label('Condição')
                    ->options([
                        '=' => 'Igual a',
                        'like' => 'Contém',
                    ])
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query'),

                Forms\Components\Select::make('data_source_value_type')
                    ->label('Tipo de Valor da Pesquisa')
                    ->options([
                        'constant' => 'Valor Constante',
                        'column' => 'Coluna do Excel',
                    ])
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state !== 'column') {
                            $set('data_source_search_value', null);
                        }
                        if ($state !== 'constant') {
                            $set('data_source_search_constant', null);
                        }
                    }),

                Forms\Components\Select::make('data_source_search_value')
                    ->label('Coluna do Excel para Pesquisa')
                    ->options(function (Get $get, RelationManager $livewire): array {
                        if ($get('data_source_type') === 'query' && $get('data_source_value_type') === 'column') {
                            // Obtém as colunas cadastradas no Layout (LayoutColumn)
                            $layoutColumns = $livewire->getOwnerRecord()->layoutColumns;

                            // Formata as opções para o Select
                            return $layoutColumns->pluck('target_column_name', 'target_column_name')->toArray();
                        }

                        return [];
                    })
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_value_type') === 'column')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_value_type') === 'column')
                    ->searchable(),

                Forms\Components\TextInput::make('data_source_search_constant')
                    ->label('Valor Constante para Pesquisa')
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_value_type') === 'constant')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_value_type') === 'constant'),

                // Forms\Components\Select::make('data_source_parametros_gerais_target_columns')
                //     ->label('Colunas do Excel para Processar (Parâmetros Gerais)')
                //     ->multiple()
                //     ->options(function (Get $get, RelationManager $livewire): array {
                //         if ($get('data_source_type') === 'parametros_gerais') {
                //             // Obtém as colunas cadastradas no Layout (LayoutColumn)
                //             $layoutColumns = $livewire->getOwnerRecord()->layoutColumns;

                //             // Formata as opções para o Select
                //             return $layoutColumns->pluck('target_column_name', 'target_column_name')->toArray();
                //         }

                //         return [];
                //     })
                //     ->required(fn(Get $get): bool => $get('data_source_type') === 'parametros_gerais')
                //     ->visible(fn(Get $get): bool => $get('data_source_type') === 'parametros_gerais'),


                Forms\Components\Select::make('data_source_historico')
                    ->label('Cód. Histórico')
                    ->required()
                    ->options(function () {

                        $values = HistoricoContabil::where('issuer_id', getCurrentIssuer())
                            ->orderBy('codigo', 'asc')
                            ->get()
                            ->map(function ($item) {
                                $item->codigo_descricao = $item->codigo . ' | ' . $item->descricao;
                                return $item;
                            })

                            ->pluck('codigo_descricao', 'codigo');

                        return $values;
                    })
                    ->required(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_table') === 'contabil_clientes' || $get('data_source_table') === 'contabil_fornecedores')
                    ->visible(fn(Get $get): bool => $get('data_source_type') === 'query' && $get('data_source_table') === 'contabil_clientes' || $get('data_source_table') === 'contabil_fornecedores'),

                Forms\Components\Select::make('condition_type')
                    ->label('Tipo de Condição')
                    ->required()
                    ->options([
                        'none' => 'Nenhuma',
                        'if' => 'Se',
                    ])
                    ->default('none')
                    ->live()
                    ->visible(function (Get $get) {
                        return $get('rule_type') !== 'historico_contabil';
                    })
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state === 'none') {
                            $set('condition_data_source', null);
                            $set('switchCases', []);
                        }
                    }),

                Forms\Components\Select::make('condition_data_source_type')
                    ->label('Tipo de Fonte de Dados da Condição')
                    ->options([
                        'column' => 'Coluna',
                        'constant' => 'Constante',
                    ])
                    ->required(fn(Get $get): bool => $get('condition_type') === 'if')
                    ->visible(fn(Get $get): bool => $get('condition_type') === 'if')
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        if ($state !== 'column' && $state !== 'query') {
                            $set('condition_data_source', null);
                        }
                        if ($state !== 'constant') {
                            $set('condition_data_source_constant', null);
                        }
                    }),

                Forms\Components\Select::make('condition_data_source')
                    ->label('Coluna de Layout (Condição)')
                    ->options(function (Get $get, RelationManager $livewire): array {
                        if ($get('condition_data_source_type') === 'column') {
                            // Obtém as colunas cadastradas no Layout (LayoutColumn)
                            $layoutColumns = $livewire->getOwnerRecord()->layoutColumns;

                            // Formata as opções para o Select
                            return $layoutColumns->pluck('target_column_name', 'target_column_name')->toArray();
                        }

                        return [];
                    })
                    ->required(fn(Get $get): bool => $get('condition_type') === 'if' && $get('condition_data_source_type') === 'column')
                    ->visible(fn(Get $get): bool => $get('condition_type') === 'if' && $get('condition_data_source_type') === 'column')
                    ->searchable(),

                Forms\Components\TextInput::make('condition_data_source_constant')
                    ->label('Valor Constante (Condição)')
                    ->required(fn(Get $get): bool => $get('condition_type') === 'if' && $get('condition_data_source_type') === 'constant')
                    ->visible(fn(Get $get): bool => $get('condition_type') === 'if' && $get('condition_data_source_type') === 'constant'),

                Forms\Components\Select::make('condition_operator')
                    ->label('Operador da Condição')
                    ->live()
                    ->options([
                        '=' => 'Igual a',
                        '!=' => 'Diferente de',
                        '>' => 'Maior que',
                        '<' => 'Menor que',
                        '>=' => 'Maior ou igual a',
                        '<=' => 'Menor ou igual a',
                        'contains' => 'Contém',
                        'not_contains' => 'Não Contém',
                        'empty' => 'Vazio',
                        'not_empty' => 'Não Vazio'
                    ])
                    ->required(fn(Get $get): bool => $get('condition_type') === 'if')
                    ->visible(fn(Get $get): bool => $get('condition_type') === 'if'),

                Forms\Components\TextInput::make('condition_value')
                    ->label('Valor da Condição')
                    ->required(fn(Get $get): bool => $get('condition_type') === 'if' && !in_array($get('condition_operator'), ['empty', 'not_empty']))
                    ->visible(fn(Get $get): bool => $get('condition_type') === 'if' && !in_array($get('condition_operator'), ['empty', 'not_empty'])),

                Forms\Components\TextInput::make('default_value')
                    ->label('Valor Padrão')
                    ->visible(function (Get $get) {
                        return $get('rule_type') !== 'historico_contabil';
                    })
                    ->nullable(),

                Forms\Components\Select::make('data_source_historical_columns')
                    ->label('Colunas do Excel para Processar Parâmetros')
                    ->multiple()
                    ->options(function (Get $get, RelationManager $livewire): array {
                        $layoutColumns = $livewire->getOwnerRecord()->layoutColumns;

                        // Formata as opções para o Select
                        return $layoutColumns->pluck('target_column_name', 'target_column_name')->toArray();
                    })
                    ->visible(function (Get $get) {
                        return $get('rule_type') === 'historico_contabil';
                    }),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Regras de Exportação')
            ->description('Regras utilizadas para exportar os dados')
            ->modelLabel('Regra')
            ->pluralModelLabel('Regras')
            ->emptyStateHeading('Nenhuma regra cadastrada')
            ->emptyStateDescription('Quando cadastrar uma regra ela aparecerá aqui')
            ->reorderable('position')
            ->defaultSort('position')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('Posição')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Tipo de Regra')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('data_source_type')
                    ->label('Tipo de Fonte de Dados'),
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
            ->bulkActions([]);
    }
}
