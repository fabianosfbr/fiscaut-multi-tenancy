<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrigemCteEnum: string implements HasColor, HasLabel
{
    case IMPORTADO = 'IMPORTADO';
    case SEFAZ = 'SEFAZ';
    case SIEG = 'SIEG';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::IMPORTADO => 'Importado',
            self::SEFAZ => 'Sefaz',
            self::SIEG => 'Sieg',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::IMPORTADO => 'info',
            self::SEFAZ => 'success',
            self::SIEG => 'warning',
        };
    }
} 