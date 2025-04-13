<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Tenant\PlanoDeConta;


use Dompdf\FrameDecorator\Text;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Imports\PlanoDeContaImport;

use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use App\Filament\Imports\ProductImporter;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Database\Eloquent\Builder;
use avadim\FastExcelLaravel\Facades\Excel;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\PlanoDeContaResource\Pages;
use App\Filament\Contabil\Resources\PlanoDeContaResource\RelationManagers;

class PlanoDeContaResource extends Resource
{
    protected static ?string $model = PlanoDeConta::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Plano de Contas';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Plano de Contas')
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código'),
                        TextInput::make('classificacao')
                            ->label('Classificação'),
                        TextInput::make('descricao')
                            ->label('Descrição'),
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'A' => 'Analítica',
                                'S' => 'Sintética',
                            ])
                            ->default('A'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(PlanoDeConta::query()->where('organization_id', getOrganizationCached()->id))
            ->recordUrl(null)
            ->defaultSort('classificacao', 'asc')
            ->recordClasses(function (Model $record) {
                if ($record->tipo == 'A') {
                    return 'bg-gray-100 dark:bg-gray-800';
                }
            })
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->alignEnd(),
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->alignEnd(),
                TextColumn::make('classificacao')
                    ->label('Classificação')
                    ->searchable()
                    ->alignEnd(),
                TextColumn::make('tipo')
                    ->badge(),
                IconColumn::make('is_ativo')
            ])

            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Tipo da conta')
                    ->placeholder('Todas')
                    ->trueLabel('Analítica')
                    ->falseLabel('Sintética')
                    ->queries(
                        true: fn(Builder $query) => $query->where('tipo', 'A'),
                        false: fn(Builder $query) => $query->where('tipo', 'S'),
                        blank: fn(Builder $query) => $query,
                    )
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPlanoDeContas::route('/'),
            'create' => Pages\CreatePlanoDeConta::route('/create'),
            'edit' => Pages\EditPlanoDeConta::route('/{record}/edit'),
        ];
    }
}
