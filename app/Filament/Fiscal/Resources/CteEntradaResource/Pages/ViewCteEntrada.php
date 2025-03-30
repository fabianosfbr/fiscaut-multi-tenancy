<?php

namespace App\Filament\Fiscal\Resources\CteEntradaResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Fiscal\Resources\CteEntradaResource;

class ViewCteEntrada extends ViewRecord
{
    protected static string $resource = CteEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cte-list')
                ->label('Voltar para lista')
                ->color('gray')
                ->url(fn(): string => CteEntradaResource::getUrl('index')),
                
            Action::make('download_xml')
                ->label('Download XML')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => route('fiscal.ctes.download.xml', $this->record))
                ->openUrlInNewTab(),
                
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document')
                ->url(fn (): string => route('fiscal.ctes.download.pdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    public function getHeading(): string
    {
        return __('CT-e de Entrada');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Detalhes do CT-e')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Dados Gerais')
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('nome_emitente')
                                            ->label('Emitente')
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

                                                TextEntry::make('endereco_emitente_completo')
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
                                                TextEntry::make('endereco_destinatario_completo')
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
                                                    ->state(fn ($record) => 
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
                            ]),
                        Tabs\Tab::make('Difal')
                            ->schema([
                                Section::make('Diferencial de Alíquota (DIFAL)')
                                    ->description('Cálculo do diferencial de alíquota entre estado de origem e destino.')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextEntry::make('uf_emitente')
                                                    ->label('UF Origem'),
                                                TextEntry::make('uf_destinatario')
                                                    ->label('UF Destino'),
                                                TextEntry::make('base_calculo_icms')
                                                    ->label('Base de Cálculo')
                                                    ->money('BRL'),
                                                TextEntry::make('aliquota_icms')
                                                    ->label('Alíquota Origem')
                                                    ->state(fn ($record) => 
                                                        isset($record->aliquota_icms) ? 
                                                        number_format($record->aliquota_icms, 2, ',', '.') . '%' : '0,00%'),
                                                TextEntry::make('aliquota_destino')
                                                    ->label('Alíquota Destino')
                                                    ->state(function ($record) {
                                                        if ($record->uf_emitente === $record->uf_destinatario) {
                                                            return 'Não aplicável (mesma UF)';
                                                        }
                                                        
                                                        $aliquota = $record->obterAliquotaDestino();
                                                        return number_format($aliquota, 2, ',', '.') . '%';
                                                    }),
                                                TextEntry::make('difal_calculado')
                                                    ->label('DIFAL Calculado')
                                                    ->state(function ($record) {
                                                        $difal = $record->calcularDifal();
                                                        return 'R$ ' . number_format($difal, 2, ',', '.');
                                                    })
                                                    ->color(fn ($record) => $record->possuiDifal() ? 'success' : 'gray'),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
} 