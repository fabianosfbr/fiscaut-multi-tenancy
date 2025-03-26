<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Tables\Actions\BulkAction;
use NFePHP\DA\NFe\Danfe;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadPdfBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'download_pdf_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Baixar DANFEs')
            ->icon('heroicon-o-document-text')
            ->requiresConfirmation()
            ->modalHeading('Baixar DANFEs selecionadas')
            ->modalDescription('Deseja baixar os PDFs das notas selecionadas? Isso pode levar alguns segundos.')
            ->modalSubmitActionLabel('Baixar DANFEs')
            ->successNotificationTitle('DANFEs baixados com sucesso')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                // Filtra apenas os registros que têm conteúdo XML
                $recordsWithXml = $records->filter(fn ($record) => !empty($record->xml_content));
                
                if ($recordsWithXml->isEmpty()) {
                    $this->failure();
                    
                    return $this->failureNotificationTitle('Nenhum XML disponível para gerar DANFEs');
                }
                
                // Cria um arquivo ZIP temporário
                $zipFileName = 'danfes_' . now()->format('YmdHis') . '.zip';
                $zipFilePath = storage_path('app/temp/' . $zipFileName);
                
                // Garante que o diretório temp existe
                if (!Storage::exists('temp')) {
                    Storage::makeDirectory('temp');
                }
                
                $zip = new ZipArchive();
                if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
                    $this->failure();
                    
                    return $this->failureNotificationTitle('Não foi possível criar o arquivo ZIP');
                }
                
                $erros = [];
                
                // Adiciona cada PDF ao arquivo ZIP
                foreach ($recordsWithXml as $record) {
                    try {
                        $danfe = new Danfe($record->xml_content);
                        $danfe->creditsIntegratorFooter(env('APP_FOOTER_CREDITS_DANFE'), false);
                        $pdf = $danfe->render();
                        
                        $filename = "{$record->chave_acesso}.pdf";
                        $zip->addFromString($filename, $pdf);
                    } catch (\Exception $e) {
                        $erros[] = "Erro ao gerar DANFE para a nota {$record->numero}: {$e->getMessage()}";
                    }
                }
                
                $zip->close();
                
                // Se houve erros, notifica o usuário
                if (!empty($erros)) {
                    Notification::make()
                        ->title('Alguns DANFEs não foram gerados')
                        ->body(implode("\n", $erros))
                        ->warning()
                        ->send();
                }
                
                // Retorna o arquivo ZIP para download
                return response()->download($zipFilePath, $zipFileName, [
                    'Content-Type' => 'application/zip',
                ])->deleteFileAfterSend();
            });
    }
} 