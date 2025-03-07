<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusManifestoCteEnum: string implements HasColor, HasLabel
{
    case PENDENTE = 'PENDENTE';
    case CONFIRMADA = 'CONFIRMADA';
    case DESCONHECIDA = 'DESCONHECIDA';
    case NAO_REALIZADA = 'NAO_REALIZADA';
    case CIENCIA = 'CIENCIA';
    case OPERACAO_NAO_REALIZADA = 'OPERACAO_NAO_REALIZADA';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDENTE => 'Pendente',
            self::CONFIRMADA => 'Confirmada',
            self::DESCONHECIDA => 'Desconhecida',
            self::NAO_REALIZADA => 'Não Realizada',
            self::CIENCIA => 'Ciência da Operação',
            self::OPERACAO_NAO_REALIZADA => 'Operação não Realizada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDENTE => 'warning',
            self::CONFIRMADA => 'success',
            self::DESCONHECIDA => 'danger',
            self::NAO_REALIZADA => 'danger',
            self::CIENCIA => 'info',
            self::OPERACAO_NAO_REALIZADA => 'danger',
        };
    }
} 