<?php

namespace App\Filament\Contabil\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Tenant\HistoricoContabil;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Contabil\Resources\HistoricoContabilResource\Pages;
use App\Filament\Contabil\Resources\HistoricoContabilResource\RelationManagers;

class HistoricoContabilResource extends Resource
{
    protected static ?string $model = HistoricoContabil::class;

    protected static ?string $modelLabel = 'Histórico Contábil';

    protected static ?string $pluralModelLabel = 'Históricos Contábeis';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?int $navigationSort = 5;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->unique(modifyRuleUsing: function (Unique $rule, callable $get) {
                                return $rule
                                    ->where('organization_id', getOrganizationCached()->id);
                            }, ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Código já cadastrado'
                            ])
                            ->required(),
                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->required(),
                    ])->columns(2)

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('organization_id', getOrganizationCached()->id);
            })
            ->searchDebounce('750ms')
            ->columns([

                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListHistoricoContabils::route('/'),
            'create' => Pages\CreateHistoricoContabil::route('/create'),
            'edit' => Pages\EditHistoricoContabil::route('/{record}/edit'),
        ];
    }
}
