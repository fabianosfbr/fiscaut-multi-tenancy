<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Tenant\Banco;
use Filament\Resources\Resource;
use App\Models\Tenant\PlanoDeConta;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\BancoResource\Pages;
use App\Filament\Contabil\Resources\BancoResource\RelationManagers;

class BancoResource extends Resource
{
    protected static ?string $model = Banco::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações do Banco')
                    ->schema([
                        TextInput::make('nome')
                            ->required(),
                        TextInput::make('cnpj')
                            ->label('CNPJ'),
                        TextInput::make('agencia')
                            ->label('Agência')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => (string) $state),
                        TextInput::make('conta')
                            ->label('Nº da Conta')
                            ->required()
                            ->dehydrateStateUsing(fn(string $state): string => (string) $state),
                        SelectPlanoDeConta::make('conta_contabil')
                            ->label('Conta contabil')
                            ->apiEndpoint(route('fiscal.remote-select.search'))
                            ->required()
                            ->columnSpan(2),

                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where('organization_id', getOrganizationCached()->id);
            })
            ->recordUrl(null)
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('nome')
                    ->searchable()
                    ->label('Nome'),
                TextColumn::make('cnpj')
                    ->label('CNPJ'),
                TextColumn::make('plano_de_conta')
                    ->label('Conta Contábil')
                    ->formatStateUsing(function ($state) {
                        return $state->codigo . ' | ' . $state->nome;
                    })
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBancos::route('/'),
            'create' => Pages\CreateBanco::route('/create'),
            'edit' => Pages\EditBanco::route('/{record}/edit'),
        ];
    }
}
