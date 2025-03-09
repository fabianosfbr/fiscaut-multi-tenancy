<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\Tenant\OrigemNfeEnum;
use Filament\Tables\Actions\Action;
use App\Tables\Columns\TagColumnNfe;
use App\Tables\Columns\ViewChaveColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\NotaFiscalEletronica;
use App\Models\Tenant\NotaFiscalEletronicaItem;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Pages;
use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\RelationManagers;
use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Actions\DownloadXmlAction;
use App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Actions\ClassificarNotaAction;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Auth;

class NotaFiscalEletronicaResource extends Resource
{
    protected static ?string $model = NotaFiscalEletronica::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            'index' => Pages\ListNotaFiscalEletronicas::route('/'),
            'create' => Pages\CreateNotaFiscalEletronica::route('/create'),
            'view' => Pages\ViewNotaFiscalEletronica::route('/{record}'),
            'edit' => Pages\EditNotaFiscalEletronica::route('/{record}/edit'),
        ];
    }
}
