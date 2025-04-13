<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Banco;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\PlanoDeConta;
use Filament\Resources\Resource;
use App\Models\HistoricoContabil;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use App\Enums\TipoParametroContabil;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use App\Models\LayoutArquivoConcilicacao;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant\ImportarLancamentoContabil;
use Filament\Tables\Filters\TernaryFilter;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\Collection;
use App\Models\ParametrosConciliacaoBancaria;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\Pages;
use App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\RelationManagers;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;

class ImportarLancamentoContabilResource extends Resource
{
    protected static ?string $model = ImportarLancamentoContabil::class;

    protected static ?string $modelLabel = 'Importar Lanç. Contábil';

    protected static ?string $pluralModelLabel = 'Importar Lanç. Contábeis';

    protected static ?string $slug = 'importar-lancamento-contabil';


    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

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
            ->recordUrl(null)
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('user_id', auth()->user()->id)
                    ->where('valor', '!=', 0)
                    ->where('organization_id', getOrganizationCached()->id);
            })
            ->columns([
                TextColumn::make('data')
                    ->label('Data')
                    ->date('d/m/Y'),

                TextColumn::make('debito')
                    ->label('Débito')
                    ->searchable()
                    ->badge()
                    ->tooltip(function (Model $record) {
                        if (!is_null($record->metadata) && isset($record->metadata['descricao_debito'])) {
                            return $record->metadata['descricao_debito'];
                        }

                        return null;
                    })
                    ->color('success')
                    ->copyable(),

                TextColumn::make('credito')
                    ->label('Crédito')
                    ->searchable()
                    ->badge()
                    ->tooltip(function (Model $record) {
                        if (!is_null($record->metadata) && isset($record->metadata['descricao_credito'])) {

                            return $record->metadata['descricao_credito'];
                        }

                        return null;
                    })
                    ->color('danger')
                    ->copyable(),


                TextColumn::make('valor')
                    ->label('Valor')
                    ->formatStateUsing(function ($state) {
                        if ($state == 0) {
                            return '';
                        }

                        return 'R$ ' . number_format($state, 2, ',', '.');
                    }),


                TextColumn::make('historico')
                    ->label('Histórico Contábil')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('metadata.texto_historico_contabil')
                    ->label('Histórico Texto')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_exist')
                    ->label('Vínculo')
                    ->boolean()
            ])
            ->filters([
                TernaryFilter::make('is_exist')
                    ->label('Possui Vínculo')
            ])
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label(label: 'Filtros'),
            )
            ->actions([
                
            ])
            ->bulkActions([
          
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
            'index' => Pages\ListImportarLancamentoContabeis::route('/'),
            'create' => Pages\CreateImportarLancamentoContabil::route('/create'),
            'edit' => Pages\EditImportarLancamentoContabil::route('/{record}/edit'),
        ];
    }
}
