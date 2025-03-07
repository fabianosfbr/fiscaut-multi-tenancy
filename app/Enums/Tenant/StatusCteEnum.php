<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusCteEnum: string implements HasColor, HasLabel
{
    case EMITIDO = 'EMITIDO';
    case AUTORIZADO = 'AUTORIZADO';
    case CANCELADO = 'CANCELADO';
    case DENEGADO = 'DENEGADO';
    case INUTILIZADO = 'INUTILIZADO';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EMITIDO => 'Emitido',
            self::AUTORIZADO => 'Autorizado',
            self::CANCELADO => 'Cancelado',
            self::DENEGADO => 'Denegado',
            self::INUTILIZADO => 'Inutilizado',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EMITIDO => 'warning',
            self::AUTORIZADO => 'success',
            self::CANCELADO => 'danger',
            self::DENEGADO => 'danger',
            self::INUTILIZADO => 'gray',
        };
    }
}
