<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Tables\Actions\Action;
use App\Models\Tenant\Organization;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class ToggleEscrituracaoTableAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'toggle_escrituracao';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Alternar Escrituração')
            ->icon('heroicon-o-document-check')
            ->action(function ($record) {
                $organization = getOrganizationCached();

                $isEscriturada = $record->toggleEscrituracao($organization);

                Notification::make()
                    ->title($isEscriturada ? 'Documento escriturado com sucesso!' : 'Documento desescriturado com sucesso!')
                    ->body($isEscriturada ? 'O documento foi marcado como escriturado.' : 'O documento foi foi marcado como não escriturado.')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading(fn($record) => $record->isEscrituradaParaOrganization(getOrganizationCached()) ? 'Marcar documento como não escriturado?' : 'Marcar documento como escriturado?')
            ->modalDescription(fn($record) => $record->isEscrituradaParaOrganization(getOrganizationCached())
                ? 'Tem certeza que deseja marcar este documento como não escriturado?'
                : 'Tem certeza que deseja marcar este documento como escriturado?')
            ->modalSubmitActionLabel('Confirmar')
            ->modalCancelActionLabel('Cancelar');
    }
}
