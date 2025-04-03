<?php

namespace App\Jobs\Downloads;

use ZipArchive;
use App\Models\Tenant\User;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use NFePHP\DA\NFe\Danfe;
use App\Models\Tenant\NotaFiscalEletronica;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class DownloadAvancadoNfeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $records,
        public array $data,
        public string $userId,
        public string $tenantId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tenant = Tenant::find($this->tenantId);
        try {

            // Cria o diretório do mês/ano se não existir
            $mesAno = now()->format('m-Y');
            $diretorioMesAno = 'public/downloads/' . $mesAno;
            if (!Storage::exists($diretorioMesAno)) {
                Storage::makeDirectory($diretorioMesAno);
            }

            // Cria um arquivo ZIP temporário
            $zipFileName = 'nfes_avancado_' . now()->format('YmdHis') . '.zip';
            $publicPath = 'downloads/' . $mesAno . '/' . $zipFileName;
            $zipFilePath = storage_path('app/public/' . $publicPath);

            // Garante que o diretório temp existe
            if (!Storage::exists('temp')) {
                Storage::makeDirectory('temp');
            }

            $zip = new ZipArchive();
            if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                throw new \Exception('Não foi possível criar o arquivo ZIP');
            }

            $erros = [];

            foreach ($this->records as $record) {
                try {
                    // Verifica se a opção de aplicar estrutura de etiqueta está ativada
                    $aplicarEstruturaEtiqueta = $this->data['aplicar_estrutura_etiqueta'] ?? false;
                    
                    // Define os diretórios para os arquivos
                    if ($aplicarEstruturaEtiqueta) {
                        // Obtém os diretórios baseados nas etiquetas
                        $diretorios = $this->getDiretoriosPorEtiquetas($record);
                    } else {
                        // Se não aplicar estrutura, usa apenas o diretório raiz (string vazia sem barra)
                        $diretorios = ['raiz'];
                    }

                    // Adiciona XML em cada diretório
                    if (!empty($record->xml_content)) {
                        foreach ($diretorios as $diretorio) {
                            if ($diretorio === 'raiz') {
                                // Salva no diretório raiz, sem prefixo
                                $xmlPath = $record->chave_acesso . '.xml';
                            } else {
                                // Salva no diretório da etiqueta
                                $xmlPath = $diretorio . '/' . $record->chave_acesso . '.xml';
                            }
                            
                            $zip->addFromString($xmlPath, $record->xml_content);
                            
                            // Adiciona log para debug
                            Log::info("Adicionando XML ao ZIP: {$xmlPath}");
                        }
                    }

                    // Adiciona PDF em cada diretório
                    if (!empty($record->xml_content)) {
                        $danfe = new Danfe($record->xml_content);
                        $danfe->creditsIntegratorFooter(env('APP_FOOTER_CREDITS_DANFE'), false);
                        $pdf = $danfe->render();

                        // Se a opção de adicionar etiquetas no PDF estiver marcada
                        if ($this->data['adicionar_etiquetas_pdf'] ?? false) {
                            $pdf = $this->adicionarPaginaResumoEtiquetas($pdf, $record);
                        }

                        foreach ($diretorios as $diretorio) {
                            if ($diretorio === 'raiz') {
                                // Salva no diretório raiz, sem prefixo
                                $pdfPath = $record->chave_acesso . '.pdf';
                            } else {
                                // Salva no diretório da etiqueta
                                $pdfPath = $diretorio . '/' . $record->chave_acesso . '.pdf';
                            }
                            
                            $zip->addFromString($pdfPath, $pdf);
                            
                            // Adiciona log para debug
                            Log::info("Adicionando PDF ao ZIP: {$pdfPath}");
                        }
                    }
                } catch (\Exception $e) {
                    $erros[] = "Erro ao processar nota {$record->numero}: {$e->getMessage()}";
                    Log::error("Erro ao processar nota {$record->numero}: {$e->getMessage()}");
                }
            }

            $zip->close();



            // Gera URL temporária para download (expira em 1 hora)
            $url = $tenant->domains->first()->domain;
            URL::formatHostUsing(function () use ($url) {
                $protocol = request()->isSecure() ? 'https://' : 'http://';

                return $protocol . $url;
            });
            $downloadUrl = URL::temporarySignedRoute(
                'download.avancado.nfe',
                now()->addMonth(),
                [
                    'filename' => $zipFileName,
                    'mes_ano' => $mesAno
                ]
            );

            // Se houve erros, registra no log
            if (!empty($erros)) {
                Log::warning('Erros durante o download avançado de NFes:', ['erros' => $erros]);
            }

            // Notifica o usuário com o link de download
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('Processamento concluído')
                    ->body('O processamento das notas foi concluído com sucesso.')
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->label('Baixar arquivo')
                            ->url($downloadUrl)
                            ->openUrlInNewTab(),
                    ])
                    ->sendToDatabase($user);
            }
        } catch (\Exception $e) {
            Log::error('Erro no job de download avançado: ' . $e->getMessage());

            // Notifica o usuário sobre o erro
            $user = User::find($this->userId);
            if ($user) {
                Notification::make()
                    ->title('Erro no processamento')
                    ->body('Ocorreu um erro ao processar o download avançado das notas.')
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }

    /**
     * Obtém os diretórios baseados nas etiquetas da nota
     * Retorna um array com um diretório para cada etiqueta
     */
    private function getDiretoriosPorEtiquetas(NotaFiscalEletronica $nota): array
    {
        $etiquetas = $nota->tagged;

        if ($etiquetas->isEmpty()) {
            return ['#sem etiqueta'];
        }

        // Retorna um array com o nome de cada etiqueta através do relacionamento tag
        return $etiquetas->map(function ($etiqueta) {
            return $etiqueta->tag->code . '-' . $etiqueta->tag->name;
        })->toArray();
    }

    /**
     * Adiciona uma página de resumo com as informações das etiquetas ao PDF
     */
    private function adicionarPaginaResumoEtiquetas(string $pdfContent, NotaFiscalEletronica $nota): string
    {
        $etiquetas = $nota->tagged;

        // Se não houver etiquetas, retorna o PDF original sem alterações
        if ($etiquetas->isEmpty()) {
            return $pdfContent;
        }

        // Garante que os diretórios temporários existam
        $mesAno = now()->format('m-Y');
        $tempDir = 'temp/' . $mesAno;
        
        Storage::makeDirectory($tempDir);

        // Gera nomes aleatórios para evitar conflitos
        $resumoFilename = Str::random(8) . '_resumo.pdf';
        $nfeFilename = Str::random(8) . '_original.pdf';
        $outputFilename = Str::random(8) . '_final.pdf';
        
        $resumoPath = storage_path('app/' . $tempDir . '/' . $resumoFilename);
        $nfePath = storage_path('app/' . $tempDir . '/' . $nfeFilename);
        $outputPath = storage_path('app/' . $tempDir . '/' . $outputFilename);

        try {
            // Salva o PDF original em disco
            Storage::put($tempDir . '/' . $nfeFilename, $pdfContent);

            // Gera o PDF da página de resumo
            Pdf::loadView('pdf.resumo-etiquetas', [
                'tags' => $nota->tagged()->get()->toArray(),
            ])->save($resumoPath);

            // Inicializa e configura o PDFMerger
            $pdfMerger = PDFMerger::init();
            $pdfMerger->addPDF($nfePath, 'all');
            $pdfMerger->addPDF($resumoPath, 'all');
            $pdfMerger->merge();
            $pdfMerger->save($outputPath);

            // Lê o PDF combinado
            $newPdf = Storage::get($tempDir . '/' . $outputFilename);
            
            return $newPdf;
        } finally {
            // Limpa os arquivos temporários independentemente do resultado
            Storage::delete([
                $tempDir . '/' . $nfeFilename,
                $tempDir . '/' . $resumoFilename,
                $tempDir . '/' . $outputFilename
            ]);
        }
    }
}
