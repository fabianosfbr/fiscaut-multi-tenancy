<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasLabel;

enum RegimesEmpresariaisEnum: string implements HasLabel
{
    case SIMPLES_NACIONAL = 'simples_nacional';
    case LUCRO_REAL = 'lucro_real';
    case LUCRO_PRESUMIDO = 'lucro_presumido';
    case IMUNE = 'imune';
    case ISENTA = 'isenta';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SIMPLES_NACIONAL => 'Simples Nacional',
            self::LUCRO_REAL => 'Lucro Real',
            self::LUCRO_PRESUMIDO => 'Lucro Presumido',
            self::IMUNE => 'Imune',
            self::ISENTA => 'Isenta',
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
