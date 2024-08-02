<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Tenant;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TenantResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\TenantResource\RelationManagers;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('razao_social')
                            ->label('Razão Social')
                            ->columnSpan(1),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label('Nome do responsável')
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label('Email do responsável')
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->label('Senha de acesso')
                            ->password()
                            ->revealable()
                            ->visibleOn('create')
                            ->columnSpan(1),
                        TextInput::make('domain')
                            ->label('Domínio')
                            ->columnSpan(1)
                            ->prefix('https://')
                            ->suffix('.localhost'),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Razão Social'),
                TextColumn::make('domains.domain')
                    ->label('Domínio')
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Responsável'),
                TextColumn::make('cnpj')
                    ->label('CNPJ'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
