<?php

namespace App\Filament\Fiscal\Pages\Importar;

use Exception;
use Carbon\Carbon;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use App\Jobs\Sieg\ProcessarDocumentoSiegJob;
use Illuminate\Http\Client\RequestException;
use App\Jobs\Sieg\ProcessarImportacaoSiegJob;
use Filament\Forms\Components\Actions\Action;
use App\Services\Fiscal\SiegConnectionService;
use Illuminate\Http\Client\ConnectionException;

class Sieg extends Page
{

    // Constantes para tipos de documentos
    const XML_TYPE_NFE = 1;
    const XML_TYPE_CTE = 2;
    const XML_TYPE_NFSE = 3;
    const XML_TYPE_NFCE = 4;
    const XML_TYPE_CFE = 5;

    
    protected static ?string $navigationGroup = 'Ferramentas';

    protected static ?string $modelLabel = 'Importar SIEG';

    protected static ?string $navigationLabel = 'Importar SIEG';

    protected static ?string $title = 'Importar SIEG';

    protected static string $view = 'filament.fiscal.pages.importar.sieg';

    // Propriedades do formulário
    public $dataInicial;
    public $dataFinal;
    public $tipoDocumento = 1; // Padrão: NFe
    public $tipoCnpj = 'emitente';
    public $downloadEventos = false;
    public $resultados = null;
    public $importando = false;
    public $progresso = 0;
    public $paginaAtual = 0;
    public $totalPaginas = 0;
    public $ultimaAtualizacao = null;

