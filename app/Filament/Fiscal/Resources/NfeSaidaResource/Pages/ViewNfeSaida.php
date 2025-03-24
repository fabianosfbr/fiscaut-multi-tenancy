<?php

namespace App\Filament\Fiscal\Resources\NfeSaidaResource\Pages;

use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\TextEntry;
use App\Livewire\Component\ProductTableInfolist;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Fiscal\Resources\NfeSaidaResource;

class ViewNfeSaida extends ViewRecord
{
    protected static string $resource = NfeSaidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nfe-list')
                ->label('Voltar para lista')
                ->color('gray')
                ->url(fn(): string => NfeSaidaResource::getUrl('index')),

        ];
    }

    public function getHeading(): string
    {
        return __('NFe de Saída');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Cabeçalho da Nota
                Section::make()
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nome_destinatario')
                            ->label('Destinatário')
                            ->columnSpan(2)
                            ->weight('bold')
                            ->size(TextEntry\TextEntrySize::Large),

                        Split::make([
                            TextEntry::make('numero')
                                ->label('NF-e Nº')
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

                Section::make('Etiquetas')
                    ->hidden(fn($record) => empty($record->tagNamesWithCodeAndValue()))
                    ->schema([
                        TextEntry::make('tags')
                            ->hiddenLabel()
                            ->state(fn($record) => collect($record->tagNamesWithCodeAndValue())->map(fn($tag) => "<li>{$tag}</li>")->implode(''))
                            ->columnSpanFull()
                            ->html(),
                    ]),

                Section::make('Detalhes da Nota')
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
                                    ->label('Endereço'),

                                TextEntry::make('data_emissao')
                                    ->label('Data Emissão')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('municipio_uf_emitente')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        return "{$record->municipio_emitente}/{$record->uf_emitente} - " . formatar_cep($record->cep_emitente);
                                    }),

                                TextEntry::make('natureza_operacao')
                                    ->label('Nat. Operação'),
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
                                    ->label('Telefone contato'),
                                TextEntry::make('endereco_destinatario_completo')
                                    ->label('Endereço'),
                                TextEntry::make('email_destinatario')
                                    ->label('Email contato'),
                                TextEntry::make('municipio_uf_destinatario')
                                    ->label('Município/UF - CEP')
                                    ->state(function ($record): string {
                                        return "{$record->municipio_destinatario}/{$record->uf_destinatario} - " . formatar_cep($record->cep_destinatario);
                                    }),
                            ])->columns(2),

                    ]),

                Section::make('Impostos')
                    ->schema([
                        Group::make()
                            ->schema([
                                TextEntry::make('valor_base_icms')
                                    ->label('Base Cálc. ICMS')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms')
                                    ->label('Valor ICMS')
                                    ->money('BRL'),
                                TextEntry::make('valor_base_icms_st')
                                    ->label('Base Calc. ST')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms_st')
                                    ->label('Valor ICMS ST')
                                    ->money('BRL'),
                                TextEntry::make('valor_imposto_importacao')
                                    ->label('V. Imp. Import.')
                                    ->money('BRL'),
                                TextEntry::make('valor_fundo_combate_uf_dest')
                                    ->label('V. FCP UF Dest.')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms_uf_remet')
                                    ->label('V. ICMS UF Remet.')
                                    ->money('BRL'),
                                TextEntry::make('valor_produtos')
                                    ->label('Total Produtos')
                                    ->money('BRL'),

                            ])
                            ->columns(8),

                        Group::make()
                            ->schema([
                                TextEntry::make('valor_frete')
                                    ->label('Valor Frete')
                                    ->money('BRL'),
                                TextEntry::make('valor_seguro')
                                    ->label('Valor Seguro')
                                    ->money('BRL'),
                                TextEntry::make('valor_desconto')
                                    ->label('Desconto')
                                    ->money('BRL'),
                                TextEntry::make('valor_outras_despesas')
                                    ->label('Outras Despesas')
                                    ->money('BRL'),


                                TextEntry::make('valor_ipi')
                                    ->label('Valor IPI')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms_uf_dest')
                                    ->label('V. ICMS UF Dest.')
                                    ->money('BRL'),
                                TextEntry::make('valor_cofins')
                                    ->label('Valor COFINS')
                                    ->money('BRL'),
                                TextEntry::make('valor_total')
                                    ->label('Valor Total Nota')
                                    ->money('BRL')
                                    ->weight('bold'),
                            ])
                            ->columns(8),
                    ]),

                Section::make('Produtos')
                    ->schema([
                        Livewire::make(ProductTableInfolist::class, [
                            'record' => $this->record,
                        ])
                    ]),

                
            ]);
    }
}
