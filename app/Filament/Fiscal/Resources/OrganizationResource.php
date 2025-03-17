<?php

namespace App\Filament\Fiscal\Resources;

use Exception;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Tenant\RegimesEmpresariaisEnum;
use Filament\Forms\Components\Actions\Action;
use App\Enums\Tenant\AtividadesEmpresariaisEnum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\OrganizationResource\Pages;
use App\Filament\Fiscal\Resources\OrganizationResource\RelationManagers;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationGroup = 'Configurações';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralLabel = 'Empresas';

    protected static ?string $pluralModelLabel = 'Empresas';

    protected static ?string $navigationLabel = 'Minhas Empresas';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Dados da empresa')
                        ->schema([
                            ...self::getOrganizationDataForm(),
                        ]),
                    Wizard\Step::make('Certificado digital')
                        ->description('Faça o upload do certificado digital A1 e informe a senha')
                        ->schema([
                            Section::make('Upload do Certificado')
                                ->description('Selecione o arquivo do certificado digital A1 (.pfx ou .p12)')
                                ->schema([
                                    Forms\Components\FileUpload::make('path_certificado')
                                        ->label('Certificado Digital')
                                        ->acceptedFileTypes(['application/x-pkcs12'])
                                        ->maxSize(2048)
                                        ->directory('certificados')
                                        ->visibility('private')
                                        ->columnSpanFull(),

                                    TextInput::make('senha_certificado')
                                        ->label('Senha do Certificado')
                                        ->password()
                                        ->required(fn(callable $get): bool => filled($get('path_certificado')))
                                        ->visible(fn(callable $get): bool => filled($get('path_certificado')))
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, $get, Forms\Set $set) {
                                            if (!$state || !$get('path_certificado')) {
                                                return;
                                            }

                                            try {
                                                $certificadoPath = $get('path_certificado');
                                                foreach ($certificadoPath as $path) {
                                                    $pfx = $path->get();
                                                }

                                                $cert = openssl_pkcs12_read($pfx, $certInfo, $state);


                                                if (!$cert) {
                                                    throw new Exception('Senha inválida');
                                                }

                                                // Extrair informações do certificado
                                                $x509 = openssl_x509_parse($certInfo['cert']);

                                                if (!$x509) {
                                                    throw new Exception('Erro ao ler o certificado');
                                                }

                                                // Atualizar campos com informações do certificado
                                                $set('razao_social', $x509['subject']['CN']);
                                                $set('cnpj', preg_replace('/[^0-9]/', '', $x509['subject']['CN']));
                                                $set('validade_certificado', date('d-m-Y', $x509['validTo_time_t']));

                                                // Salvar o conteúdo do certificado
                                                $set('certificado_content', base64_encode($pfx));

                                                Notification::make()
                                                    ->title('Certificado validado com sucesso!')
                                                    ->success()
                                                    ->send();
                                            } catch (Exception $e) {
                                                Notification::make()
                                                    ->title('Erro ao validar certificado')
                                                    ->body($e->getMessage())
                                                    ->danger()
                                                    ->send();
                                            }
                                        })
                                        ->columnSpanFull(),

                                    TextInput::make('validade_certificado')
                                        ->label('Validade do Certificado')
                                        ->disabled()
                                        ->columnSpanFull(),

                                    Hidden::make('certificado_content'),
                                    Hidden::make('validade_certificado'),
                                ]),
                        ])->columnSpanFull()

                ])
                    ->submitAction(new HtmlString(
                        Blade::render(<<<BLADE
                                    <x-filament::button
                                        type="submit"
                                        size="sm">
                                        Salvar
                                    </x-filament::button>
                                    BLADE)
                    ))
                    ->nextAction(
                        fn(Action $action) => $action->label('Avançar'),
                    )
                    ->columnSpanFull()

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->defaultSort('razao_social')
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Razão Social')
                    ->sortable()
                    ->searchable()
                    ->getStateUsing(function (Model $record) {
                        $issuer = explode(':', $record->razao_social);

                        return $issuer[0];
                    })
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 40) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    }),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('CNPJ foi copiado')
                    ->copyMessageDuration(1500),

                TextColumn::make('validade_certificado')
                    ->label('Validade Cert.')
                    ->date('d/m/Y')
                    ->toggleable(),

                TextColumn::make('dias_para_vencimento')
                    ->label('Dias para Vencimento')
                    ->badge()
                    ->state(function (Model $record): string {
                        if (!$record->validade_certificado) {
                            return 'Sem certificado';
                        }

                        $dataVencimento = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $record->validade_certificado);
                        $hoje = \Carbon\Carbon::now();
                        $diasRestantes = $hoje->diffInDays($dataVencimento, false);

                        if ($diasRestantes < 0) {
                            return 'Vencido';
                        }

                        return intval($diasRestantes) . ' dias';
                    })
                    ->color(function (Model $record): string {
                        if (!$record->validade_certificado) {
                            return 'gray';
                        }

                        $dataVencimento = Carbon::createFromFormat('Y-m-d H:i:s', $record->validade_certificado);
                        $hoje = Carbon::now();
                        $diasRestantes = $hoje->diffInDays($dataVencimento, false);

                        if ($diasRestantes < 0) {
                            return 'danger';
                        }

                        if ($diasRestantes <= 30) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->toggleable()
                    ->sortable(),

                TextColumn::make('servicos_habilitados')
                    ->label('Serviços')
                    ->state(function (Model $record): string {

                        $html = '<div class="flex items-center gap-2">';
                        $servicos = [
                            'NFe' => $record->is_enable_nfe_servico,
                            'CTe' => $record->is_enable_cte_servico,
                            'NFSe' => $record->is_enable_nfse_servico,
                            'CFe' => $record->is_enable_cfe_servico,
                            'Sieg' => $record->is_enable_sync_sieg,
                        ];
                        foreach ($servicos as $nome => $habilitado) {
                            $cor = $habilitado ? '#98D8AA' : '#FF6D60';
                            $html .= "<span style='border-radius: 10px; padding:2px 5px 2px 5px; font-size: 11px; background-color: {$cor};'>{$nome}</span>";
                        }
                        $html .= '</div>';

                        return new HtmlString($html);
                    })
                    ->html()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('download_certificado')
                        ->label('Baixar Certificado')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->disabled(fn(Organization $record): bool => !empty($record->certificado_content))
                        ->action(function (Organization $record) {
                            $certificadoContent = base64_decode($record->certificado_content);
                            $nomeArquivo = $record->cnpj . '.pfx';

                            return response()->streamDownload(function () use ($certificadoContent) {
                                echo $certificadoContent;
                            }, $nomeArquivo, [
                                'Content-Type' => 'application/x-pkcs12',
                            ]);
                        }),
                ])
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }

    public static function getOrganizationDataForm()
    {
        return [
            Section::make('Dados da Empresa')
                ->description('Preencha os dados da empresa para criação ou edição.')
                ->schema([
                    TextInput::make('razao_social')
                        ->label('Razão Social')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'A Razão Social é obrigatória',
                        ]),

                    TextInput::make('cnpj')
                        ->label('CNPJ')
                        ->required()
                        ->maxLength(14)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'O CNPJ é obrigatório',
                            'unique' => 'Este CNPJ já está cadastrado',
                        ]),

                    TextInput::make('inscricao_estadual')
                        ->label('Inscrição Estadual')
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'A Inscrição Estadual é obrigatória',
                        ]),

                    TextInput::make('inscricao_municipal')
                        ->label('Inscrição Municipal (sem dígito)')
                        ->columnSpan(2),

                    Select::make('cod_municipio_ibge')
                        ->label('Município')
                        ->required()
                        ->options([
                            '3525904' => 'Jundiaí',
                        ])
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'O Município é obrigatório',
                        ]),

                    Select::make('regime')
                        ->required()
                        ->options(RegimesEmpresariaisEnum::class)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'O Regime é obrigatório',
                        ]),
                    Select::make('atividade')
                        ->label('Atividade')
                        ->multiple()
                        ->required()
                        ->options(AtividadesEmpresariaisEnum::class)
                        ->columnSpan(2)
                        ->validationMessages([
                            'required' => 'A Atividade é obrigatória',
                        ]),

                ])->columns(6),

            Section::make('Serviços Habilitados')
                ->description('Informe os serviços que estarão habilitados para essa empresa')
                ->schema([
                    Toggle::make('is_enable_nfe_servico')
                        ->label('NFe')
                        ->inline(),
                    Toggle::make('is_enable_cte_servico')
                        ->label('CTe')
                        ->inline(),
                    Toggle::make('is_enable_nfse_servico')
                        ->label('NFSe')
                        ->inline(),
                    Toggle::make('is_enable_cfe_servico')
                        ->label('CFe')
                        ->inline(),
                    Toggle::make('is_enable_sync_sieg')
                        ->label('Sieg')
                        ->inline(),

                ])->columns(3),
        ];
    }
}
