<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DownloadXmlBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'download_xml_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download XMLs')
            ->icon('heroicon-o-document-arrow-down')
            ->requiresConfirmation()
            ->modalHeading('Download XMLs selecionados')
            ->modalDescription('Deseja baixar os XMLs das notas selecionadas?')
            ->modalSubmitActionLabel('Download XMLs')
            ->successNotificationTitle('XMLs baixados com sucesso')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                // Filtra apenas os registros que têm conteúdo XML
                $recordsWithXml = $records->filter(fn ($record) => !empty($record->xml_content));
                
                if ($recordsWithXml->isEmpty()) {
                    $this->failure();
                    
                    return $this->failureNotificationTitle('Nenhum XML disponível para download');
                }
                
                // Cria um arquivo ZIP temporário
                $zipFileName = 'xmls_' . now()->format('YmdHis') . '.zip';
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
                
                // Adiciona cada XML ao arquivo ZIP
                foreach ($recordsWithXml as $record) {
                    $filename = "{$record->chave_acesso}.xml";
                    $zip->addFromString($filename, $record->xml_content);
                }
                
                $zip->close();
                
                // Retorna o arquivo ZIP para download
                return response()->download($zipFilePath, $zipFileName, [
                    'Content-Type' => 'application/zip',
                ])->deleteFileAfterSend();
            });
    }
} 