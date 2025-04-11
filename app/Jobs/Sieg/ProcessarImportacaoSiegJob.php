<?php

namespace App\Jobs\Sieg;

use Exception;
use Carbon\Carbon;
use App\Models\Tenant\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use App\Services\Fiscal\SiegConnectionService;
use Illuminate\Http\Client\ConnectionException;

class ProcessarImportacaoSiegJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * O número de vezes que o job pode ser tentado.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * O número de segundos que o job pode ser processado antes de ser considerado travado.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hora

    /**
     * Indica se o job deve ser marcado como falha na primeira exceção.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Cria uma nova instância do job.
     */
    public function __construct(
        private readonly Organization $organization,
        private readonly string $dataInicial,
        private readonly string $dataFinal,
        private readonly int $tipoDocumento,
        private readonly string $tipoCnpj,
        private readonly bool $downloadEventos,
        private readonly ?string $userId = null,
        private ?int $jobId = null
    ) {}

    /**
     * Executa o job.
     */
    public function handle(): void
    {

        $superAdmin = User::find($this->userId);

        $apiKey = $superAdmin->sieg()->first()?->sieg_api_key;

        $skip = 0;
        $take = 50; // Máximo permitido pela API
        $temMaisPaginas = true;


        $resultadoFinal = [
            'success' => true,
            'documentos_processados' => 0,
            'eventos_processados' => 0,
            'total_documentos' => 0,
            'erros' => []
        ];

        try {

            while ($temMaisPaginas) {

                $requestData = [
                    'XmlType' => $this->tipoDocumento,
                    'Take' => $take,
                    'Skip' => $skip,
                    'DataEmissaoInicio' => $this->dataInicial,
                    'DataEmissaoFim' => $this->dataFinal,
                    'CnpjEmit' => $this->organization->cnpj,
                    'Downloadevent' => $this->downloadEventos,
                ];

                $response = Http::retry(3, 100)->withHeader('Accept', 'application/json')
                    ->post(
                        'https://api.sieg.com/BaixarXmls' . '?api_key=' . $apiKey,
                        $requestData
                    );

                $responseData = json_decode($response->json(), true);

                $totalDocumentosPagina = count($responseData ?? []);

                // Adiciona ao resultado final
                $resultadoFinal['documentos_processados'] += $totalDocumentosPagina;

                // Lógica para verificar se há mais páginas
                // Se retornou menos que o máximo (take), provavelmente é a última página
                $temMaisPaginas = $totalDocumentosPagina >= $take;

                if (!empty($responseData)) {
                    foreach ($responseData as $xml) {

                        $this->processarResposta($xml, $this->organization, $requestData);
                    }
                }

                $skip++;
            }


            //code...
        } catch (ConnectionException $e) {
            Log::error('SIEG Service: Erro de conexão com a API.', ['message' => $e->getMessage()]);
        } catch (RequestException $e) {
            // Verifica se é um erro 404 com a mensagem específica de "Nenhum arquivo XML localizado"
            if ($e->response && $e->response->status() === 404) {
                $responseBody = $e->response->json();
                if (
                    is_array($responseBody) &&
                    isset($responseBody[0]) &&
                    str_contains($responseBody[0], "Nenhum arquivo XML localizado")
                ) {

                    // Registra como informação, não como erro
                    Log::info('SIEG Service: Nenhum arquivo XML localizado para os parâmetros informados.', [
                        'data_inicial' => $this->dataInicial,
                        'data_final' => $this->dataFinal,
                        'tipo_documento' => $this->tipoDocumento,
                        'tipo_cnpj' => $this->tipoCnpj
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('SIEG Service: Erro inesperado.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Processa a resposta da API
     *
     * @param string $value Resposta da API
     * @param array $params Parâmetros da requisição
     * @param Organization $organization
     * @return array
     */
    private function processarResposta(string $value, Organization $organization, array $params = []): array
    {
        $documentosProcessados = 0;
        $eventosProcessados = 0;
        $erros = [];
        $downloadEvents = $params['Downloadevent'] ?? false;

        try {
            $decodedData = base64_decode($value);
            $xml = iconv('ASCII', 'UTF-8//IGNORE', $decodedData);

            if (empty(trim($xml))) {
                $erros[] = ['erro' => 'XML vazio após decodificação'];
                return  $erros;
            }

            // Verifica se o XML é um evento ou documento principal
            $isEvento = $this->isXmlEvento($xml);

            // Despacha um job para processar esse documento em background
            ProcessarDocumentoSiegJob::dispatch(
                $organization,
                $xml,
                $params,
                $isEvento
            );
        } catch (Exception $e) {
            $erros[] = [
                'erro' => $e->getMessage(),
                'xml_hash' => isset($xml) ? md5($xml) : 'não disponível'
            ];
            Log::error('Erro ao processar conteúdo da Sieg', [
                'erro' => $e->getMessage(),
                'is_evento' => $isEvento ?? 'não identificado'
            ]);
        }

        return [
            'success' => true,
            'documentos_processados' => $documentosProcessados,
            'eventos_processados' => $eventosProcessados,
            'total_processados' => $documentosProcessados + $eventosProcessados,
            'download_eventos' => $downloadEvents,
            'erros' => $erros,
        ];
    }

    /**
     * Verifica se o XML é um evento
     * 
     * @param string $xml Conteúdo XML
     * @return bool
     */
    private function isXmlEvento(string $xml): bool
    {
        try {
            // Carrega o XML como um objeto DOMDocument
            $dom = new \DOMDocument();
            $dom->loadXML($xml);

            // Verifica nodes que identificam eventos
            $eventoNodes = [
                'procEventoNFe',
                'evento',
                'evtConfRecebto', // Evento de confirmação de recebimento
                'evtCancNFe',     // Evento de cancelamento
                'evtCCe'          // Evento de carta de correção
            ];

            foreach ($eventoNodes as $nodeName) {
                if ($dom->getElementsByTagName($nodeName)->length > 0) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Log::warning('Erro ao verificar se o XML é um evento: ' . $e->getMessage());
            return false; // Em caso de erro, assume que não é um evento
        }
    }
}
