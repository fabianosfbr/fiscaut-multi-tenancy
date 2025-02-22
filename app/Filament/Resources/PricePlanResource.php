<?php

namespace App\Filament\Resources;

use App\Enums\Tenant\PricePlanTypEnum;
use App\Filament\Resources\PricePlanResource\Pages;
use App\Models\PricePlan;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricePlanResource extends Resource
{
    protected static ?string $model = PricePlan::class;

    protected static ?string $modelLabel = 'Plano';

    protected static ?string $pluralLabel = 'Planos';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('title')
                            ->label('Nome'),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->required(),
                        ToggleButtons::make('type')
                            ->label('Tipo')
                            ->inline()
                            ->options(PricePlanTypEnum::class)
                            ->required()
                            ->default('monthly'),
                        Toggle::make('status')
                            ->default(1),
                        TextInput::make('documents_permission_feature')
                            ->label('Limite de documentos')
                            ->numeric()
                            ->required(),
                        TextInput::make('users_permission_feature')
                            ->label('Limite de usuários')
                            ->numeric()
                            ->required(),
                        TextInput::make('storage_permission_feature')
                            ->label('Limite de espaço em disco')
                            ->numeric()
                            ->required(),
                        TextInput::make('price')
                            ->label('Valor')
                            ->prefix('R$')
                            ->required()
                            ->numeric(),
                        Toggle::make('has_trial')
                            ->label('Período de teste'),
                        TextInput::make('trial_days')
                            ->label('Quantidade de dias teste')
                            ->numeric(),
                        TextInput::make('package_badge')
                            ->required()
                            ->label('Badge do pacote'),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nome'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL'),
                IconColumn::make('status')
                    ->label('Status')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPricePlans::route('/'),
            'create' => Pages\CreatePricePlan::route('/create'),
            'edit' => Pages\EditPricePlan::route('/{record}/edit'),
        ];
    }
}
