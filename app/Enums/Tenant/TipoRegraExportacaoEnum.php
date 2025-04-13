<?php

namespace App\Enums\Tenant;

use Filament\Support\Contracts\HasLabel;


enum TipoRegraExportacaoEnum: string implements HasLabel
{

    case DATA_DA_OPERACAO = 'data_da_operacao';
    case OPERACAO_DE_DEBITO = 'operacao_de_debito';
    case OPERACAO_DE_CREDITO = 'operacao_de_credito';
    case HISTORICO_CONTABIL = 'historico_contabil';
    case VALOR_DA_OPERACAO = 'valor_da_operacao';


    public function getLabel(): string
    {
        return match ($this) {
            self::DATA_DA_OPERACAO => 'Data da Operação',
            self::OPERACAO_DE_DEBITO => 'Operação de Débito',
            self::OPERACAO_DE_CREDITO => 'Operação de Crédito',
            self::HISTORICO_CONTABIL => 'Base para parâmetros',
            self::VALOR_DA_OPERACAO => 'Valor da Operação',
            default => 'Tipo de Regra não encontrado'
        };
    }
}
