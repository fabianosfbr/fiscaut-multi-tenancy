<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DocTypeEnum: int implements HasColor, HasLabel
{
    case NFS_TOMADA = 1;
    case FATURA = 2;
    case BOLETO = 3;
    case NOTA_DEBITO = 4;
    case DOCUMENTOS_CONTABEIS = 5;
    case EXTRATOS_BANCARIOS = 6;
    case CONTRATOS = 7;
    case PLANILHAS_DE_CONTROLE = 8;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NFS_TOMADA => 'NFS Tomada',
            self::FATURA => 'Fatura',
            self::BOLETO => 'Boleto',
            self::NOTA_DEBITO => 'Nota Débito',
            self::DOCUMENTOS_CONTABEIS => 'Documentos contábeis',
            self::EXTRATOS_BANCARIOS => 'Extrato bancário',
            self::CONTRATOS => 'Contratos',
            self::PLANILHAS_DE_CONTROLE => 'Planilhas de controle',

        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NFS_TOMADA => 'gray',
            self::FATURA => 'warning',
            self::BOLETO => 'success',
            self::NOTA_DEBITO => 'gray',
            self::DOCUMENTOS_CONTABEIS => 'warning',
            self::EXTRATOS_BANCARIOS => 'success',
            self::CONTRATOS => 'gray',
            self::PLANILHAS_DE_CONTROLE => 'warning',
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
