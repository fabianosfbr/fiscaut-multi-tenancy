<?php

namespace App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\Pages;

use DateTime;
use Exception;
use ReflectionClass;
use App\Models\Banco;
use Filament\Actions;

use ReflectionMethod;
use App\Models\Tenant\Layout;
use Illuminate\Support\Str;
use App\Models\Tenant\PlanoDeConta;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use App\Models\Tenant\HistoricoContabil;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use App\Enums\Tenant\TipoParametroContabil;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Radio;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Tenant\LayoutArquivoConcilicacao;
use Filament\Forms\Components\FileUpload;

use Filament\Resources\Pages\ListRecords;
use App\Imports\Contabil\LancamentoImport;
use App\Models\Tenant\ImportarLancamentoContabil;
use Filament\Forms\Components\Placeholder;
use App\Filament\Actions\UploadExcelAction;
use App\Forms\Components\SelectPlanoDeConta;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Tenant\ParametrosConciliacaoBancaria;
use Konnco\FilamentImport\Actions\ImportField;
use App\Imports\Contabil\UploadFileExcelImport;
use Konnco\FilamentImport\Actions\ImportAction;
use App\Imports\Contabil\LancamentoImportService;
use App\Filament\Contabil\Actions\UploadExcelFileAction;
use App\Filament\Contabil\Actions\ReprocessarExcelFileAction;
use App\Filament\Contabil\Resources\ImportarLancamentoContabilResource;
use App\Filament\Contabil\Resources\ImportarLancamentoContabilResource\Widgets\ImportLancamentoOverview;
use Illuminate\Support\Facades\Auth;


class ListImportarLancamentoContabeis extends ListRecords
{
    protected static string $resource = ImportarLancamentoContabilResource::class;


