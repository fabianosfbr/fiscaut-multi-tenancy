<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Actions\Action;
use NFePHP\DA\NFe\Danfe;
use Filament\Notifications\Notification;

class DownloadPdfPageAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'gerar-danfe';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-document-text')
            ->label('Download PDF')
            ->action(function ($record) {
                try {

                    $name = $record->chave_acesso;

                    if (empty($record->xml_content)) {
                        Notification::make()
                            ->title('XML não encontrado')
                            ->body('Não foi possível encontrar o XML desta nota fiscal.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $danfe = new Danfe($record->xml_content);
                    $danfe->creditsIntegratorFooter(env('APP_FOOTER_CREDITS_DANFE'), false);
                    $pdf = $danfe->render();

                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf;
                    }, $name . '.pdf');
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Erro ao gerar DANFE')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
