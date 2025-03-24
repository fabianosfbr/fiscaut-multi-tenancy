<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
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

        $this->label('Alternar Escrituração')
            ->icon('heroicon-o-document-check')
            ->requiresConfirmation()
            ->modalHeading('Alternar Escrituração')
            ->modalDescription('Deseja realmente alternar o status de escrituração desta nota fiscal?')
            ->modalSubmitActionLabel('Sim, alternar')
            ->modalCancelActionLabel('Não, cancelar')
            ->action(function (Model $record): void {
                $organization = getOrganizationCached();
                
                $isEscriturada = $record->toggleEscrituracao($organization);
                
                Notification::make()
                    ->title($isEscriturada ? 'Nota fiscal escriturada com sucesso!' : 'Nota fiscal desescriturada com sucesso!')
                    ->success()
                    ->send();
            });
    }
}
