<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use App\Enums\Tenant\UserTypeEnum;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class RemoveClassificacaoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'remove-classificacao';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-trash')
            ->label('Remover Classificação')
            ->color('danger')
            ->hidden(fn ($record) => $record->tagged->isEmpty() || Auth::user()->role === UserTypeEnum::USER->value)
            ->action(function ($record) {
                $record->untag();

                Notification::make()
                    ->title('Classificação removida com sucesso!')
                    ->body("A nota fiscal {$record->numero} foi removida da classificação.")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Remover classificação da nota fiscal?')
            ->modalDescription('Tem certeza que deseja remover a classificação desta nota fiscal? Esta ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Sim, remover')
            ->modalCancelActionLabel('Cancelar');
    }
} 