<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use App\Jobs\Downloads\DownloadAvancadoNfeJob;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class DownloadAvancadoBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'download_avancado_bulk';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download Avançado')
            ->icon('heroicon-o-cog-6-tooth')
            ->requiresConfirmation()
            ->modalHeading('Download Avançado')
            ->modalDescription('Selecione as opções desejadas para o download.')
            ->modalSubmitActionLabel('Iniciar Download')
            ->successNotificationTitle('Download iniciado com sucesso')
            ->deselectRecordsAfterCompletion()
            ->modalWidth('lg')
            ->form([
                Checkbox::make('aplicar_estrutura_etiqueta')
                    ->label('Aplicar estrutura de etiqueta')
                    ->helperText('Organiza os arquivos conforme a estrutura de etiquetas das notas')
                    ->default(false),

                Checkbox::make('gerar_arquivo_importacao')
                    ->label('Gerar arquivo de importação')
                    ->helperText('Gera um arquivo CSV com os dados das notas para importação')
                    ->default(false),

                Checkbox::make('adicionar_etiquetas_pdf')
                    ->label('Adicionar etiquetas no final do PDF')
                    ->helperText('Adiciona as etiquetas das notas no final de cada PDF')
                    ->default(true),
            ])
            ->action(function (Collection $records, array $data) {
                // Dispara o job para processar o download em background

                DownloadAvancadoNfeJob::dispatch($records, $data, Auth::id(), tenant()->id);


                Notification::make()
                    ->title('Download iniciado')
                    ->body('O download das notas foi iniciado e será processado em segundo plano.')
                    ->success()
                    ->send();
            });
    }
}
