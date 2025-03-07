<?php

namespace App\Services\Tenant\Xml;

use ZipArchive;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class XmlExtractorService
{
    /**
     * Processa o arquivo enviado e retorna uma coleção de conteúdos XML
     */
    public function extract(UploadedFile $file): Collection
    {
        $xmlContents = collect();

        if ($file->getClientOriginalExtension() === 'zip') {
            $xmlContents = $this->extractFromZip($file);
        } elseif ($file->getClientOriginalExtension() === 'xml') {
            $xmlContents->push([
                'content' => $file->get(),
                'filename' => $file->getClientOriginalName()
            ]);
        } else {
            throw new Exception("Formato de arquivo não suportado. Use XML ou ZIP.");
        }

        return $xmlContents;
    }

    /**
     * Extrai os arquivos XML de um arquivo ZIP
     */
    private function extractFromZip(UploadedFile $zipFile): Collection
    {
        $xmlContents = collect();
        $zip = new ZipArchive();
        $tempPath = Storage::disk('local')->path('temp_' . uniqid() . '.zip');

        try {
            // Move o arquivo para um local temporário
            file_put_contents($tempPath, $zipFile->get());

            if ($zip->open($tempPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    
                    // Verifica se é um arquivo XML
                    if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'xml') {
                        $xmlContents->push([
                            'content' => $zip->getFromIndex($i),
                            'filename' => $filename
                        ]);
                    }
                }
                $zip->close();
            } else {
                throw new Exception("Não foi possível abrir o arquivo ZIP.");
            }

            // Remove o arquivo temporário
            unlink($tempPath);

            if ($xmlContents->isEmpty()) {
                throw new Exception("Nenhum arquivo XML encontrado no ZIP.");
            }

            return $xmlContents;

        } catch (Exception $e) {
            // Garante que o arquivo temporário seja removido em caso de erro
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            throw $e;
        }
    }
} 