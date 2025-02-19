<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusManifestoNfe: int implements HasColor, HasLabel
{
    case CIENTE = 210210;
    case CONFIRMADA = 210200;
    case DESCONHECIDA = 210220;
    case NAOREALIZADA = 210240;
    case DEFAULT = 0;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CIENTE => 'Ciente',
            self::CONFIRMADA => 'Confirmada',
            self::DESCONHECIDA => 'Desconhecida',
            self::NAOREALIZADA => 'Não realizada',
            self::DEFAULT => 'Ciente',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CIENTE => 'success',
            self::CONFIRMADA => 'success',
            self::DESCONHECIDA => 'danger',
            self::NAOREALIZADA => 'danger',
            self::DEFAULT => 'success',
        };
    }
}
