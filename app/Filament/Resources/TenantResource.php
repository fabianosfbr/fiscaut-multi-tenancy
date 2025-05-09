<?php

namespace App\Filament\Resources;

use Closure;
use Filament\Tables;
use App\Models\Domain;
use App\Models\Tenant;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\PricePlan;
use App\Models\PaymentLog;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use App\Enums\Tenant\PaymentLogStatusEnum;
use App\Filament\Resources\TenantResource\Pages;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresas';


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
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('domain', Str::slug($state)))
                            ->columnSpan(1),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->required()
                            ->validationAttribute('CNPJ')
                            ->live(onBlur: true)
                            ->unique(ignoreRecord: true)
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    $value = sanitize($value);
                                    $tenant = Tenant::where('cnpj', $value)->first();
                                    if ($tenant) {
                                        $fail('Este cnpj já está sendo utilizado por outra empresa.');
                                    }
                                },
                            ])
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->label('Nome do responsável')
                            ->validationAttribute('Nome do responsável')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('email')
                            ->label('Email do responsável')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),
                        TextInput::make('password')
                            ->label('Senha de acesso')
                            ->validationAttribute('Senha de acesso')
                            ->password()
                            ->revealable()
                            ->required()
                            ->visibleOn('create')
                            ->columnSpan(1),
                        TextInput::make('domain')
                            ->label('Domínio')
                            ->validationAttribute('Domínio')
                            ->disabledOn('edit')
                            ->required()
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    $domain = Tenant::where('domain', $value)->first();
                                    if ($domain) {
                                        $fail('Este domínio já está sendo utilizado por outra empresa.');
                                    }
                                },
                            ])
                            ->columnSpan(1)
                            ->prefix('https://')
                            ->suffix('.' . config('app.domain')),
                    ])->columns(2),

                // Section::make('Configurações')
                //     ->schema([
                //         TextInput::make('tenancy_db_name')
                //             ->label('Nome do banco de dados') 
                //             ->required()                          
                //             ->columnSpan(1),
                //         TextInput::make('tenancy_db_username')
                //             ->label('Usuário do banco de dados')  
                //             ->required()                          
                //             ->columnSpan(1),
                //         TextInput::make('tenancy_db_password')
                //             ->label('Senha do banco de dados')
                //             ->required()
                //             ->password()
                //             ->columnSpan(1),                        
                //     ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
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
                Action::make('assinar-pacote')
                    ->label('Assinar pacote')
                    ->icon('heroicon-o-credit-card')
                    ->form([
                        Select::make('package_id')
                            ->label('Pacote')
                            ->options(PricePlan::all()->pluck('title', 'id'))
                            ->required(),
                    ])
                    ->modalWidth('md')
                    ->action(function (Model $tenant, array $data) {

                        $package = PricePlan::find($data['package_id']);

                        $subscription = [
                            'package_id' => $package->id,
                            'package_name' => $package->title,
                            'package_price' => $package->price,
                            'status' => PaymentLogStatusEnum::PAID->value,
                            'name' => $tenant->name,
                            'email' => $tenant->email,
                            'tenant_id' => $tenant->id,
                            'start_date' => now(),
                            'expire_date' => '2100-12-31',
                            'track' => Str::random(10) . Str::random(10),

                        ];

                        PaymentLog::create($subscription);
                    }),
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
