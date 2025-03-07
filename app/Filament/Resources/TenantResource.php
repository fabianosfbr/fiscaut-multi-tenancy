<?php

namespace App\Filament\Resources;

use App\Enums\Tenant\PaymentLogStatusEnum;
use App\Filament\Resources\TenantResource\Pages;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use Closure;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralLabel = 'Empresas';

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
                            ->rules([
                                fn(): Closure => function (string $attribute, $value, Closure $fail) {
                                    $value = str_replace(['-', '.', '/'], '', $value);
                                    $domain = Tenant::where('cnpj', $value)->first();
                                    if ($domain) {
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
                            ->suffix('.localhost'),
                    ])->columns(2),

                Section::make('Configuração do Banco de Dados')
                    ->schema([
                        TextInput::make('db_host')
                            ->label('Host do Banco de Dados')
                            ->placeholder('localhost')
                            ->helperText('Deixe em branco para usar o padrão do sistema')
                            ->columnSpan(1),
                        TextInput::make('db_name')
                            ->label('Nome do Banco')
                            ->helperText('Deixe em branco para usar o padrão do sistema')
                            ->columnSpan(1),
                        TextInput::make('db_username')
                            ->label('Usuário do Banco')
                            ->helperText('Deixe em branco para usar o padrão do sistema')
                            ->columnSpan(1),
                        TextInput::make('db_password')
                            ->label('Senha do Banco')
                            ->password()
                            ->revealable()
                            ->helperText('Deixe em branco para usar o padrão do sistema')
                            ->columnSpan(1),
                    ])->columns(2)
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
                Tables\Actions\EditAction::make(),
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
