<?php

namespace App\Imports\Contabil;

use App\Models\Tenant\Layout;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UploadFileExcelImport implements ToCollection, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $layout;
    private $rows = 0;
    private $data = [];
    private $headingRow = 1;

    public function __construct(Layout $layout)
    {
        $this->layout = $layout;
        $this->headingRow = $layout->metadata['header_row'] ?? 1;
    }

    /**
     * @return int
     */
    public function headingRow(): int
    {
        return $this->headingRow;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $rowData = [];  // Array para armazenar os dados de cada linha

            // Itera sobre as colunas definidas no layout
            foreach ($this->layout->layoutColumns as $column) {
                $excelColumnName = Str::slug(trim(strtolower(trim($column->excel_column_name))), '_');

                // Verifica se a coluna existe no cabeçalho do Excel
                if (isset($row[$excelColumnName])) {
                    $value = $row[$excelColumnName];

                    // Aplica formatação, se necessário
                    if ($column->data_type === 'number') {
                        $value = (float) $value; // Converte para float
                    } elseif ($column->data_type === 'date' && $column->format) {


                        try {
                            if (gettype($value) === 'integer') {
                                $value =  Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject(trim($value)))->format($column->format); // Converter para formato de data
                            } elseif (gettype($value) === 'string') {
                                $value = Carbon::createFromFormat('d/m/Y', $value);;
                            } else {
                                $value = null;
                            }
                        } catch (\Exception $e) {
                            // Lidar com erros de formatação de data
                            Log::error("Erro ao formatar data: " . $e->getMessage());
                            $value = null; // Define como nulo ou outro valor padrão
                        }
                    }

                    if ($column->is_sanitize) {
                        $value = sanitize($value);
                    }

                    $rowData[$column->target_column_name] = $value;
                } else {
                    // A coluna do Excel não foi encontrada
                    Log::warning("Coluna '{$column->excel_column_name}' não encontrada no arquivo Excel.");
                    $rowData[$column->target_column_name] = null; // Define como nulo ou outro valor padrão
                }
            }

            $this->data[] = $rowData; // Adiciona os dados da linha ao array principal
            $this->rows++;
        }
    }


    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
