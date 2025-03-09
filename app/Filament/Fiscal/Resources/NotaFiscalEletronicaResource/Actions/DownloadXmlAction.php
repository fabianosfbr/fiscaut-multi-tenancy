<?php

namespace App\Filament\Fiscal\Resources\NotaFiscalEletronicaResource\Actions;

use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class DownloadXmlAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download_xml';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download XML')
            ->icon('heroicon-o-document-arrow-down')
            ->color('success')
            ->requiresConfirmation(false)
            ->visible(fn(Model $record): bool => !empty($record->xml_content))
            ->action(function ($record) {
                if (empty($record->xml_content)) {
                    return;
                }
                $filename = "NFe_{$record->chave_acesso}.xml";

                return response()->streamDownload(function () use ($record) {
                    echo $record->xml_content;
                }, $filename);
            });
    }
}
