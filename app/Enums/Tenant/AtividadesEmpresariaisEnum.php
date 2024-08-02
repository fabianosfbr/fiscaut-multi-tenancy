<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasLabel;

enum AtividadesEmpresariaisEnum: string implements HasLabel
{
    case INDUSTRIA = 'industria';
    case COMERCIO = 'comercio';
    case SERVICO = 'servico';
    case ATACADISTA = 'atacadista';

    public function getLabel(): string
    {
        return match ($this) {
            self::INDUSTRIA => 'Indústria',
            self::COMERCIO => 'Comércio',
            self::SERVICO => 'Serviço',
            self::ATACADISTA => 'Atacadista',
            default => 'Atividade não encontrada'
        };
    }

    public static function toArray()
    {
        $statuses = [];

        foreach (self::cases() as $status) {
            $statuses[$status->value] = $status->getLabel();
        }

        return $statuses;
    }
}
