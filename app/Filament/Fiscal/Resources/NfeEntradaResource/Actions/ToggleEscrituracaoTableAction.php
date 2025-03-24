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
                    ->title($isEscriturada ? 'Nota fiscal escriturada com sucesso!' : 'Nota fiscal desescriturada com sucesso!')
                    ->body($isEscriturada ? 'A nota fiscal foi marcada como escriturada.' : 'A nota fiscal foi marcada como não escriturada.')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading(fn($record) => $record->isEscrituradaParaOrganization(getOrganizationCached()) ? 'Marcar nota como não escriturada?' : 'Marcar nota como escriturada?')
            ->modalDescription(fn($record) => $record->isEscrituradaParaOrganization(getOrganizationCached())
                ? 'Tem certeza que deseja marcar esta nota fiscal como não escriturada?'
                : 'Tem certeza que deseja marcar esta nota fiscal como escriturada?')
            ->modalSubmitActionLabel('Confirmar')
            ->modalCancelActionLabel('Cancelar');
    }
}
