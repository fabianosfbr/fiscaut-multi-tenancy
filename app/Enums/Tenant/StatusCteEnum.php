<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusCteEnum: int implements HasColor, HasLabel
{
    case ATIVA = 100;
    case CANCELADA = 101;
    case DENEGADA = 302;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ATIVA => 'Ativa',
            self::CANCELADA => 'Cancelada',
            self::DENEGADA => 'Denegada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ATIVA => 'success',
            self::CANCELADA => 'danger',
            self::DENEGADA => 'danger',
        };
    }
}
