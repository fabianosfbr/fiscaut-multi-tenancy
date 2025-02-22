<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PermissionTypeEnum: string implements HasColor, HasLabel
{
    case MANIFESTAR_CTE = 'manifestar-cte';
    case MANIFESTAR_NFE = 'manifestar-nfe';
    case CLASSIFICAR_NFE = 'classificar-nfe';
    case CLASSIFICAR_CTE = 'classificar-cte';
    case MARCAR_DOCUMENTO_APURADO = 'marcar-documento-apurado';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MANIFESTAR_NFE => 'Manifestar NFe',
            self::MANIFESTAR_CTE => 'Manifestar CTe',
            self::CLASSIFICAR_NFE => 'Classificar NFe',
            self::CLASSIFICAR_CTE => 'Classificar CTe',
            self::MARCAR_DOCUMENTO_APURADO => 'Marcar Documento como Apurado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MANIFESTAR_NFE => 'success',
            self::MANIFESTAR_CTE => 'success',
            self::CLASSIFICAR_NFE => 'success',
            self::CLASSIFICAR_CTE => 'success',
            self::MARCAR_DOCUMENTO_APURADO => 'success',
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
