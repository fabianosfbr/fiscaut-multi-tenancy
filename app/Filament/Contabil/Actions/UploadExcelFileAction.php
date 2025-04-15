<?php

namespace App\Filament\Contabil\Actions;

use Exception;
use App\Models\Tenant\Layout;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use App\Models\Tenant\ImportarLancamentoContabil;
use App\Imports\Contabil\UploadFileExcelImport;
use App\Filament\Contabil\Actions\Traits\ImportarLancamentoContabilTrait;
use Illuminate\Support\Facades\Auth;

class UploadExcelFileAction extends Action
{

    use ImportarLancamentoContabilTrait;

    public static function getDefaultName(): ?string
    {
        return 'upload-excel-file';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Importar Arquivo');
        $this->modalHeading('Importar Arquivo Excel');
        $this->modalSubmitActionLabel('Sim, importar arquivo');
        $this->before(function () {
            ImportarLancamentoContabil::where('organization_id', getOrganizationCached()->id)
                ->where('user_id', Auth::user()->id)
                ->delete();
        });
        $this->form([
            Select::make('layout_id')
                ->label('Layout utilizado para importação')
                ->required()
                ->default(1)
                ->options(function () {
                    return Layout::where('organization_id', getOrganizationCached()->id)->pluck('name', 'id');
                }),

            FileUpload::make('excel_file')
                ->label('Arquivo Excel')
                // ->required()
                ->directory('upload-importacao')
                ->validationMessages([
                    'required' => 'O arquivo Excel é obrigatório.',
                    'file.mimes' => 'O arquivo deve ser um arquivo Excel válido.',
                    'file.max' => 'O arquivo deve ter no máximo 10MB.',
                ])
                ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']),
        ]);

        $this->action(function ($data, $action): void {

            $layout = Layout::find($data['layout_id']);

            $data['excel_file'] = 'upload-importacao/01JRTM4XD5J259YQ8CC3N7J7ER.xlsx';
            //$data['excel_file'] = 'upload-importacao/01JPQN9WDQ5CQ30VY45CF007HH.xlsx';

            $file = Storage::disk('public')->path($data['excel_file']);

            try {

                $dadosExcel = Excel::toArray(null, $file);  // Usa null como primeiro argumento

                $missingColumns = self::validateExcelColumns($dadosExcel, $layout);

                if (!empty($missingColumns)) {
                    Notification::make()
                        ->title('Colunas Ausentes')
                        ->body('As seguintes colunas estão faltando no arquivo Excel: ' . implode(', ', $missingColumns))
                        ->danger()
                        ->persistent()
                        ->send();

                    Storage::delete($file); // Remove o arquivo temporário
                    $action->halt();
                }

                // Recarrega o layout para obter o metadata atualizado com a linha do cabeçalho
                $layout = Layout::find($layout->id);

                $import = new UploadFileExcelImport($layout);
                Excel::import($import, $file);

                $data = $import->getData();


                self::prepareData($data, $layout);
            } catch (Exception $e) {
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
                Notification::make()
                    ->title('Erro na Importação')
                    ->body('Ocorreu um erro ao importar o arquivo Excel: ' . $e->getMessage())
                    ->danger()
                    ->send();
            } finally {
                Storage::delete($file); // Remove o arquivo temporário
            }
        });
    }
}
