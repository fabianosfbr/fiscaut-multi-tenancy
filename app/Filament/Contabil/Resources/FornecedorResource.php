<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Tenant\Fornecedor;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\FornecedorResource\Pages;
use App\Filament\Contabil\Resources\FornecedorResource\RelationManagers;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedor::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $navigationLabel = 'Fornecedores';

    protected static ?string $pluralLabel = 'Fornecedores';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

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
            ->recordUrl(null)
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
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
            'index' => Pages\ListFornecedors::route('/'),
            'create' => Pages\CreateFornecedor::route('/create'),
            'edit' => Pages\EditFornecedor::route('/{record}/edit'),
        ];
    }
}
