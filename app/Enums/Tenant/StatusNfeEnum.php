<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusNfeEnum: string implements HasColor, HasLabel
{
    case EMITIDA = 'EMITIDA';
    case AUTORIZADA = 'AUTORIZADA';
    case CANCELADA = 'CANCELADA';
    case DENEGADA = 'DENEGADA';
    case INUTILIZADA = 'INUTILIZADA';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EMITIDA => 'Emitida',
            self::AUTORIZADA => 'Autorizada',
            self::CANCELADA => 'Cancelada',
            self::DENEGADA => 'Denegada',
            self::INUTILIZADA => 'Inutilizada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EMITIDA => 'success',
            self::AUTORIZADA => 'success',
            self::CANCELADA => 'danger',
            self::DENEGADA => 'danger',
            self::INUTILIZADA => 'warning',
        };
    }
}
