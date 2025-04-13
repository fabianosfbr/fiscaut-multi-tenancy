<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use App\Models\Tenant\HistoricoContabil;
use Filament\Forms\Components\TagsInput;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\SelectPlanoDeConta;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Tenant\ParametrosConciliacaoBancaria;
use App\Filament\Contabil\Resources\ParametroResource\Pages;
use App\Filament\Contabil\Resources\ParametroResource\RelationManagers;

class ParametroResource extends Resource
{
    protected static ?string $model = ParametrosConciliacaoBancaria::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Parâmetros';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        TagsInput::make('params')
                            ->label('Parâmetros')
                            ->placeholder('Insira o termo de busca')
                            ->required()
                            ->columnSpan(2),

                        ToggleButtons::make('is_inclusivo')
                            ->label('Forma que será aplicado o filtro')
                            ->required()
                            ->default(0)
                            ->options([
                                '0' => 'OU',
                                '1' => 'E',
                            ])
                            ->inline()
                            ->columnSpan(1),


                    ]),

                SelectPlanoDeConta::make('conta_contabil')
                    ->label('Conta contabil')
                    ->required()
                    ->apiEndpoint(route('fiscal.remote-select.search'))
                    ->columnSpan(1),


                Grid::make(1)
                    ->schema([
                        Select::make('codigo_historico')
                            ->label('Cód. Histórico')
                            ->required()
                            ->options(function () {
                                $values = HistoricoContabil::where('organization_id', getOrganizationCached()->id)
                                    ->orderBy('codigo', 'asc')
                                    ->get()
                                    ->map(function ($item) {
                                        $item->codigo_descricao = $item->codigo . ' | ' . $item->descricao;
                                        return $item;
                                    })

                                    ->pluck('codigo_descricao', 'codigo');
                                return $values;
                            })
                            ->columnSpan(1),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('params')
                    ->label('Parâmetros')
                    ->badge()
                    ->color('gray')
                    ->searchable(query: fn(Builder $query, string $search): Builder => $query->SearchByParametro(search: $search)),

                TextColumn::make('plano_de_conta')
                    ->label('Conta Contábil')
                    ->limit(30)
                    ->formatStateUsing(function ($state) {
                        return $state->codigo . ' | ' . $state->nome;
                    })
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state?->codigo . ' | ' . $state?->nome) <= $column->getListLimit()) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state?->codigo . ' | ' . $state?->nome;
                    })
                    ->color('gray')
                    ->badge(),
                TextColumn::make('codigo_historico')
                    ->label('Cód. Histórico')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageParametros::route('/'),
        ];
    }
}
