<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PricePlanTypEnum:int implements HasColor, HasIcon, HasLabel
{


    case MONTHLY = 0;
    case YEARLY = 1;
    case LIFETIME = 2;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::MONTHLY => 'Mensal',
            self::YEARLY => 'Anual',
            self::LIFETIME => 'Vitalício',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::MONTHLY => 'success',
            self::YEARLY => 'success',
            self::LIFETIME => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::MONTHLY => 'heroicon-o-shield-check',
            self::YEARLY => 'heroicon-o-users',
            self::LIFETIME => 'heroicon-o-users',
        };
    }

}