    protected function getHeaderWidgets(): array
    {
        return [
            ImportLancamentoOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        $formData = [
            'data' => 'Data',
        ];
        return [

            ActionGroup::make([


                UploadExcelFileAction::make(),


                Action::make('gerar-lancamentos')
                    ->label('Gerar Lançamentos')
                    ->modalWidth('lg')
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->form([
                        Checkbox::make('is_exist')
                            ->label('Gerar somente lançamentos com vinculo')
                            ->live()
                            ->default(true),

                        Checkbox::make('limpar_lancamentos')
                            ->label('Limpar lançamentos após geração do arquivo')
                            ->default(false),

                        Fieldset::make('Registros sem lançamento')
                            ->visible(fn(callable $get) => $get('is_exist') === false)
                            ->schema([
                                SelectPlanoDeConta::make('conta_contabil')
                                    ->label('Conta contabil')
                                    ->required()
                                    ->columnSpan(2),

                                Select::make('codigo_historico')
                                    ->label('Cód. Histórico')
                                    ->required()
                                    ->options(function () {

                                        $values = HistoricoContabil::where('organization_id', getOrganizationCached()->id)
                                            ->orderBy('codigo', 'asc')
                                            ->get()
                                            ->map(function ($item) {
                                                $item->codigo_descricao = $item->codigo . ' | ' . $item->descricao;
                                                return $item;
                                            })

                                            ->pluck('codigo_descricao', 'id');

                                        return $values;
                                    })
                                    ->columnSpan(2),

                            ])



                    ])
                    ->action(function (array $data, $action) {

                        $lancamentos = ImportarLancamentoContabil::where('organization_id', getOrganizationCached()->id)
                            ->where('user_id', Auth::user()->id)
                            ->where('valor', '!=', 0)
                            ->when($data['is_exist'], fn($query) => $query->where('is_exist', $data['is_exist'])) // Aplica o filtro apenas se is_exist for true
                            ->orderBy('id', 'asc')
                            ->get();


                        if ($lancamentos->count() == 0) {

                            Notification::make()
                                ->title('Erro ao gerar lançamento')
                                ->body('Não existe registros para serem processados')
                                ->success()
                                ->send();

                            $action->halt();
                        }

                        $filename = now()->format('m-Y') . '/' . Str::random(8) . '.txt';

                        if (isset($data['conta_contabil'])) {
                            $conta_contabil = PlanoDeConta::getCachedByOrganization(getOrganizationCached()->id)
                                ->where('codigo', $data['conta_contabil'])->first();

                            $data['descricao_conta_contabil'] = $conta_contabil?->nome;
                        }


                        $txtContent = $this->gerarRelatorio($lancamentos, $data, ';');

                        Storage::disk('downloads-files')->put($filename, $txtContent);


                        Notification::make()
                            ->title('Exportação iniciada')
                            ->body('A exportação foi iniciada e as linhas selecionadas serão processadas em segundo plano')
                            ->success()
                            ->send();

                        if ($data['limpar_lancamentos']) {
                            ImportarLancamentoContabil::where('organization_id', getOrganizationCached()->id)
                                ->where('user_id', Auth::user()->id)
                                ->delete();
                        }


                        return response()->download(public_path('/downloads/' . $filename));
                    }),

               // ReprocessarExcelFileAction::make(),
            ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('primary')
                ->button(),






        ];
    }

    public function listOperacoes()
    {

        return [
            'cliente' => 'Buscar Cliente',
            'cliente_parametro' => 'Buscar Cliente + Parametro',
            'fornecedor' => 'Buscar Fornecedor',
            'fornecedor_parametro' => 'Buscar Fornecedor + Parametro',
            'parametro' => 'Buscar Parametro',
            'banco' => 'Buscar Banco',
            'plano_conta' => 'Buscar Plano de conta'
        ];
    }

    public function saveForm($data)
    {
        unset($data['attachment']);



        LayoutArquivoConcilicacao::updateOrCreate(
            ['issuer_id' => getCurrentIssuer(), 'layout' => $data['layout']],
            ['issuer_id' => getCurrentIssuer(), 'tenant_id' => auth()->user()->tenant_id, 'layout' => $data['layout'], 'form' => $data]
        );
    }

    public function gerarRelatorio(Collection $lancamentos, array $data = [], string $separador = ';'): string
    {

        $linhas = $lancamentos
            ->map(fn($lancamento) => $this->formatarConteudo($lancamento, $data, $separador));

        return $linhas->implode(PHP_EOL) . PHP_EOL;
    }

    private function formatarConteudo($lancamento, array $params, string $separador): string
    {

        $data = $lancamento->data->format('d/m/Y');
        $valorFormatado = number_format(abs($lancamento->valor), 2, ',', '');


        $semLancamento = false;

        //Não possui lançamento
        if ($lancamento->is_exist == false) {


            $semLancamento = true;
            if (is_null($lancamento->credito)) {

                $metadata = $lancamento->metadata;
                $metadata['descricao_credito'] = $params['descricao_conta_contabil'];

                $lancamento->credito = $params['conta_contabil'];
                $lancamento->metadata = $metadata;
            } else {

                $metadata = $lancamento->metadata;
                $metadata['descricao_debito'] = $params['descricao_conta_contabil'];

                $lancamento->debito = $params['conta_contabil'];
                $lancamento->metadata = $metadata;
            }
        }


        return implode($separador, [
            $data,
            $lancamento->debito,
            $lancamento->credito,
            $valorFormatado,
            0,
            $lancamento->historico,
        ]);
    }



    public static function incrementarOuDecrementarDiasUteis(Carbon $date, $value)
    {
        if ($value === 0) {
            return $date; // Não faz nada se o valor for 0
        }

        $currentDate = clone $date;
        $increment = $value > 0 ? 1 : -1;
        $remainingDays = abs($value);

        while ($remainingDays > 0) {
            $currentDate->addDays($increment);

            if ($currentDate->isWeekday()) {
                $remainingDays--;
            }
        }

        return $currentDate;
    }
}
