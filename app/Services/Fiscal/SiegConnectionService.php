<?php

namespace App\Services\Fiscal;

use Exception;
use Carbon\Carbon;
use App\Models\Tenant\User;
use App\Models\Tenant\EventoNfe;
use App\Models\Tenant\ResumoNfe;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Jobs\Sieg\ProcessarDocumentoSiegJob;
use Illuminate\Http\Client\RequestException;
use App\Services\Tenant\Xml\XmlNfeReaderService;

class SiegConnectionService
{
    private Organization $organization;
    private string $apiKey;
    private string $apiUrl;

    // Constantes para tipos de documentos
    const XML_TYPE_NFE = 1;
    const XML_TYPE_CTE = 2;
    const XML_TYPE_NFSE = 3;
    const XML_TYPE_NFCE = 4;
    const XML_TYPE_CFE = 5;

    // Limite máximo de documentos por requisição
    const MAX_DOCUMENTS_PER_REQUEST = 50;

    /**
     * Construtor do serviço de conexão com a API Sieg
     * 
     * @param Organization $organization Organização atual
     */
    public function __construct(Organization $organization)
    {
        $this->organization = $organization;
        $this->apiUrl = 'https://api.sieg.com/BaixarXmls';
        $this->configurar();
    }

    /**
     * Configura a conexão com a API Sieg usando os dados da organização
     *
     * @throws Exception
     */
    private function configurar(): void
    {
        try {
            $superAdmin = $this->organization->users()
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'super-admin');
                })
                ->first();

            if ($superAdmin) {
                $this->apiKey = $superAdmin->sieg()->first()?->sieg_api_key;
            }

            if (empty($this->apiKey)) {
                throw new Exception('Chave da API Sieg não configurada para o usuário super-admin.');
            }
        } catch (Exception $e) {
            Log::error('Erro ao configurar conexão com a API Sieg', [
                'organization_id' => $this->organization->id,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Erro ao configurar conexão com a API Sieg: ' . $e->getMessage());
        }
    }

    /**
     * Baixa todos os XMLs disponíveis por período com paginação automática
     *
     * @param array $params Parâmetros base da consulta
     * @param string $tipoDocumento Descrição do tipo de documento para logs
     * @param callable|null $progressCallback Callback para atualizar o progresso (skip)
     * @return array Resultado completo da consulta
     */
    private function baixarTodosDocumentosPorPeriodo(
        array $params,
        string $tipoDocumento = 'XML',
        ?callable $progressCallback = null
    ): array {
        $skip = 0;
        $totalProcessados = 0;
        $totalDocumentos = 0;
        $totalEventos = 0;
        $todosErros = [];
        $hasMore = true;
        $tentativasSemResultados = 0;
        $maxTentativasSemResultados = 3; // Evita loops infinitos

        // Loop de paginação
        while ($hasMore && $tentativasSemResultados < $maxTentativasSemResultados) {
            // Log para acompanhamento
            Log::info("Consultando {$tipoDocumento} com skip = {$skip}", [
                'params' => $params
            ]);

            // Chama o callback de progresso, se fornecido
            if ($progressCallback && is_callable($progressCallback)) {
                call_user_func($progressCallback, $skip, $totalDocumentos);
            }

            $resultado = $this->baixarXmls($params, $skip);

            // Verifica se houve erro na requisição
            if (!$resultado['success']) {
                return [
                    'success' => false,
                    'message' => "Erro durante a consulta de {$tipoDocumento}: " . ($resultado['message'] ?? 'Erro desconhecido'),
                    'documentos_processados' => $totalProcessados,
                    'eventos_processados' => $totalEventos,
                    'total_documentos' => $totalDocumentos,
                    'erros' => $todosErros,
                    'ultimo_skip' => $skip
                ];
            }

            // Verifica se a API sinalizou explicitamente que não há mais documentos
            if (isset($resultado['no_more_documents']) && $resultado['no_more_documents']) {
                Log::info("API informou que não há documentos disponíveis para os parâmetros informados");

                // Finaliza com sucesso, sem continuar a paginação
                return [
                    'success' => true,
                    'message' => "Consulta concluída. " . ($totalDocumentos > 0
                        ? "Foram encontrados {$totalDocumentos} documentos."
                        : "Nenhum documento encontrado para os parâmetros informados."),
                    'documentos_processados' => $totalProcessados,
                    'eventos_processados' => $totalEventos,
                    'total_documentos' => $totalDocumentos,
                    'erros' => $todosErros,
                    'ultimo_skip' => $skip,
                    'params_utilizados' => $params
                ];
            }

            // Atualiza estatísticas
            $docsProcessados = $resultado['documentos_processados'] ?? 0;
            $eventosProcessados = $resultado['eventos_processados'] ?? 0;
            $docsEncontrados = $resultado['total_documentos'] ?? 0;

            $totalProcessados += ($docsProcessados + $eventosProcessados);
            $totalDocumentos += $docsEncontrados;
            $totalEventos += $eventosProcessados;

            // Acumula erros, se houver
            if (!empty($resultado['erros'])) {
                $todosErros = array_merge($todosErros, $resultado['erros']);
            }

            // Verifica se não houve documentos nesta página
            if ($docsEncontrados == 0) {
                $tentativasSemResultados++;
                Log::info("Nenhum {$tipoDocumento} encontrado na página. Tentativa {$tentativasSemResultados} de {$maxTentativasSemResultados}");
                $hasMore = false; // Se não encontrou documentos, provavelmente não há mais
            } else {
                $tentativasSemResultados = 0; // Reseta contador se encontrou documentos
            }

            // Incrementa o skip para a próxima página
            $skip++;

            // Aguarda um breve intervalo para não sobrecarregar a API
            // (limite de 30 requisições por minuto)
            usleep(300000); // 300ms
        }

        return [
            'success' => true,
            'documentos_processados' => $totalProcessados,
            'eventos_processados' => $totalEventos,
            'total_documentos' => $totalDocumentos,
            'erros' => $todosErros,
            'ultimo_skip' => $skip,
            'params_utilizados' => $params
        ];
    }

    /**
     * Baixa todos os XMLs de NFe disponíveis por período
     *
     * @param string $dataInicial Data inicial (Y-m-d)
     * @param string $dataFinal Data final (Y-m-d)
     * @param string $tipoCnpj Tipo de CNPJ para busca ('emitente' ou 'destinatario')
     * @param bool $downloadEvents Se deve baixar eventos relacionados
     * @param callable|null $progressCallback Callback para atualizar o progresso
     * @return array Resultado completo da consulta
     */
    public function baixarTodosXmlsNFePorPeriodo(
        string $dataInicial,
        string $dataFinal,
        string $tipoCnpj = 'emitente',
        bool $downloadEvents = false,
        ?callable $progressCallback = null
    ): array {
        $params = $this->prepararParametrosConsulta(
            $dataInicial,
            $dataFinal,
            self::XML_TYPE_NFE,
            $tipoCnpj,
            $downloadEvents
        );

        return $this->baixarTodosDocumentosPorPeriodo($params, 'NFe', $progressCallback);
    }

    /**
     * Baixa todos os CT-e disponíveis por período
     *
     * @param string $dataInicial Data inicial (Y-m-d)
     * @param string $dataFinal Data final (Y-m-d)
     * @param string $tipoCnpj Tipo de CNPJ para busca ('remetente' ou 'tomador')
     * @param bool $downloadEvents Se deve baixar eventos relacionados
     * @param callable|null $progressCallback Callback para atualizar o progresso
     * @return array Resultado completo da consulta
     */
    public function baixarTodosCTePorPeriodo(
        string $dataInicial,
        string $dataFinal,
        string $tipoCnpj = 'tomador',
        bool $downloadEvents = false,
        ?callable $progressCallback = null
    ): array {
        $params = $this->prepararParametrosConsulta(
            $dataInicial,
            $dataFinal,
            self::XML_TYPE_CTE,
            $tipoCnpj,
            $downloadEvents
        );

        return $this->baixarTodosDocumentosPorPeriodo($params, 'CT-e', $progressCallback);
    }

    /**
     * Baixa todos os documentos por período independente do tipo
     *
     * @param string $dataInicial Data inicial (Y-m-d)
     * @param string $dataFinal Data final (Y-m-d)
     * @param int $xmlType Tipo do documento
     * @param string $tipoCnpj Tipo de CNPJ para busca
     * @param bool $downloadEvents Se deve baixar eventos relacionados
     * @param callable|null $progressCallback Callback para atualizar o progresso
     * @return array Resultado completo da consulta
     */
    public function baixarTodosDocumentosPorTipo(
        string $dataInicial,
        string $dataFinal,
        int $xmlType,
        string $tipoCnpj,
        bool $downloadEvents = false,
        ?callable $progressCallback = null
    ): array {
        $params = $this->prepararParametrosConsulta(
            $dataInicial,
            $dataFinal,
            $xmlType,
            $tipoCnpj,
            $downloadEvents
        );

        $tipoDesc = self::getTiposDocumento()[$xmlType] ?? "Tipo {$xmlType}";

        return $this->baixarTodosDocumentosPorPeriodo($params, $tipoDesc, $progressCallback);
    }

    /**
     * Prepara os parâmetros para consulta baseado no tipo de documento
     *
     * @param string $dataInicial Data inicial (Y-m-d)
     * @param string $dataFinal Data final (Y-m-d)
     * @param int $xmlType Tipo do documento
     * @param string $tipoCnpj Tipo de CNPJ para busca
     * @param bool $downloadEvents Se deve baixar eventos relacionados
     * @return array Parâmetros formatados para a consulta
     */
    private function prepararParametrosConsulta(
        string $dataInicial,
        string $dataFinal,
        int $xmlType,
        string $tipoCnpj,
        bool $downloadEvents
    ): array {
        $params = [
            'XmlType' => $xmlType,
            'DataEmissaoInicio' => $dataInicial . 'T00:00:00.000Z',
            'DataEmissaoFim' => $dataFinal . 'T23:59:59.999Z',
            'Downloadevent' => $downloadEvents
        ];

        // Define o parâmetro de CNPJ com base no tipo de documento e parâmetro escolhido
        if ($xmlType === self::XML_TYPE_CTE) {
            // Para CT-e
            if (strtolower($tipoCnpj) === 'remetente') {
                $params['CnpjRem'] = $this->organization->cnpj;
            } else {
                // Por padrão para CT-e, usa como tomador
                $params['CnpjTom'] = $this->organization->cnpj;
            }
        } else {
            // Para outros tipos (NFe, NFCe, etc)
            if (strtolower($tipoCnpj) === 'destinatario') {
                $params['CnpjDest'] = $this->organization->cnpj;
            } else {
                // Por padrão para outros documentos, usa como emitente
                $params['CnpjEmit'] = $this->organization->cnpj;
            }
        }

        return $params;
    }

    /**
     * Baixa XMLs com base nos parâmetros fornecidos
     *
     * @param array $params Parâmetros para a consulta
     * @param int $skip Quantidade de registros para pular
     * @return array
     */
    public function baixarXmls(array $params, int $skip): array
    {
        try {
            $requestData = array_merge([
                'Take' => self::MAX_DOCUMENTS_PER_REQUEST,
                'Skip' => $skip
            ], $params);

            Log::info('Enviando requisição para API Sieg', [
                'skip' => $skip,
                'take' => self::MAX_DOCUMENTS_PER_REQUEST,
                'download_eventos' => $params['Downloadevent'] ?? false
            ]);

            $response = Http::retry(3, 100)
                ->post(
                    $this->apiUrl . '?api_key=' . $this->apiKey,
                    $requestData
                )->throw();
           

            // Para outros tipos de falha na requisição
            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'Erro ao baixar XMLs: ' . $response->status(),
                    'errors' => $response->body()
                ];
            }

            // Verifica se a resposta é uma string JSON
            $responseBody = $response->body();

            // Tenta fazer o parsing do JSON
            $responseData = json_decode($response->json(), true);

            // Verifica se o parsing foi bem-sucedido
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Erro ao decodificar resposta JSON', [
                    'json_error' => json_last_error_msg(),
                    'response' => substr($responseBody, 0, 500)
                ]);

                return [
                    'success' => false,
                    'message' => 'Resposta inválida da API: ' . json_last_error_msg(),
                    'raw_response' => substr($responseBody, 0, 500)
                ];
            }

            return $this->processarResposta($responseData, $requestData);

        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                $errorMessage = $e->response->json()['message'] ?? $e->response->body();

                if (str_contains($errorMessage, 'Nenhum arquivo XML localizado')) {
                    // Trata como sucesso, mas sem documentos
                    return [
                        'success' => true,
                        'message' => 'Nenhum arquivo XML localizado',
                        'total_documentos' => 0,
                        'documentos_processados' => 0,
                        'eventos_processados' => 0,
                        'total_processados' => 0,
                        'erros' => [],
                        'no_more_documents' => true // Indicador para parar a paginação
                    ];
                }
            }



            Log::error('Erro ao baixar XMLs da API Sieg', [
                'organization_id' => $this->organization->id,
                'params' => $params,
                'skip' => $skip,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Processa a resposta da API
     *
     * @param array $response Resposta da API
     * @param array $params Parâmetros da requisição
     * @return array
     */
    private function processarResposta(array $response, array $params = []): array
    {
        $documentosProcessados = 0;
        $eventosProcessados = 0;
        $erros = [];
        $downloadEvents = $params['Downloadevent'] ?? false;

        foreach ($response as $value) {
            try {
                $decodedData = base64_decode($value);
                $xml = iconv('ASCII', 'UTF-8//IGNORE', $decodedData);

                if (empty(trim($xml))) {
                    $erros[] = ['erro' => 'XML vazio após decodificação'];
                    continue;
                }

                // Verifica se o XML é um evento ou documento principal
                $isEvento = $this->isXmlEvento($xml);

                // Despacha um job para processar esse documento em background
                ProcessarDocumentoSiegJob::dispatch(
                    $this->organization,
                    $xml,
                    $params,
                    $isEvento
                );

                // Incrementa contadores para relatório
                if ($isEvento) {
                    $eventosProcessados++;
                } else {
                    $documentosProcessados++;                    
                }
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
        }

        return [
            'success' => true,
            'total_documentos' => count($response),
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

    /**
     * Processa um XML de evento
     * 
     * @param string $xmlContent Conteúdo do XML do evento
     * @param array $params Parâmetros da requisição original
     * @return bool Verdadeiro se o evento foi processado com sucesso
     */
    private function processarEvento(string $xmlContent, array $params = []): bool
    {
        try {
            // Utiliza a biblioteca NFePHP para transformar o XML em um array padronizado
            $std = new \NFePHP\NFe\Common\Standardize();
            $stdClass = $std->toStd($xmlContent);

            // Verifica se o XML é um evento completo (procEventoNFe) ou apenas um evento sem retorno
            if (isset($stdClass->procEventoNFe)) {
                $evento = $stdClass->procEventoNFe;
                $chaveNFe = $evento->evento->infEvento->chNFe ?? null;
                $tipoEvento = $evento->evento->infEvento->tpEvento ?? null;
                $dataEvento = $evento->evento->infEvento->dhEvento ?? null;
                $detEvento = $evento->evento->infEvento->detEvento ?? null;
                $nSeqEvento = $evento->evento->infEvento->nSeqEvento ?? 0;
                $protocolo = $evento->retEvento->infEvento->nProt ?? null;
                $statusSefaz = $evento->retEvento->infEvento->cStat ?? null;
                $motivo = $evento->retEvento->infEvento->xMotivo ?? null;
            } elseif (isset($stdClass->evento)) {
                $evento = $stdClass->evento;
                $chaveNFe = $evento->infEvento->chNFe ?? null;
                $tipoEvento = $evento->infEvento->tpEvento ?? null;
                $dataEvento = $evento->infEvento->dhEvento ?? null;
                $detEvento = $evento->infEvento->detEvento ?? null;
                $nSeqEvento = $evento->infEvento->nSeqEvento ?? 0;
                $protocolo = null;
                $statusSefaz = null;
                $motivo = null;
            } else {
                Log::warning("Formato de evento não reconhecido", [
                    'xml_hash' => md5($xmlContent),
                    'params' => $params
                ]);
                return false;
            }

            if (!$chaveNFe || !$tipoEvento) {
                Log::warning("Evento sem chave NFe ou tipo evento", [
                    'chave' => $chaveNFe,
                    'tipo' => $tipoEvento,
                    'params' => $params
                ]);
                return false;
            }

            // Converte a data para o formato do banco
            if ($dataEvento) {
                try {
                    $dataEvento = Carbon::parse($dataEvento)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    Log::warning("Erro ao converter data do evento", [
                        'data' => $dataEvento,
                        'erro' => $e->getMessage()
                    ]);
                    $dataEvento = now()->format('Y-m-d H:i:s');
                }
            } else {
                $dataEvento = now()->format('Y-m-d H:i:s');
            }

            // Determina descrição do evento
            $descricao = '';
            if ($detEvento && isset($detEvento->descEvento)) {
                $descricao = $detEvento->descEvento;
            }

            // Verifica se já existe um evento igual
            $eventoExistente = EventoNfe::where('chave_nfe', $chaveNFe)
                ->where('tipo_evento', $tipoEvento)
                ->where('numero_sequencial', $nSeqEvento)
                ->first();

            if ($eventoExistente) {
                // Atualiza o evento existente
                $eventoExistente->update([
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $xmlContent,
                ]);

            } else {
                // Cria um novo evento
                EventoNfe::create([
                    'organization_id' => $this->organization->id,
                    'chave_nfe' => $chaveNFe,
                    'tipo_evento' => $tipoEvento,
                    'numero_sequencial' => $nSeqEvento,
                    'data_evento' => $dataEvento,
                    'protocolo' => $protocolo,
                    'status_sefaz' => $statusSefaz,
                    'motivo' => $motivo,
                    'xml_evento' => $xmlContent,
                ]);

            }

            return true;
        } catch (\Exception $e) {
            Log::error("Erro ao processar evento", [
                'erro' => $e->getMessage(),
                'linha' => $e->getLine(),
                'arquivo' => $e->getFile(),
                'params' => $params
            ]);
            return false;
        }
    }

    /**
     * Processa a NFe completa
     */
    private function processarNFe(string $content): void
    {
        try {
            $xmlReader = new XmlNfeReaderService();
            $xmlReader->loadXml($content)
                ->parse()
                ->setOrigem('SIEG')
                ->save();
        } catch (Exception $e) {
            Log::error('Erro ao processar NFe da Sieg: ' . $e->getMessage());
            throw $e;
        }
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
