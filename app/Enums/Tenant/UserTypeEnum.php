<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UserTypeEnum: string implements HasColor, HasIcon, HasLabel
{

    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case ACCOUNTING = 'accounting';
    case USER = 'user';


    public function getLabel(): ?string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::ADMIN => 'Administrador',
            self::ACCOUNTING => 'Contabilidade',
            self::USER => 'Usuário',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SUPER_ADMIN => 'success',
            self::ADMIN => 'success',
            self::ACCOUNTING => 'warning',
            self::USER => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'heroicon-o-shield-check',
            self::ADMIN => 'heroicon-o-shield-check',
            self::ACCOUNTING => 'heroicon-o-users',
            self::USER => 'heroicon-o-users',
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
