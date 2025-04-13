<?php

namespace App\Filament\Contabil\Actions\Traits;

use Exception;
use App\Models\Tenant\Layout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant\HistoricoContabil;
use App\Enums\Tenant\TipoRegraExportacaoEnum;
use App\Models\Tenant\ImportarLancamentoContabil;
use App\Models\Tenant\ParametrosConciliacaoBancaria;

trait ImportarLancamentoContabilTrait
{

    private static function validateExcelColumns(array $dadosExcel, Layout $layout): array
    {
        // Busca o cabeçalho em todas as linhas do Excel até encontrar
        $headerExcel = null;
        $headerRow = 0;

        // Procura nas primeiras 10 linhas do arquivo (ajustável conforme necessidade)
        for ($i = 0; $i < min(20, count($dadosExcel[0])); $i++) {
            $potentialHeader = $dadosExcel[0][$i] ?? [];

            // Verifica se esta linha contém elementos que correspondem às colunas do layout
            $normalizedHeader = array_map(function ($columnName) {
                return strtolower(trim($columnName));
            }, $potentialHeader);

            // Se pelo menos 60% das colunas esperadas estiverem presentes, consideramos como cabeçalho
            $matchCount = 0;
            foreach ($layout->layoutColumns as $column) {
                $normalizedColumnName = strtolower(trim($column->excel_column_name));
                if (in_array($normalizedColumnName, $normalizedHeader)) {
                    $matchCount++;
                }
            }


            // Se encontrou correspondências suficientes, considera como cabeçalho
            if ($matchCount > 0 && $matchCount >= count($layout->layoutColumns) * 1) {
                info($headerRow);
                $headerExcel = $potentialHeader;
                $headerRow = $i + 1;
                break;
            }
        }


        // Se não encontrou um cabeçalho, usa a primeira linha como fallback
        if ($headerExcel === null) {
            $headerExcel = $dadosExcel[0][0] ?? [];
            $headerRow = 1;
        }

        $missingColumns = [];

        // Normaliza o cabeçalho do Excel: remove espaços e converte para minúsculas
        $normalizedHeaderExcel = array_map(function ($columnName) {
            return strtolower(trim($columnName));
        }, $headerExcel);

        foreach ($layout->layoutColumns as $column) {
            // Normaliza o nome da coluna do layout: remove espaços e converte para minúsculas
            $normalizedColumnName = strtolower(trim($column->excel_column_name));

            if (!in_array($normalizedColumnName, $normalizedHeaderExcel)) {
                $missingColumns[] = $column->excel_column_name; // Mantém o nome original para exibir ao usuário
            }
        }


        // Armazena o número da linha do cabeçalho para uso posterior na importação
        Layout::where('id', $layout->id)->update([
            'metadata' => [
                'header_row' => $headerRow
            ]
        ]);

        return $missingColumns;
    }

