<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Tenant\Acumulador;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Fiscal\Resources\AcumuladorResource\Pages;

class AcumuladorResource extends Resource
{
    protected static ?string $model = Acumulador::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Acumulador';

    protected static ?string $pluralLabel = 'Acumuladores';

    protected static ?string $slug = 'acumuladores';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados do Acumulador')
                    ->schema([
                        TextInput::make('codi_acu')
                            ->label('Código Acumulador')
                            ->required()
                            ->unique(
                                table: Acumulador::class,
                                column: 'codi_acu',
                                ignoreRecord: true,
                                modifyRuleUsing: fn($rule) =>
                                $rule->where('organization_id', getOrganizationCached()->id)
                            )
                            ->validationMessages([
                                'unique' => 'O código acumulador já está em uso.',
                            ]),
                        TextInput::make('nome_acu')
                            ->label('Nome')
                            ->required(),

                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('organization_id', Auth::user()->last_organization_id);
            })
            ->columns([
                TextColumn::make('codi_acu')
                    ->label('Código Acumulador')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nome_acu')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([])
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
            'index' => Pages\ListAcumuladors::route('/'),
            'create' => Pages\CreateAcumulador::route('/create'),
            'edit' => Pages\EditAcumulador::route('/{record}/edit'),
        ];
    }
}
