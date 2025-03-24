<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class DownloadXmlPageAction extends Action
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
            ->requiresConfirmation(false)
            ->visible(fn(Model $record): bool => !empty($record->xml_content))
            ->action(function ($record) {
                if (empty($record->xml_content)) {
                    return;
                }
                $filename = "{$record->chave_acesso}.xml";

                return response()->streamDownload(function () use ($record) {
                    echo $record->xml_content;
                }, $filename);
            });
    }
}
