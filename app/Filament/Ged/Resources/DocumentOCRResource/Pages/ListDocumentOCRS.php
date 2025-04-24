<?php

namespace App\Filament\Ged\Resources\DocumentOCRResource\Pages;

use Imagick;
use Filament\Forms;
use Filament\Actions;
use Spatie\PdfToText\Pdf;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Tenant\DocumentOCR;
use Illuminate\Support\Facades\Log;
use Filament\Resources\Pages\ListRecords;
use thiagoalessio\TesseractOCR\TesseractOCR;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use App\Filament\Ged\Resources\DocumentOCRResource;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;

class ListDocumentOCRS extends ListRecords
{
    protected static string $resource = DocumentOCRResource::class;


    public function getTitle(): string
    {
        return 'Documentos Enviados';
    }

    protected function getHeaderActions(): array
    {
        return [

            Actions\Action::make('teste')
                ->label('Enviar documento')
                ->modalDescription('Envie os documentos para que os dados sejam extraídos.')
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->modalSubmitActionLabel('Extrair dados')
                ->form([
                    Forms\Components\FileUpload::make('files')
                        ->label('Documento (Imagem ou PDF)')
                        ->directory(fn() => 'ocr-uploads/' . now()->format('Y-m'))
                        ->multiple()
                        ->preserveFilenames()
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                        ->required()
                        ->maxSize(2048),
                ])
                ->action(function (array $data) {

                    foreach ($data['files'] as $file) {

                        $filePath = storage_path('app/public/' . $file);
                        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

                        $ocrText = null;

                        if ($extension === 'pdf') {
                            // Primeiro tenta extrair texto direto do PDF
                            $text = Pdf::getText($filePath);
                            $ocrText = trim($text);

                            // Se estiver vazio, converte para imagem e usa OCR
                            if (empty($ocrText)) {

                                $imagePath = storage_path('app/public/temp/' . Str::random(10) . '.png');

                                try {
                                    // Usa Imagick para converter a em imagem (instale via `pecl install imagick`)
                                    $imagick = new Imagick();

                                    // Configura a resolução para melhor qualidade
                                    $imagick->setResolution(300, 300);

                                    // Lê o PDF
                                    $imagick->readImage($filePath);

                                    // Converte para PNG
                                    $imagick->setImageFormat('png');

                                    // Salva a primeira página apenas
                                    $imagick->writeImage($imagePath);


                                    // Limpa a memória
                                    $imagick->clear();
                                    $imagick->destroy();

                                    if (!file_exists($imagePath)) {
                                        throw new \Exception("Falha ao criar imagem do PDF");
                                    }

                                    // Usa OCR na imagem gerada
                                    $ocrText = (new TesseractOCR($imagePath))->lang('por')->run();

                                    // Remove o arquivo temporário
                                    @unlink($imagePath);
                                } catch (\Exception $e) {
                                    Log::error('Erro ao processar PDF com ImageMagick: ' . $e->getMessage());
                                    throw $e;
                                }
                            }
                        } else {
                            // Arquivo é imagem e usa o OCR para extrair o texto
                            $ocrText = (new TesseractOCR($filePath))->lang('por')->run();
                        }

                        $prompt = "
                            Você é um extrator inteligente de dados de documentos financeiros, como boletos bancários, faturas de contas de consumo (água, luz, telefone), e similares.

                            Extraia com precisão as seguintes informações do conteúdo OCR de um documento:

                            - Razão social do beneficiário
                            - CNPJ do beneficiário
                            - Razão social do pagador
                            - CNPJ do pagador
                            - Valor a ser pago
                            - Data de vencimento
                            - Código digitável do boleto (caso exista)

                            1. **Razão Social do Beneficiário**: O nome completo da empresa ou entidade que receberá o pagamento, conforme indicado no documento.
                            2. **CNPJ do Beneficiário**: O número do Cadastro Nacional da Pessoa Jurídica (CNPJ) da entidade beneficiária, no formato XX.XXX.XXX/XXXX-XX.
                            3. **Razão Social do Pagador**: O nome completo da empresa, entidade ou pessoa responsável pelo pagamento, conforme indicado no documento.
                            4. **CNPJ do Pagador**: O número do Cadastro Nacional da Pessoa Jurídica (CNPJ) do pagador, no formato XX.XXX.XXX/XXXX-XX. Caso o pagador seja uma pessoa física, retornar vazio ou extrair o CPF, se presente, no formato XXX.XXX.XXX-XX.
                            5. **Valor a Ser Pago**: O valor monetário total a ser pago, incluindo centavos, no formato XXX,XX ou conforme apresentado no documento (ex.: 150,75). Fique atento aos valores inteiros, pois as vezes o valor é apresentado como 15000,00.
                            6. **Data de Vencimento**: A data de vencimento do pagamento, no formato AAAA-MM-DD (ex.: 2025-12-25). Se não houver data de vencimento explícita, inferir a partir de termos como \"vencimento\", \"pagar até\" ou similares.
                            7. **Linha Digitável do Boleto**: A sequência numérica completa do código de barras (geralmente 44 ou 48 dígitos) usada para pagamento do boleto. Se o documento não for um boleto, retornar vazio.

                            **Instruções**:

                            - Analise o texto, imagens, tabelas ou qualquer outro conteúdo presente no documento.
                            - Identifique padrões comuns, como campos rotulados (ex.: \"Beneficiário\", \"Pagador\", \"Valor\", \"Vencimento\") ou formatos numéricos específicos (CNPJ, código digitável).
                            - Ignore informações irrelevantes, como propagandas, logotipos ou textos genéricos.
                            - Caso alguma informação não esteja presente ou não seja clara, retorne \"Não identificado\" para o respectivo campo.
                            - Se o documento contiver múltiplos valores ou datas, selecione aqueles explicitamente associados ao pagamento principal.
                            - Para o código digitável, priorize a sequência numérica completa, geralmente próxima ao código de barras ou rotulada como \"Linha Digitável\".
                            - Retorne somente os dados extraídos em um formato estruturado, como JSON, com os campos listados acima.


                            O retorno somente o JSON com a seguinte estrutura:
                            {
                            \"beneficiario_razao_social\": \"\",
                            \"beneficiario_cnpj\": \"\",
                            \"pagador_razao_social\": \"\",
                            \"pagador_cnpj\": \"\",
                            \"valor\": \"\",
                            \"vencimento\": \"\",
                            \"linha_digitavel\": \"\"
                            }

                            Caso alguma informação não esteja visível ou legível no conteúdo fornecido, retorne o campo com valor null.

                            Agora, extraia os dados a partir do conteúdo OCR abaixo:
                            ----
                            $ocrText
                            ----
                        ";

                        $model = 'google/gemini-2.0-flash-exp';
                        $messageData = new MessageData(
                            content: $prompt,
                            role: RoleType::USER,
                        );

                        $chatData = new ChatData(
                            messages: [
                                $messageData,
                            ],
                            model: $model
                        );

                        $response = LaravelOpenRouter::chatRequest($chatData);

                        $content = Arr::get($response->choices[0], 'message.content');

                        $cleanJson = preg_replace('/json\n|\n|```/', '', $content);

                        $dados = json_decode(trim($cleanJson), true);

                        $valor = str_replace('.', '', $dados['valor']);
                        $valor = str_replace(',', '.', $valor);
                        DocumentOCR::updateOrCreate(
                            [
                                'beneficiario_razao_social' => $dados['beneficiario_razao_social'],
                                'beneficiario_cnpj' => $dados['beneficiario_cnpj'],
                                'pagador_razao_social' => $dados['pagador_razao_social'],
                                'pagador_cnpj' => $dados['pagador_cnpj'],
                                'valor' => $valor,
                                'vencimento' => $dados['vencimento'],
                            ],
                            [
                                'beneficiario_razao_social' => $dados['beneficiario_razao_social'],
                                'beneficiario_cnpj' => $dados['beneficiario_cnpj'],
                                'pagador_razao_social' => $dados['pagador_razao_social'],
                                'pagador_cnpj' => $dados['pagador_cnpj'],
                                'valor' => $valor,
                                'vencimento' => $dados['vencimento'],
                                'linha_digitavel' => $dados['linha_digitavel'],
                                'raw_text' => $ocrText,
                                'file' => $file,
                            ]
                        );
                    }
                }),
        ];
    }
}
