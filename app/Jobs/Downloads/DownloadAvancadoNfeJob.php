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

            // Cria um arquivo ZIP temporário
            $zipFileName = 'nfes_avancado_' . now()->format('YmdHis') . '.zip';
            $zipFilePath = storage_path('app/temp/' . $zipFileName);

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
                    // Obtém os diretórios baseados nas etiquetas
                    $diretorios = $this->getDiretoriosPorEtiquetas($record);

                    // Adiciona XML em cada diretório
                    if (!empty($record->xml_content)) {
                        foreach ($diretorios as $diretorio) {
                            $xmlPath = $diretorio . '/' . $record->chave_acesso . '.xml';
                            $zip->addFromString($xmlPath, $record->xml_content);
                        }
                    }

                    // Adiciona PDF em cada diretório
                    if (!empty($record->xml_content)) {
                        $danfe = new Danfe($record->xml_content);
                        $danfe->creditsIntegratorFooter(env('APP_FOOTER_CREDITS_DANFE'), false);
                        $pdf = $danfe->render();

                        foreach ($diretorios as $diretorio) {
                            $pdfPath = $diretorio . '/' . $record->chave_acesso . '.pdf';
                            $zip->addFromString($pdfPath, $pdf);
                        }
                    }
                } catch (\Exception $e) {
                    $erros[] = "Erro ao processar nota {$record->numero}: {$e->getMessage()}";
                    Log::error("Erro ao processar nota {$record->numero}: {$e->getMessage()}");
                }
            }

            $zip->close();

            // Cria o diretório base downloads se não existir
            if (!Storage::exists('public/downloads')) {
                Storage::makeDirectory('public/downloads');
            }

            // Cria o diretório do mês/ano se não existir
            $mesAno = now()->format('m-Y');
            $diretorioMesAno = 'public/downloads/' . $mesAno;
            if (!Storage::exists($diretorioMesAno)) {
                Storage::makeDirectory($diretorioMesAno);
            }

            // Move o arquivo ZIP para a pasta do mês/ano
            $publicPath = 'downloads/' . $mesAno . '/' . $zipFileName;
            Storage::putFileAs('public', $zipFilePath, $publicPath);

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
}
