<?php

namespace App\Filament\Fiscal\Pages\Importar;

use Exception;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Log;

use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Livewire;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use App\Services\Tenant\Sefaz\NfeService;
use Filament\Forms\Components\FileUpload;
use App\Models\Tenant\NotaFiscalEletronica;
use Filament\Forms\Components\ToggleButtons;
use App\Livewire\ResultadoImportacaoXmlModal;
use App\Services\Tenant\Xml\XmlCteReaderService;
use App\Services\Tenant\Xml\XmlExtractorService;
use App\Services\Tenant\Xml\XmlNfeReaderService;
use App\Services\Tenant\Xml\XmlIdentifierService;
use Filament\Pages\Concerns\InteractsWithFormActions;
use App\Models\Tenant\ConhecimentoTransporteEletronico;
use Filament\Notifications\Actions\Action as NotificationAction;

class NfeCte extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationGroup = 'Ferramentas';

    protected static ?string $modelLabel = 'Importar XML';

    protected static ?string $navigationLabel = 'Importar XML';

    protected static ?string $title = 'Importar NF-e/CT-e';

    protected static string $view = 'filament.fiscal.pages.importar.nfe-cte';

    public $filesToImport = [];

    public $xml_type = [];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Upload de XML')
                    ->description('Selecione um ou mais arquivos XML de NF-e para importar')
                    ->schema([
                        FileUpload::make('xmlFiles')
                            ->label('Arquivos XML/ZIP')
                            ->multiple()
                            ->helperText('Você pode enviar arquivos XML (NFe ou CTe) individuais ou um arquivo ZIP contendo vários XMLs')
                            ->maxSize(50000) // 50MB
                            ->required(),
                    ]),
                Livewire::make(
                    ResultadoImportacaoXmlModal::class,
                    [
                        'id' => 'importar_xml',
                        'width' => '2xl',
                    ]
                )
            ])
            ->statePath('data');
    }

    public function processarXmls(): void
    {
        $this->validate();

        try {
            $resultados = [
                'nfe' => [
                    'sucessos' => [],
                    'atualizacoes' => [],
                    'falhas' => [],
                    'total' => 0,
                ],
                'cte' => [
                    'sucessos' => [],
                    'atualizacoes' => [],
                    'falhas' => [],
                    'total' => 0,
                ],
                'arquivos_processados' => 0,
            ];

            $xmlExtractor = app(XmlExtractorService::class);

            foreach ($this->data['xmlFiles'] as $file) {

                try {
                    $xmlContents = $xmlExtractor->extract($file);

                    foreach ($xmlContents as $xmlData) {
                        try {
                            $xmlContent = $xmlData['content'];
                            $nomeArquivo = $xmlData['filename'];

                            $tipoXml = XmlIdentifierService::identificarTipoXml($xmlContent);


                            if ($tipoXml === XmlIdentifierService::TIPO_NFE) {
                                $this->processarNfe($xmlContent, $nomeArquivo, $resultados['nfe']);
                            } else {
                                $this->processarCte($xmlContent, $nomeArquivo, $resultados['cte']);
                            }
                        } catch (Exception $e) {
                            $resultados['falhas'][] = [
                                'arquivo' => $file->getClientOriginalName(),
                                'erro' => "Erro ao processar arquivo: " . $e->getMessage()
                            ];
                            $resultados['arquivos_processados']++;
                        }
                    }

                    $resultados['arquivos_processados']++;
                } catch (Exception $e) {
                    // Se não conseguir extrair o arquivo, considera como NFe por padrão
                    $resultados['nfe']['falhas'][] = [
                        'arquivo' => $file->getClientOriginalName(),
                        'erro' => "Erro ao processar arquivo: " . $e->getMessage()
                    ];
                    $resultados['arquivos_processados']++;
                }
            }
        } catch (Exception $e) {
            Notification::make()
                ->danger()
                ->title('Erro no Processamento')
                ->body('Ocorreu um erro inesperado durante o processamento dos arquivos.')
                ->send();

            Log::error('Erro no processamento de XMLs: ' . $e->getMessage());
        }

        // Reseta o formulário
        $this->form->fill();


        $this->dispatch('openModal', $resultados);


        // Determina o tipo de notificação com base nos resultados
        // if (empty($resultados['falhas'])) {
        //     // Sucesso total
        //     Notification::make()
        //         ->success()
        //         ->title('Importação Concluída')
        //         ->body("Todas as {$resultados['total']} notas foram importadas com sucesso!")
        //         ->actions([
        //             NotificationAction::make('ver_detalhes')
        //                 ->label('Ver Detalhes')
        //                 ->color('success')
        //                 ->dispatch('openModal', data: ['resultados' => 111]),
        //         ])
        //         ->send();
        // } elseif (empty($resultados['sucessos'])) {
        //     // Falha total
        //     Notification::make()
        //         ->danger()
        //         ->title('Falha na Importação')
        //         ->body("Nenhuma nota foi importada. Todas as {$resultados['total']} tentativas falharam.")
        //         ->actions([
        //             NotificationAction::make('ver_erros')
        //                 ->label('Ver Erros')
        //                 ->color('danger')
        //                 ->dispatch('openModal', $resultados)
        //         ])
        //         ->send();
        // } else {
        //     // Resultado parcial
        //     Notification::make()
        //         ->warning()
        //         ->title('Importação Parcialmente Concluída')
        //         ->body(sprintf(
        //             "Processadas: %d | Sucesso: %d | Falhas: %d",
        //             $resultados['total'],
        //             count($resultados['sucessos']),
        //             count($resultados['falhas'])
        //         ))
        //         ->actions([
        //             NotificationAction::make('ver_resultados')
        //                 ->label('Ver Resultados')
        //                 ->color('warning')
        //                 ->dispatch('openModal', ['resultados' => $resultados])
        //         ])
        //         ->send();
        // }



    }


    private function processarNfe(string $xmlContent, string $nomeArquivo, array &$resultados): void
    {
        $xmlReader = (new XmlNfeReaderService())
            ->loadXml($xmlContent)
            ->parse();

        $dadosXml = $xmlReader->getData();
        $chaveAcesso = $dadosXml['chave_acesso'];

        $existente = NotaFiscalEletronica::where('chave_acesso', $chaveAcesso)->first();
        $nfe = $xmlReader->save();

        if ($existente) {
            $resultados['atualizacoes'][] = [
                'arquivo' => $nomeArquivo,
                'chave' => $nfe->chave_acesso,
                'numero' => $nfe->numero,
                'emitente' => $nfe->nome_emitente,
                'status_anterior' => $existente->status_nota,
                'status_novo' => $nfe->status_nota,
            ];
        } else {
            $resultados['sucessos'][] = [
                'arquivo' => $nomeArquivo,
                'chave' => $nfe->chave_acesso,
                'numero' => $nfe->numero,
                'emitente' => $nfe->nome_emitente,
                'valor' => number_format($nfe->valor_total, 2, ',', '.'),
            ];
        }
        $resultados['total']++;
    }

    private function processarCte(string $xmlContent, string $nomeArquivo, array &$resultados): void
    {
        $xmlReader = (new XmlCteReaderService())
            ->loadXml($xmlContent)
            ->parse();

        $dadosXml = $xmlReader->getData();
        $chaveAcesso = $dadosXml['chave_acesso'];

        $existente = ConhecimentoTransporteEletronico::where('chave_acesso', $chaveAcesso)->first();
        $cte = $xmlReader->save();

        if ($existente) {
            $resultados['atualizacoes'][] = [
                'arquivo' => $nomeArquivo,
                'chave' => $cte->chave_acesso,
                'numero' => $cte->numero,
                'emitente' => $cte->nome_emitente,
                'status_anterior' => $existente->status_cte,
                'status_novo' => $cte->status_cte,
            ];
        } else {
            $resultados['sucessos'][] = [
                'arquivo' => $nomeArquivo,
                'chave' => $cte->chave_acesso,
                'numero' => $cte->numero,
                'emitente' => $cte->nome_emitente,
                'valor' => number_format($cte->valor_total, 2, ',', '.'),
            ];
        }
        $resultados['total']++;
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('processarXmls')
                ->label('Importar')
                ->action('processarXmls')
                ->color('primary')
        ];
    }
}
