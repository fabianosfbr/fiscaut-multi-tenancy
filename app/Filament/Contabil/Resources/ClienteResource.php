<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Tenant\Cliente;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\ClienteResource\Pages;
use App\Filament\Contabil\Resources\ClienteResource\RelationManagers;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Section::make('Dados do fornecedor')
                    ->schema([
                        TextInput::make('nome')
                            ->required(),
                        TextInput::make('cnpj')
                            ->label('CNPJ'),
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->recordUrl(null)
            ->columns([
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