    public function mount(): void
    {
        // Inicializa com o primeiro dia do mês atual
        $this->dataInicial = now()->startOfMonth()->format('Y-m-d');
        // Inicializa com o dia atual
        $this->dataFinal = now()->format('Y-m-d');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Importação de documentos fiscais')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('dataInicial')
                                ->label('Data inicial')
                                ->displayFormat('d/m/Y')
                                ->required(),
                            DatePicker::make('dataFinal')
                                ->label('Data final')
                                ->maxDate(now())
                                ->displayFormat('d/m/Y')
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Select::make('tipoDocumento')
                                ->label('Tipo de documento')
                                ->options(self::getTiposDocumento())
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    if (filled($state)) {
                                        $this->tipoDocumento = $state;

                                        if ($this->tipoDocumento == self::XML_TYPE_CTE) {
                                            $set('tipoCnpj', 'tomador');
                                            $this->tipoCnpj = 'tomador';
                                        } else {
                                            $set('tipoCnpj', 'emitente');
                                            $this->tipoCnpj = 'emitente';
                                        }
                                    }
                                }),
                            Select::make('tipoCnpj')
                                ->label('Tipo de CNPJ')
                                ->options(function (Get $get) {
                                    $tipoDoc = $get('tipoDocumento');

                                    if ($tipoDoc == self::XML_TYPE_CTE) {
                                        return [
                                            'tomador' => "CNPJ do Tomador",
                                            'remetente' => "CNPJ do Remetente",
                                            'emitente' => "CNPJ do Emitente",
                                            'destinatario' => "CNPJ do Destinatário",
                                        ];
                                    }

                                    return [
                                        'emitente' => "CNPJ do Emitente",
                                        'destinatario' => "CNPJ do Destinatário",
                                    ];
                                })
                                ->default('emitente')
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                    if (filled($state)) {
                                        $this->tipoCnpj = $state;
                                    }
                                })
                                ->required(),

                        ]),
                    Toggle::make('downloadEventos')
                        ->label('Baixar eventos')
                        ->helperText('Ativa o download de eventos associados aos documentos (manifestações, cancelamentos, etc.)')
                        ->default(false),
                ]),
        ]);
    }


    protected function getFormActions(): array
    {
        return [
            Action::make('importar')
                ->label('Importar')
                ->action('importarDocumentos')
                ->color('primary')
        ];
    }

    protected function exibirIndicadorProgresso()
    {
        if (!$this->importando) {
            return null;
        }

        return Placeholder::make('progresso')
            ->label('Progresso da importação')
            ->content(function () {
                if ($this->totalPaginas > 0) {
                    $porcentagem = min(100, round(($this->paginaAtual / $this->totalPaginas) * 100));
                    $html = "
                        <div class='w-full bg-gray-200 rounded-full dark:bg-gray-700 mb-2'>
                            <div class='bg-primary-600 text-xs font-medium text-primary-100 text-center p-0.5 leading-none rounded-full' style='width: {$porcentagem}%'>{$porcentagem}%</div>
                        </div>
                    ";
                    $html .= "<div class='text-xs text-gray-600 dark:text-gray-400'>Processando página {$this->paginaAtual} de aproximadamente {$this->totalPaginas}</div>";

                    if ($this->ultimaAtualizacao) {
                        $html .= "<div class='text-xs text-gray-500 dark:text-gray-500 mt-1'>Última atualização: {$this->ultimaAtualizacao}</div>";
                    }

                    return new HtmlString($html);
                }

                return 'Iniciando importação...';
            });
    }

    public function importarDocumentos(): void
    {
        $this->validate([
            'dataInicial' => ['required', 'date'],
            'dataFinal' => ['required', 'date', 'after_or_equal:dataInicial'],
            'tipoDocumento' => ['required', 'integer'],
            'tipoCnpj' => ['required', 'string'],
        ]);

        $organization = getOrganizationCached();

        $superAdmin = $organization->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super-admin');
            })
            ->first();


        if (!$superAdmin) {
            Notification::make()
                ->title('Erro')
                ->body('Nenhum super admin encontrado')
                ->danger()
                ->send();

            throw new Halt();
        }

        try {
            // Registra a importação com status inicial antes de despachar o job
            $tiposDocumento = self::getTiposDocumento();
            $tipoDesc = $tiposDocumento[$this->tipoDocumento] ?? "Tipo {$this->tipoDocumento}";
            
            // Verificar se já existe uma importação em processamento com os mesmos parâmetros
            $importacaoExistente = DB::table('sieg_importacoes')
                ->where('organization_id', getOrganizationCached()->id)
                ->where('user_id', Auth::id())
                ->where('data_inicial', $this->dataInicial)
                ->where('data_final', $this->dataFinal)
                ->where('status', 'processando')
                ->where('tipo_documento', $tipoDesc)
                ->where('tipo_cnpj', $this->tipoCnpj)
                ->first();

         
            if ($importacaoExistente) {
                Notification::make()
                    ->title('Importação já em andamento')
                    ->body("Já existe uma importação em processamento para o período selecionado.")
                    ->warning()
                    ->send();
                    
                return;
            }
            
            // Cria o registro inicial da importação
            $idRegistro = DB::table('sieg_importacoes')->insertGetId([
                'organization_id' => getOrganizationCached()->id,
                'user_id' => Auth::id(),
                'data_inicial' => $this->dataInicial,
                'data_final' => $this->dataFinal,
                'tipo_documento' => $tipoDesc,
                'tipo_cnpj' => $this->tipoCnpj,
                'documentos_processados' => 0,
                'eventos_processados' => 0,
                'total_processados' => 0,
                'total_documentos' => 0,
                'sucesso' => false,
                'status' => 'processando',
                'mensagem' => 'Importação em processamento',
                'download_eventos' => $this->downloadEventos,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Registra nos logs a criação do registro
            Log::info('Registro de importação SIEG criado', [
                'id' => $idRegistro,
                'organization_id' => getOrganizationCached()->id,
                'user_id' => Auth::id(),
                'data_inicial' => $this->dataInicial,
                'data_final' => $this->dataFinal,
                'tipo_documento' => $tipoDesc,
                'tipo_cnpj' => $this->tipoCnpj
            ]);

            // Despacha o job para processamento em background
            ProcessarImportacaoSiegJob::dispatch(
                getOrganizationCached(),
                $this->dataInicial,
                $this->dataFinal,
                $this->tipoDocumento,
                $this->tipoCnpj,
                $this->downloadEventos,
                Auth::id(),
                $idRegistro
            );

            Notification::make()
                ->title('Importação iniciada')
                ->body("A importação foi iniciada em segundo plano. Você pode acompanhar o progresso na lista de importações e será notificado quando concluir.")
                ->success()
                ->send();
        
        } catch (\Exception $e) {
            Log::error('Erro ao iniciar importação SIEG', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            Notification::make()
                ->title('Erro')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function obterHistoricoImportacoes(): string
    {
        try {
            $importacoes = DB::table('sieg_importacoes')
                ->where('organization_id', getOrganizationCached()->id)
                ->orderByDesc('created_at')
                ->limit(15)
                ->get();

            if ($importacoes->isEmpty()) {
                return '<div class="text-gray-500 italic">Nenhuma importação encontrada.</div>';
            }

            $html = '<div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CNPJ</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Processados</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Eventos</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Mensagem</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-900 dark:divide-gray-700">';

            foreach ($importacoes as $importacao) {
                $dataImportacao = Carbon::parse($importacao->created_at)->format('d/m/Y H:i');
                $periodo = Carbon::parse($importacao->data_inicial)->format('d/m/Y') . ' a ' .
                    Carbon::parse($importacao->data_final)->format('d/m/Y');

                // Define a cor e texto do status
                $statusClass = match ($importacao->status ?? 'pendente') {
                    'concluido' => 'text-success-600 dark:text-success-400',
                    'erro' => 'text-danger-600 dark:text-danger-400',
                    'processando' => 'text-warning-600 dark:text-warning-400',
                    default => 'text-gray-600 dark:text-gray-400'
                };

                $statusText = match ($importacao->status ?? 'pendente') {
                    'concluido' => 'Concluído',
                    'erro' => 'Erro',
                    'processando' => 'Processando...',
                    default => 'Pendente'
                };

                $status = "<span class=\"{$statusClass}\">{$statusText}</span>";

                $processados = "{$importacao->documentos_processados}";
                if ($importacao->eventos_processados > 0) {
                    $processados .= " + {$importacao->eventos_processados} eventos";
                }
                $processados .= " / {$importacao->total_documentos}";

                // Trunca a mensagem se for muito longa
                $mensagem = $importacao->mensagem ?? '';
                if (strlen($mensagem) > 50) {
                    $mensagem = substr($mensagem, 0, 47) . '...';
                }
                $mensagem = e($mensagem); // Escape HTML

                // Ícone para indicar se a importação incluiu eventos
                $eventosIcon = $importacao->download_eventos
                    ? '<svg class="w-5 h-5 text-success-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>'
                    : '<svg class="w-5 h-5 text-gray-400 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';

                $html .= "
                    <tr class='hover:bg-gray-50 dark:hover:bg-gray-800'>
                        <td class='px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'>{$dataImportacao}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'>{$periodo}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'>{$importacao->tipo_documento}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'>{$importacao->tipo_cnpj}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100'>{$processados}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm'>{$status}</td>
                        <td class='px-3 py-2 whitespace-nowrap text-sm' title='" . ($importacao->download_eventos ? 'Com download de eventos' : 'Sem download de eventos') . "'>{$eventosIcon}</td>
                        <td class='px-3 py-2 text-sm text-gray-900 dark:text-gray-100'>{$mensagem}</td>
                    </tr>";
            }

            $html .= '</tbody></table></div>';
            return $html;
        } catch (\Exception $e) {
            // Se houver erro, retorna mensagem
            return '<div class="text-danger-500">Erro ao carregar histórico: ' . e($e->getMessage()) . '</div>';
        }
    }

    protected function exibirResultados()
    {
        if (!$this->resultados) {
            return null;
        }

        $totalProcessados = ($this->resultados['documentos_processados'] ?? 0) + ($this->resultados['eventos_processados'] ?? 0);
        $eventosProcessados = $this->resultados['eventos_processados'] ?? 0;
        $documentosProcessados = $this->resultados['documentos_processados'] ?? 0;
        $totalDocumentos = $this->resultados['total_documentos'] ?? 0;

        $section = Section::make('Resultados da importação')
            ->schema([
                Placeholder::make('status')
                    ->label('Status')
                    ->content(fn() => $this->resultados['success'] ? 'Concluído com sucesso' : 'Falha na importação')
                    ->extraAttributes(['class' => $this->resultados['success'] ? 'text-success-600' : 'text-danger-600']),

                Placeholder::make('documentosProcessados')
                    ->label('Documentos processados')
                    ->content($documentosProcessados),

                Placeholder::make('eventosProcessados')
                    ->label('Eventos processados')
                    ->content($eventosProcessados)
                    ->visible(fn() => $this->downloadEventos),

                Placeholder::make('totalProcessados')
                    ->label('Total processados')
                    ->content($totalProcessados),

                Placeholder::make('totalDocumentos')
                    ->label('Total de documentos')
                    ->content($totalDocumentos),
            ]);

        // Adiciona erros, se houver
        if (!empty($this->resultados['erros'])) {
            $errosHtml = '<ul class="text-sm text-danger-600">';
            foreach (array_slice($this->resultados['erros'], 0, 5) as $erro) {
                $errosHtml .= '<li>' . ($erro['erro'] ?? 'Erro desconhecido') . '</li>';
            }

            if (count($this->resultados['erros']) > 5) {
                $errosHtml .= '<li>... e mais ' . (count($this->resultados['erros']) - 5) . ' erros</li>';
            }

            $errosHtml .= '</ul>';

            $section->schema([
                ...$section->getSchema(),
                Placeholder::make('erros')
                    ->label('Erros encontrados')
                    ->content(new HtmlString($errosHtml)),
            ]);
        }

        return $section;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('atualizar')
                ->label('Atualizar')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->redirect(static::getUrl()))
                ->color('gray'),
        ];
    }

    /**
     * Renderiza a view da página com dados adicionais.
     */
    protected function getViewData(): array
    {

        // Verifica se existem importações em andamento
        $processando = DB::table('sieg_importacoes')
            ->where('organization_id', getOrganizationCached()->id)
            ->where('status', 'processando')
            ->exists();

        // Retorna dados adicionais para a view
        return [
            'processando' => $processando,
            'historicoImportacoes' => $this->obterHistoricoImportacoes(),
        ];
    }


    /**
     * Retorna os tipos de documentos disponíveis
     */
    public static function getTiposDocumento(): array
    {
        return [
            self::XML_TYPE_NFE => 'NFe',
            self::XML_TYPE_CTE => 'CT-e',
            self::XML_TYPE_NFSE => 'NFSe',
            self::XML_TYPE_NFCE => 'NFCe',
            self::XML_TYPE_CFE => 'CF-e'
        ];
    }
}
