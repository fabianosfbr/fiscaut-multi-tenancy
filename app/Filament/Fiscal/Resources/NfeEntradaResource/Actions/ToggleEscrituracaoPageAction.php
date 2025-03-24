<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ToggleEscrituracaoPageAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'toggle-escrituracao-';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn($record) => $record->escriturada_destinatario ? 'Não Escriturada' : 'Escriturada')
            ->icon('heroicon-o-document-check')
            ->action(function ($record) {
                $record->escriturada_destinatario = !$record->escriturada_destinatario;
                $record->save();

                $status = $record->escriturada_destinatario ? 'escriturada' : 'não escriturada';

                Notification::make()
                    ->title("Nota fiscal {$status} com sucesso!")
                    ->body("A nota fiscal {$record->numero} foi marcada como {$status}.")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading(fn($record) => $record->escriturada_destinatario ? 'Marcar nota como não escriturada?' : 'Marcar nota como escriturada?')
            ->modalDescription(fn($record) => $record->escriturada_destinatario
                ? 'Tem certeza que deseja marcar esta nota fiscal como não escriturada?'
                : 'Tem certeza que deseja marcar esta nota fiscal como escriturada?')
            ->modalSubmitActionLabel('Confirmar')
            ->modalCancelActionLabel('Cancelar');
    }
}
