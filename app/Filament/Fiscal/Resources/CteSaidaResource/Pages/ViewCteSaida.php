<?php

namespace App\Filament\Fiscal\Resources\CteSaidaResource\Pages;

use App\Filament\Fiscal\Resources\CteSaidaResource;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewCteSaida extends ViewRecord
{
    protected static string $resource = CteSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cte-list')
                ->label('Voltar para lista')
                ->color('gray')
                ->url(fn(): string => CteSaidaResource::getUrl('index')),


        ];
    }

    public function getHeading(): string
    {
        return __('Conhecimento de Transporte');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nome_tomador')
                            ->label('Tomador')
                            ->columnSpan(2)
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large),

                        Split::make([
                            TextEntry::make('numero')
                                ->label('CT-e Nº')
                                ->weight('bold'),
                            TextEntry::make('serie')
                                ->label('Série')
                                ->weight('bold'),
                            TextEntry::make('valor_total')
                                ->label('Valor Total')
                                ->money('BRL')
                                ->weight('bold'),
                        ])->columnSpan(1),
                    ]),



                Section::make('Detalhes do CT-e')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('numero')
                                    ->label('N°'),

                                TextEntry::make('nome_emitente')
                                    ->label('Razão Social Emitente'),

                                TextEntry::make('chave_acesso')
                                    ->label('Chave de Acesso')
                                    ->copyable(),

                                TextEntry::make('cnpj_emitente')
                                    ->label('CNPJ Emitente')
                                    ->formatStateUsing(fn(string $state): string => formatar_cnpj_cpf($state)),

                                TextEntry::make('valor_total')
                                    ->label('Valor')
                                    ->money('BRL'),

                                TextEntry::make('logradouro_emitente')
                                    ->label('Endereço Emitente')
                                    ->default('Não informado'),

                                TextEntry::make('data_emissao')
                                    ->label('Data Emissão')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('municipio_uf_emitente')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        $municipio = $record->municipio_emitente ?? '';
                                        $uf = $record->uf_emitente ?? '';
                                        $cep = $record->cep_emitente ?? '';

                                        if (empty($municipio) && empty($uf) && empty($cep)) {
                                            return 'Não informado';
                                        }

                                        $cepFormatado = !empty($cep) ? ' - ' . formatar_cep($cep) : '';
                                        return "{$municipio}/{$uf}{$cepFormatado}";
                                    }),
                            ]),
                    ]),

                    Section::make('Remetente')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('nome_remetente')
                                    ->label('Razão Social'),
                                TextEntry::make('cnpj_remetente')
                                    ->label('CNPJ')
                                    ->formatStateUsing(fn(string $state): string => formatar_cnpj_cpf($state)),
                                TextEntry::make('telefone_remetente')
                                    ->label('Telefone contato')
                                    ->default('Não informado'),
                                TextEntry::make('logradouro_remetente')
                                    ->label('Endereço')
                                    ->default('Não informado'),
                                TextEntry::make('email_remetente')
                                    ->label('Email contato')
                                    ->default('Não informado'),
                                TextEntry::make('municipio_uf_remetente')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        $municipio = $record->municipio_remetente ?? '';
                                        $uf = $record->uf_remetente ?? '';
                                        $cep = $record->cep_remetente ?? '';

                                        if (empty($municipio) && empty($uf) && empty($cep)) {
                                            return 'Não informado';
                                        }

                                        $cepFormatado = !empty($cep) ? ' - ' . formatar_cep($cep) : '';
                                        return "{$municipio}/{$uf}{$cepFormatado}";
                                    }),
                            ])->columns(2),
                    ]),                    

                Section::make('Destinatário')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('nome_destinatario')
                                    ->label('Razão Social'),
                                TextEntry::make('cnpj_destinatario')
                                    ->label('CNPJ')
                                    ->formatStateUsing(fn(string $state): string => formatar_cnpj_cpf($state)),
                                TextEntry::make('telefone_destinatario')
                                    ->label('Telefone contato')
                                    ->default('Não informado'),
                                TextEntry::make('logradouro_destinatario')
                                    ->label('Endereço')
                                    ->default('Não informado'),
                                TextEntry::make('email_destinatario')
                                    ->label('Email contato')
                                    ->default('Não informado'),
                                TextEntry::make('municipio_uf_destinatario')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        $municipio = $record->municipio_destinatario ?? '';
                                        $uf = $record->uf_destinatario ?? '';
                                        $cep = $record->cep_destinatario ?? '';

                                        if (empty($municipio) && empty($uf) && empty($cep)) {
                                            return 'Não informado';
                                        }

                                        $cepFormatado = !empty($cep) ? ' - ' . formatar_cep($cep) : '';
                                        return "{$municipio}/{$uf}{$cepFormatado}";
                                    }),
                            ])->columns(2),
                    ]),

                Section::make('Dados do Expedidor')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('nome_expedidor')
                                    ->label('Razão Social')
                                    ->default('Não informado'),

                                TextEntry::make('cnpj_expedidor')
                                    ->label('CNPJ')
                                    ->formatStateUsing(fn($state) => $state ? formatar_cnpj_cpf($state) : 'Não informado'),

                                TextEntry::make('ie_expedidor')
                                    ->label('Inscrição Estadual')
                                    ->default('Não informado'),

                                TextEntry::make('xFant_expedidor')
                                    ->label('Nome Fantasia')
                                    ->default('Não informado'),

                                TextEntry::make('fone_expedidor')
                                    ->label('Telefone')
                                    ->default('Não informado'),

                                TextEntry::make('endereco_expedidor')
                                    ->label('Endereço')
                                    ->state(function ($record): string {
                                        $logradouro = $record->logradouro_expedidor ?? '';
                                        $numero = $record->numero_expedidor ?? '';
                                        $complemento = $record->complemento_expedidor ? ', ' . $record->complemento_expedidor : '';
                                        $bairro = $record->bairro_expedidor ? ', ' . $record->bairro_expedidor : '';

                                        if (empty($logradouro) && empty($numero)) {
                                            return 'Não informado';
                                        }

                                        return "{$logradouro}, {$numero}{$complemento}{$bairro}";
                                    }),

                                TextEntry::make('municipio_uf_expedidor')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        $municipio = $record->municipio_expedidor ?? '';
                                        $uf = $record->uf_expedidor ?? '';
                                        $cep = $record->cep_expedidor ?? '';

                                        if (empty($municipio) && empty($uf) && empty($cep)) {
                                            return 'Não informado';
                                        }

                                        $cepFormatado = !empty($cep) ? ' - ' . formatar_cep($cep) : '';
                                        return "{$municipio}/{$uf}{$cepFormatado}";
                                    }),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Dados do Recebedor')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('nome_recebedor')
                                    ->label('Razão Social')
                                    ->default('Não informado'),

                                TextEntry::make('cnpj_recebedor')
                                    ->label('CNPJ')
                                    ->formatStateUsing(fn($state) => $state ? formatar_cnpj_cpf($state) : 'Não informado'),

                                TextEntry::make('ie_recebedor')
                                    ->label('Inscrição Estadual')
                                    ->default('Não informado'),

                                TextEntry::make('xFant_recebedor')
                                    ->label('Nome Fantasia')
                                    ->default('Não informado'),

                                TextEntry::make('fone_recebedor')
                                    ->label('Telefone')
                                    ->default('Não informado'),

                                TextEntry::make('endereco_recebedor')
                                    ->label('Endereço')
                                    ->state(function ($record): string {
                                        $logradouro = $record->logradouro_recebedor ?? '';
                                        $numero = $record->numero_recebedor ?? '';
                                        $complemento = $record->complemento_recebedor ? ', ' . $record->complemento_recebedor : '';
                                        $bairro = $record->bairro_recebedor ? ', ' . $record->bairro_recebedor : '';

                                        if (empty($logradouro) && empty($numero)) {
                                            return 'Não informado';
                                        }

                                        return "{$logradouro}, {$numero}{$complemento}{$bairro}";
                                    }),

                                TextEntry::make('municipio_uf_recebedor')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        $municipio = $record->municipio_recebedor ?? '';
                                        $uf = $record->uf_recebedor ?? '';
                                        $cep = $record->cep_recebedor ?? '';

                                        if (empty($municipio) && empty($uf) && empty($cep)) {
                                            return 'Não informado';
                                        }

                                        $cepFormatado = !empty($cep) ? ' - ' . formatar_cep($cep) : '';
                                        return "{$municipio}/{$uf}{$cepFormatado}";
                                    }),
                            ])
                            ->columns(2),
                    ]),



                Section::make('Impostos')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('base_calculo_icms')
                                    ->label('Base Cálc. ICMS')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms')
                                    ->label('Valor ICMS')
                                    ->money('BRL'),
                                TextEntry::make('aliquota_icms')
                                    ->label('Alíquota ICMS')
                                    ->state(fn($record) =>
                                    isset($record->aliquota_icms) ?
                                        number_format($record->aliquota_icms, 2, ',', '.') . '%' : '0,00%'),
                                TextEntry::make('valor_servico')
                                    ->label('Valor Serviço')
                                    ->money('BRL'),
                                TextEntry::make('valor_receber')
                                    ->label('Valor a Receber')
                                    ->money('BRL'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }
}
