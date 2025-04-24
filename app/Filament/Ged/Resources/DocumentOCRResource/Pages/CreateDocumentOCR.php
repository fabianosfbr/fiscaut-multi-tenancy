<?php

namespace App\Filament\Ged\Resources\DocumentOCRResource\Pages;

use Imagick;
use Filament\Actions;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Filament\Resources\Pages\CreateRecord;
use thiagoalessio\TesseractOCR\TesseractOCR;
use MoeMizrak\LaravelOpenrouter\DTO\ChatData;
use MoeMizrak\LaravelOpenrouter\Types\RoleType;
use MoeMizrak\LaravelOpenrouter\DTO\MessageData;
use App\Filament\Ged\Resources\DocumentOCRResource;
use MoeMizrak\LaravelOpenrouter\Facades\LaravelOpenRouter;

class CreateDocumentOCR extends CreateRecord
{
    protected static string $resource = DocumentOCRResource::class;


    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $filePath = storage_path('app/public/' . $data['file']);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $ocrText = null;

        if ($extension === 'pdf') {
            // Primeiro tenta extrair texto direto do PDF
            $text = Pdf::getText($filePath);
            $ocrText = trim($text);


            // Se estiver vazio, converte para imagem e usa OCR
            if (empty($ocrText)) {

                $imagePath = storage_path('app/public/temp/pdf_ocr_image.png');

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
            // Arquivo é imagem
            $ocrText = (new TesseractOCR($filePath))->lang('por')->run();
        }

        Log::info($ocrText);

        $data['raw_text'] = $ocrText;


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
            4. **CNPJ do Pagador**: O número do Cadastro Nacional da Pessoa Jurídica (CNPJ) do pagador, no formato XX.XXX.XXX/XXXX-XX. Caso o pagador seja uma pessoa física, retornar \"Não aplicável\" ou extrair o CPF, se presente, no formato XXX.XXX.XXX-XX.
            5. **Valor a Ser Pago**: O valor monetário total a ser pago, incluindo centavos, no formato R$ XXX,XX ou conforme apresentado no documento (ex.: 150,75).
            6. **Data de Vencimento**: A data de vencimento do pagamento, no formato AAAA-MM-DD (ex.: 2025-12-25). Se não houver data de vencimento explícita, inferir a partir de termos como \"vencimento\", \"pagar até\" ou similares.
            7. **Código Digitável do Boleto**: A sequência numérica completa do código de barras (geralmente 44 ou 48 dígitos) usada para pagamento do boleto. Se o documento não for um boleto, retornar \"Não aplicável\".

            **Instruções**:

            - Analise o texto, imagens, tabelas ou qualquer outro conteúdo presente no documento.
            - Identifique padrões comuns, como campos rotulados (ex.: \"Beneficiário\", \"Pagador\", \"Valor\", \"Vencimento\") ou formatos numéricos específicos (CNPJ, código digitável).
            - Ignore informações irrelevantes, como propagandas, logotipos ou textos genéricos.
            - Caso alguma informação não esteja presente ou não seja clara, retorne \"Não identificado\" para o respectivo campo.
            - Se o documento contiver múltiplos valores ou datas, selecione aqueles explicitamente associados ao pagamento principal.
            - Para o código digitável, priorize a sequência numérica completa, geralmente próxima ao código de barras ou rotulada como \"Linha Digitável\".
            - Retorne somenteos dados extraídos em um formato estruturado, como JSON, com os campos listados acima.


            O retorno deve ser um array PHP com a seguinte estrutura:
            [
            \"beneficiario_razao_social\" => \"\",
            \"beneficiario_cnpj\" => \"\",
            \"pagador_razao_social\" => \"\",
            \"pagador_cnpj\" => \"\",
            \"valor\" => \"\",
            \"vencimento\" => \"\",
            \"codigo_digitavel\" => \"\"
            ]

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


        dd($content);

        // Extração (pode refatorar para um service depois)
        // $data['beneficiario_cnpj'] = self::extractCNPJ($ocrText, 'beneficiario');
        // $data['beneficiario_nome'] = self::extractNome($ocrText, 'beneficiario');
        // $data['pagador_cnpj'] = self::extractCNPJ($ocrText, 'pagador');
        // $data['pagador_nome'] = self::extractNome($ocrText, 'pagador');
        // $data['vencimento'] = self::extractVencimento($ocrText);
        // $data['linha_digitavel'] = self::extractLinhaDigitavel($ocrText);
        return $data;
    }

    // Regras simples de extração — modularizáveis depois
    protected static function extractCNPJ(string $text, string $context): ?string
    {
        if ($context === 'beneficiario') {
            // Padrão específico para o formato apresentado
            if (preg_match('/Benefici[aá]rio\s*([^|]+)\s*\|\s*CPF\/CNPJ:\s*([\d\.,\/-]+)/i', $text, $m)) {
                return preg_replace('/\D/', '', $m[2]);
            }

            // Padrão alternativo mais genérico
            if (preg_match('/CPF\/CNPJ[: ]*([\d\.\/-]{14,18})/i', $text, $m)) {
                return preg_replace('/\D/', '', $m[1]);
            }
        }

        if ($context === 'pagador' && preg_match('/CNPJ\/CPF[: ]*([\d\.\/-]{14,18})/i', $text, $m)) {
            return preg_replace('/\D/', '', $m[1]);
        }

        return null;
    }

    protected static function extractNome(string $text, string $context): ?string
    {
        if ($context === 'beneficiario') {
            // Padrão específico para o formato com pipe e CNPJ
            if (preg_match('/Benefici[aá]rio\s*([^|]+)\s*\|/i', $text, $m)) {
                return trim($m[1]);
            }

            // Padrão alternativo mais genérico
            if (preg_match('/Benefici[aá]rio[\s:\-]*([^\n]+)/i', $text, $m)) {
                return trim($m[1]);
            }
        }

        if ($context === 'pagador') {
            // Padrão específico para o formato com pipe e CNPJ
            if (preg_match('/Pagador\s*([^|]+)\s*\|/i', $text, $m)) {
                return trim($m[1]);
            }

            // Padrão alternativo mais genérico
            if (preg_match('/Pagador[\s:\-]*([^\n]+)/i', $text, $m)) {
                return trim($m[1]);
            }
        }

        return null;
    }

    protected static function extractVencimento(string $text): ?string
    {
        if (preg_match('/Vencimento[\s:\-]*([0-9]{2}\/[0-9]{2}\/[0-9]{4})/i', $text, $m)) {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $m[1])->toDateString();
        }

        return null;
    }

    protected static function extractLinhaDigitavel(string $text): ?string
    {
        if (preg_match('/\b(\d{5}\.\d{5} \d{5}\.\d{6} \d{5}\.\d{6} \d{1} \d{14})\b/', $text, $m)) {
            return preg_replace('/\D/', '', $m[1]);
        }

        // Alternativa mais permissiva
        if (preg_match('/(\d{47,48})/', $text, $m)) {
            return $m[1];
        }

        return null;
    }
}