    private static function prepareData(array $data, Layout $layout): Collection
    {
        $preparedData = new Collection();

        $user_id = Auth::user()->id;
        $organization_id = $layout->organization_id;

        // Ordena as regras pela posição
        $rules = $layout->layoutRules()->orderBy('position')->get();

        foreach ($data as $index => $row) {

            $rowLine = [];
            $rowLine['operacao_de_debito'] = null;
            $rowLine['operacao_de_credito'] = null;
            $rowLine['valor_da_operacao'] = null;


            foreach ($rules as $rule) {

                $value = self::resolveRuleValue($rule, $row, $layout);

                if ($rule->rule_type === TipoRegraExportacaoEnum::DATA_DA_OPERACAO) {
                    $rowLine['data_da_operacao'] = is_null($value) ? $rowLine['data_da_operacao'] : $value;
                } elseif ($rule->rule_type === TipoRegraExportacaoEnum::OPERACAO_DE_DEBITO) {
                    $rowLine['operacao_de_debito'] = is_null($value) ? $rowLine['operacao_de_debito'] : $value;
                } elseif ($rule->rule_type === TipoRegraExportacaoEnum::OPERACAO_DE_CREDITO) {
                    $rowLine['operacao_de_credito'] = is_null($value) ? $rowLine['operacao_de_credito'] : $value;
                } elseif ($rule->rule_type === TipoRegraExportacaoEnum::VALOR_DA_OPERACAO) {
                    if ($rowLine['valor_da_operacao'] === null && $value !== null) {
                        $rowLine['valor_da_operacao'] = $value;
                    }
                }
                $rowLine['texto_historico_contabil'] = implode(' ', self::resolveHistoricoContabilValue($rule, $row, $layout));
            }


            if (!is_null($rowLine['valor_da_operacao'])) {

                $data = [
                    'organization_id' => $organization_id,
                    'user_id' => $user_id,
                    'data' => $rowLine['data_da_operacao'],
                    'valor' => $rowLine['valor_da_operacao'],
                    'debito' => isset($rowLine['operacao_de_debito']['conta_contabil_code']) ? $rowLine['operacao_de_debito']['conta_contabil_code'] : $rowLine['operacao_de_debito']['conta_contabil'] ?? null,
                    'credito' => isset($rowLine['operacao_de_credito']['conta_contabil_code']) ? $rowLine['operacao_de_credito']['conta_contabil_code'] :  $rowLine['operacao_de_credito']['conta_contabil'] ?? null,
                    'is_exist' => !is_null($rowLine['data_da_operacao'] ?? null) &&  !is_null($rowLine['operacao_de_debito']['conta_contabil'] ?? null) && !is_null($rowLine['operacao_de_credito']['conta_contabil'] ?? null),
                    'metadata' => [
                        'descricao_debito' => self::getDescricaoDebito($rowLine),
                        'descricao_credito' => self::getDescricaoCredito($rowLine),
                        'cod_historico' => self::getCodigoHistorico($rowLine),
                        'historico' => self::getHistorico(self::getCodigoHistorico($rowLine), $organization_id),
                        'texto_historico_contabil' => $rowLine['texto_historico_contabil'],
                        'row' => $row,
                    ]
                ];


                $import = new ImportarLancamentoContabil();
                $import->organization_id = $organization_id;
                $import->user_id = $user_id;
                $import->data = $rowLine['data_da_operacao'];
                $import->valor = $data['valor'];
                $import->debito = $data['debito'];
                $import->credito = $data['credito'];
                $import->is_exist = $data['is_exist'];
                $import->metadata = $data['metadata'];
                $import->historico = self::substituirCaracteresHistoricoContabil($import);


                $import->saveQuietly();

                $preparedData->push($rowLine);
            }


            // if ($index === 3) {
            //     break;
            // }

        }
        return $preparedData;
    }

    private static function resolveHistoricoContabilValue($rule, $row, $layout)
    {
        // Obtém todas as colunas do layout
        $targetColumns = $layout->layoutColumns->pluck('target_column_name')->toArray();

        // Cria array com os valores das colunas
        $searchValues = $targetColumns
            ? array_combine($targetColumns, array_map(fn($col) => $row[$col] ?? null, $targetColumns))
            : [];

        // Remove valores nulos e converte para string maiúscula para comparação
        $searchValues = array_filter($searchValues, fn($value) => !is_null($value));
        $searchValues = array_map(fn($value) => mb_strtoupper((string)$value, 'UTF-8'), $searchValues);


        return $searchValues;
    }

    private static function resolveRuleValue($rule, $row, $layout)
    {

        $value = match ($rule->data_source_type->value) {
            'column' => self::processColumnSource($rule, $row, $layout),
            'constant' => $rule->data_source_constant,
            'parametros_gerais' => self::processParametrosGerais($rule, $row, $layout),
            'query' => self::processQuerySource($rule, $row),
            default => $rule->default_value ?? null
        };

        $result = self::applyCondition($rule, $row, $layout, $value);

        return $result;
    }

    private static function processColumnSource($rule, $row, $layout)
    {

        $column = $layout->layoutColumns()->where('target_column_name', $rule->data_source)->first();

        $value = $row[$rule->data_source] ?? null;

        if (!isset($value) || !$column) {
            return null;
        }

        return match ($column->data_type) {
            'number' => self::formatNumberValue($value, $column),
            'date' => self::formatDateValue($value, $layout, $rule),
            default => $value
        };
    }

    private static function formatNumberValue($value, $column)
    {

        if ($value < 0) $value = $value * -1;

        return (float)$value;
    }

