<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\Tenant\OrigemNfeEnum;
use App\Models\Tenant\Organization;
use Filament\Tables\Actions\Action;
use App\Tables\Columns\TagColumnNfe;
use Illuminate\Support\Facades\Auth;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Pages;
use App\Filament\Fiscal\Resources\NfeEntradaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ClassificarNotaAction;
use App\Filament\Fiscal\Resources\NfeEntradaResource\Actions\ToggleEscrituracaoAction;

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
                    ->label('Escriturada'),

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
                    ->sortable(),

                TextColumn::make('data_entrada')
                    ->label('Entrada')
                    ->sortable()
                    ->toggleable()
                    ->date('d/m/Y'),

                TextColumn::make('status_nota')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TagColumnNfe::make('tagged')
                    ->label('Etiqueta')
                    ->alignCenter()
                    ->toggleable()
                    ->showTagCode(function () {
                        return ConfiguracaoGeral::getValue('isNfeMostrarEtiquetaComNomeAbreviado', Auth::user()->last_organization_id);
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
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Detalhes'),
                    ClassificarNotaAction::make()
                        ->label('Classificar'),
                    DownloadXmlAction::make()
                        ->label('Download XML'),
                    ToggleEscrituracaoAction::make(),
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
