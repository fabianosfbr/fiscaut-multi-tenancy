<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Tenant;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
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
                            ->validationAttribute('Razão Social')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('domains', Str::slug($state)))
                            ->columnSpan(1),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->required()
                            ->validationAttribute('CNPJ')
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label('Nome do responsável')
                            ->validationAttribute('Nome do responsável')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label('Email do responsável')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->label('Senha de acesso')
                            ->validationAttribute('Senha de acesso')
                            ->password()
                            ->revealable()
                            ->required()
                            ->visibleOn('create')
                            ->columnSpan(1),
                        TextInput::make('domains')
                            ->label('Domínio')
                            ->validationAttribute('Domínio')
                            ->disabledOn('edit')
                            ->required()
                            ->formatStateUsing(function (?Model $record) {
                                if (!$record) {
                                    return null;
                                }
                                $url = $record->domains->toArray()[0]['domain'];
                                return explode('.', $url)[0];
                            })
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
                TextColumn::make('payment_log.package_name')
                    ->label('Pacote Assinado'),
                    TextColumn::make('payment_log.status')
                    ->label('Status do pacote'),
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