    private static function formatDateValue($value, $layout, $rule)
    {

        try {
            $layoutColumn = $layout->layoutColumns->where('data_type', 'date')->first();


            $date = match (gettype($value)) {
                'integer' => Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(trim($value)))
                    ->format($layoutColumn->format),
                'string' => Carbon::createFromFormat('d/m/y', $value) ?? Carbon::createFromFormat('d/m/Y', $value),
                default => $value
            };


            if (!$date) {
                return Carbon::now();
            }

            $date = self::adjustDateBasedRule($layoutColumn, $date);

            return $date;
        } catch (Exception $e) {
            Log::error("Erro ao formatar a data: " . $e->getMessage());
            return null;
        }
    }

    private static function processParametrosGerais($rule, $row, $layout)
    {
        // Obtém todas as colunas do layout
        $targetColumns = $layout->layoutColumns->pluck('target_column_name')->toArray();

        // Cria array com os valores das colunas
        $searchValues = $targetColumns
            ? array_combine($targetColumns, array_map(fn($col) => $row[$col] ?? null, $targetColumns))
            : [];

        // Remove valores nulos e converte para string maiúscula para comparação
        $searchValues = array_filter($searchValues, fn($value) => !is_null($value));
        $searchValues = array_map(fn($value) => mb_strtoupper((string)$value, 'UTF-8'), $searchValues);

        // Busca todos os parâmetros cadastrados
        $params = ParametrosConciliacaoBancaria::getCachedByOrganization($layout->organization_id);


        $parametroEncontrado = null;
        foreach ($params as $index => $parametro) {
            // Converte os termos do parâmetro para maiúsculo
            $termos = array_map(fn($termo) => mb_strtoupper($termo, 'UTF-8'), $parametro->params);

            // Verifica se os termos estão presentes nos valores de busca
            $termosEncontrados = self::verificarTermos($termos, $searchValues, $parametro->is_inclusivo);

            if ($termosEncontrados) {
                $value = $parametro->toArray();
                $value['conta_contabil'] = $value['descricao_conta_contabil']['codigo'];
                $parametroEncontrado = $value;
            }
        }

        return $parametroEncontrado;
    }

    /**
     * Verifica se os termos estão presentes nos valores de busca
     *
     * @param array $termos Array de termos a serem buscados
     * @param array $searchValues Array de valores onde buscar
     * @param bool $isInclusivo Se true, todos os termos devem estar presentes
     * @return bool
     */
    private static function verificarTermos(array $termos, array $searchValues, bool $isInclusivo): bool
    {
        if ($isInclusivo) {
            // Modo inclusivo: todos os termos devem estar presentes
            foreach ($termos as $termo) {
                $termoEncontrado = false;
                foreach ($searchValues as $valor) {
                    if (str_contains($valor, $termo)) {
                        $termoEncontrado = true;
                        break;
                    }
                }
                // Se qualquer termo não for encontrado, retorna false
                if (!$termoEncontrado) {
                    return false;
                }
            }
            return true;
        } else {
            // Modo OU: pelo menos um termo deve estar presente
            foreach ($termos as $termo) {
                foreach ($searchValues as $valor) {
                    if (str_contains($valor, $termo)) {
                        return true;
                    }
                }
            }
            return false;
        }
    }

    private static function processQuerySource($rule, $row)
    {

        try {
            $searchValue = match ($rule->data_source_value_type) {
                'constant' => $rule->data_source_search_constant,
                'column' => $row[$rule->data_source_search_value] ?? '',
                default => ''
            };


            $query = sprintf(
                'SELECT * FROM %s WHERE %s %s ? AND organization_id = \'' . $rule->layout->organization_id . '\' LIMIT 1',
                $rule->data_source_table,
                $rule->data_source_attribute,
                $rule->data_source_condition
            );


            $searchValue = $rule->data_source_condition === 'like' ? "%$searchValue%" : $searchValue;

            if (is_string($searchValue) && trim($searchValue) !== "") {

                $result = DB::select($query, [$searchValue]);

                $data = isset($result[0]) ? (array)$result[0] : [];
                $data['codigo_historico'] = $rule->data_source_historico ?? null;
            }

            return $data ?? null;
        } catch (Exception $e) {
            Log::error("Erro ao executar a query: " . $e->getMessage());
            return $rule->default_value ?? null;
        }
    }

    private static function applyCondition($rule, $row, $layout, $value)
    {

        if ($rule->condition_type === 'none') {
            return $value;
        }

        if ($rule->condition_type === 'if') {

            $conditionValue = self::getConditionValue($rule, $row, $layout);

            $conditionResult = self::evaluateCondition($rule, $conditionValue);

            return $conditionResult ? $value : ($rule->default_value ?? null);
        }

        return $value;
    }

    private static function getConditionValue($rule, $row, $layout)
    {
        return match ($rule->condition_data_source_type) {
            'column' => $row[$rule->condition_data_source] ?? null,
            'constant' => $rule->condition_data_source,
            'query' => self::executeConditionQuery($rule, $row, $layout),
            default => null
        };
    }

    private static function executeConditionQuery($rule, $row, $layout)
    {
        try {
            $conditionResult = DB::select($rule->condition_data_source, ['row' => $row, 'layout' => $layout]);
            return $conditionResult[0]->value ?? '';
        } catch (Exception $e) {
            Log::error("Erro ao executar a query da condição: " . $e->getMessage());
            return null;
        }
    }

    private static function evaluateCondition($rule, $conditionValue)
    {
        $conditionOperators = [
            '=' => fn($a, $b) => $a == $b,
            '!=' => fn($a, $b) => $a != $b,
            '>' => fn($a, $b) => $a > $b,
            '<' => fn($a, $b) => $a < $b,
            '>=' => fn($a, $b) => $a >= $b,
            '<=' => fn($a, $b) => $a <= $b,
            'contains' => fn($a, $b) => str_contains($a, $b),
            'not_contains' => fn($a, $b) => !str_contains($a, $b),
            'empty' => fn($a) => empty($a),
            'not_empty' => fn($a) => !empty($a)
        ];

        $operator = $rule->condition_operator;
        $ruleValue = $rule->condition_value;

        if (in_array($operator, ['empty', 'not_empty'])) {
            return $conditionOperators[$operator]($conditionValue);
        }

        return $conditionOperators[$operator]($conditionValue, $ruleValue);
    }





    private static function getDescricaoDebito($row)
    {
        if (isset($row['operacao_de_debito']['nome'])) {
            return $row['operacao_de_debito']['nome'];
        }
        if (isset($row['operacao_de_debito']['descricao_conta_contabil']['descricao'])) {
            return $row['operacao_de_debito']['descricao_conta_contabil']['descricao'];
        }

        return null;
    }

    private static function getDescricaoCredito($row)
    {
        if (isset($row['operacao_de_credito']['nome'])) {
            return $row['operacao_de_credito']['nome'];
        }
        if (isset($row['operacao_de_credito']['descricao_conta_contabil']['descricao'])) {
            return $row['operacao_de_credito']['descricao_conta_contabil']['descricao'];
        }

        return null;
    }

    private static function getCodigoHistorico($row)
    {
        if (isset($row['operacao_de_credito']['codigo_historico'])) {
            return $row['operacao_de_credito']['codigo_historico'];
        }
        if (isset($row['operacao_de_debito']['codigo_historico'])) {
            return $row['operacao_de_debito']['codigo_historico'];
        }

        return null;
    }

    private static function getHistorico($codigo, $organization_id)
    {
        return HistoricoContabil::where('organization_id', $organization_id)
            ->where('codigo', $codigo)
            ->first()?->descricao;
    }

    private static function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();

        while (!$nextDay->isWeekday()) {
            $nextDay->addDay();
        }

        return $nextDay;
    }

    /**
     * Obtém o dia útil anterior
     */
    private static function getPreviousWorkingDay(Carbon $date): Carbon
    {
        $previousDay = $date->copy()->subDay();

        while (!$previousDay->isWeekday()) {
            $previousDay->subDay();
        }

        return $previousDay;
    }

    /**
     * Ajusta a data baseado no banco encontrado nas operações
     */
    private static function adjustDateBasedRule($layoutColumn, $date): Carbon
    {

        if ($layoutColumn->date_adjustment == 'same') {
            return $date;
        }

        // Converte a data para objeto Carbon
        try {

            // Aplica o ajuste conforme configuração do banco
            $adjustedDate = match ($layoutColumn->date_adjustment) {
                'd-1' => self::getPreviousWorkingDay($date),
                'd+1' => self::getNextWorkingDay($date),
                default => $date // 'same'
            };

            // Atualiza a data no rowLine mantendo o mesmo formato
            $date = $adjustedDate;
        } catch (Exception $e) {
            Log::error("Erro ao ajustar data baseado no banco: " . $e->getMessage());
        }

        return $date;
    }

    private static function substituirCaracteresHistoricoContabil($lancamento)
    {
        $texto = $lancamento->metadata['historico'];

        // Encontra todos os marcadores no formato #ALGO no texto
        preg_match_all('/#[^\s#]+/u', $texto, $matches);


        // Para cada marcador encontrado
        foreach ($matches[0] as $index => $marcador) {

            $codigo = substr($marcador, 1);  // Pega o código sem o #
            $valor = '';

            switch ($codigo) {

                case 'M':
                    $valor = $lancamento->data->format('d/m/Y');
                    break;

                case 'N':
                    $valor = $lancamento->data->format('m/Y');
                    break;

                case 'D':
                    $valor = $lancamento->metadata['descricao_debito'] ?? '';
                    break;

                case 'C':
                    $valor = $lancamento->metadata['descricao_credito'] ?? '';
                    break;

                case 'V':
                    $valor = number_format(abs($lancamento->valor), 2, ',', '');
                    break;

                case 'A':
                    $valor = $lancamento->data->subMonth()->format('m/Y');
                    break;

                default:
                    // Verifica se é uma chave do array row
                    if (isset($lancamento->metadata['row'][$codigo])) {
                        $valor = $lancamento->metadata['row'][$codigo];
                    }
                    break;
            }
            // Substitui o marcador pelo valor no texto original
            $texto = str_replace($marcador, $valor, $texto);
        }

        // Retorna o texto com os marcadores substituídos
        return $texto;
    }
}
